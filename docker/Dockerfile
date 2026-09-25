FROM moodlehq/moodle-php-apache:8.2

ARG MOODLE_TAG=v4.5.0

RUN set -eux; \
    curl -fsSL "https://github.com/moodle/moodle/archive/refs/tags/${MOODLE_TAG}.tar.gz" \
        | tar -xz --strip-components=1 -C /var/www/html/; \
    chown -R www-data:www-data /var/www/html

RUN if ! command -v composer >/dev/null 2>&1; then \
        curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    fi

RUN cd /var/www/html && composer install --no-interaction --prefer-dist

RUN mkdir -p /var/moodledata && chown -R www-data:www-data /var/moodledata
VOLUME /var/moodledata

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
