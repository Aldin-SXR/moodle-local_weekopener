#!/bin/bash
set -e

MOODLE_ROOT=/var/www/html
CONFIG_FILE="${MOODLE_ROOT}/config.php"
CONFIG_BACKUP=/var/moodledata/.config.php
INSTALLED_FLAG=/var/moodledata/.installed

echo "Waiting for database at ${MOODLE_DB_HOST:-db}:${MOODLE_DB_PORT:-3306}..."
until php -r "
    \$conn = @new mysqli(
        '${MOODLE_DB_HOST:-db}',
        '${MOODLE_DB_USER:-moodle}',
        '${MOODLE_DB_PASSWORD:-moodlepass}',
        '${MOODLE_DB_NAME:-moodle}',
        ${MOODLE_DB_PORT:-3306}
    );
    if (\$conn->connect_error) { exit(1); }
    exit(0);
" 2>/dev/null; do
    echo "  DB not ready, retrying in 3s..."
    sleep 3
done
echo "Database is ready."

if [ -f "${INSTALLED_FLAG}" ]; then
    if [ ! -f "${CONFIG_FILE}" ] && [ -f "${CONFIG_BACKUP}" ]; then
        echo "Restoring config.php from data volume..."
        cp "${CONFIG_BACKUP}" "${CONFIG_FILE}"
        chown www-data:www-data "${CONFIG_FILE}"
    fi
else
    echo "First boot: running Moodle CLI installer (this takes a few minutes)..."
    php "${MOODLE_ROOT}/admin/cli/install.php" \
        --wwwroot="${MOODLE_WWWROOT:-http://localhost:8380}" \
        --dataroot=/var/moodledata \
        --dbtype=mariadb \
        --dbhost="${MOODLE_DB_HOST:-db}" \
        --dbport="${MOODLE_DB_PORT:-3306}" \
        --dbname="${MOODLE_DB_NAME:-moodle}" \
        --dbuser="${MOODLE_DB_USER:-moodle}" \
        --dbpass="${MOODLE_DB_PASSWORD:-moodlepass}" \
        --adminuser="${MOODLE_ADMIN_USER:-admin}" \
        --adminpass="${MOODLE_ADMIN_PASSWORD:-Admin1234!}" \
        --adminemail="${MOODLE_ADMIN_EMAIL:-admin@example.com}" \
        --fullname="${MOODLE_SITE_NAME:-local_weekopener Dev}" \
        --shortname=moodle \
        --non-interactive \
        --agree-license
    echo "Moodle installation complete."

    php -r "
        \$cfg = file_get_contents('${CONFIG_FILE}');
        \$insert  = \"\\\$CFG->phpunit_prefix   = 'phpu_';\n\";
        \$insert .= \"\\\$CFG->phpunit_dataroot = '/var/moodledata/phpunit';\n\";
        \$insert .= \"\\\$CFG->behat_wwwroot    = 'http://moodle';\n\";
        \$insert .= \"\\\$CFG->behat_prefix     = 'beh_';\n\";
        \$insert .= \"\\\$CFG->behat_dataroot   = '/var/moodledata/behat';\n\";
        \$insert .= \"\\\$CFG->behat_selenium2url = 'http://selenium:4444/wd/hub';\n\";
        \$insert .= \"\\\$CFG->behat_profiles    = ['chrome' => ['browser' => 'chrome', 'wd_host' => 'http://selenium:4444/wd/hub', 'capabilities' => ['extra_capabilities' => ['chromeOptions' => ['args' => ['--no-sandbox', '--disable-dev-shm-usage']]]]]];\n\";
        \$cfg = str_replace(\"require_once(__DIR__ . '/lib/setup.php');\", \$insert . \"require_once(__DIR__ . '/lib/setup.php');\", \$cfg);
        file_put_contents('${CONFIG_FILE}', \$cfg);
    "
    chown www-data:www-data "${CONFIG_FILE}"

    cp "${CONFIG_FILE}" "${CONFIG_BACKUP}"
    touch "${INSTALLED_FLAG}"

    echo "Initialising PHPUnit..."
    php "${MOODLE_ROOT}/admin/tool/phpunit/cli/init.php" || true
    echo "Initialising Behat..."
    php "${MOODLE_ROOT}/admin/tool/behat/cli/init.php" || true
fi

echo "Starting Moodle cron loop (every 60s)..."
(
    while true; do
        su www-data -s /bin/bash -c "php ${MOODLE_ROOT}/admin/cli/cron.php" \
            >> /var/moodledata/cron.log 2>&1 || true
        sleep 60
    done
) &

exec "$@"
