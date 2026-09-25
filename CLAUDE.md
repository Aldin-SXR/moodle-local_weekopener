# local_weekopener: working notes for Claude

A Moodle local plugin that sets the dates a course's sections open on. It writes
ordinary `availability_date` restrictions into `course_sections.availability`.
It never adds its own gate, so everything it does can be seen and edited in
the section's *Restrict access* settings, and uninstalling the plugin leaves
the dates in place.

This repo *is* the plugin. It is mounted into a dockerized Moodle at
`/var/www/html/local/weekopener` for development and testing.

## Running the stack

```bash
cp .env.example .env      # once
docker compose up -d      # first boot installs Moodle, PHPUnit and Behat (~5 min)
```

Moodle: <http://localhost:8380> (admin / `Admin1234!`).
Containers: `moodle-week-opener-moodle-1`, `-db-1`, `-selenium-1`.

## Running tests (you can and should do this)

```bash
# Re-init after adding or renaming test files, or bumping version.php:
docker exec moodle-week-opener-moodle-1 sh -lc \
  'php /var/www/html/admin/tool/phpunit/cli/init.php'

# Whole plugin suite:
docker exec moodle-week-opener-moodle-1 sh -lc \
  'cd /var/www/html && php vendor/bin/phpunit --testsuite local_weekopener_testsuite --configuration local/weekopener/phpunit.xml'
```

A change to `db/install.xml` is not picked up by `init.php`; drop the test site
first with `php admin/tool/phpunit/cli/util.php --drop`.

## Running Behat

Behat drives the Chrome in `moodle-week-opener-selenium-1`. Re-init after
adding or changing a feature, and pass **`--profile=chrome`**:

```bash
docker exec moodle-week-opener-moodle-1 sh -lc \
  'cd /var/www/html && php admin/tool/behat/cli/init.php'

docker exec moodle-week-opener-moodle-1 sh -lc \
  'cd /var/www/html && vendor/bin/behat --config /var/moodledata/behat/behatrun/behat/behat.yml --profile=chrome --tags=@local_weekopener'
```

If a step fails with `session deleted because of page crash` or a WebDriver
timeout, the Chromium container is at fault, not the code:
`docker restart moodle-week-opener-selenium-1`.

## After a change that needs it

Bump `version.php` (`YYYYMMDDXX`) for DB schema, capabilities, **and new or
changed language strings**, then:

```bash
docker exec moodle-week-opener-moodle-1 sh -lc 'php /var/www/html/admin/cli/upgrade.php --non-interactive'
```

## How it works

- `planner` is pure arithmetic. It takes section ids in course order and
  returns section id => opening time, or null for "open now". Excluded sections
  are left out and take no slot. Days are added on the calendar in the user's
  timezone, so the opening time stays the same local time across a DST change.
- `availability_editor` is pure JSON. The opening date is a **top-level
  `{"type":"date","d":">="}` condition of an AND root**. Setting one replaces any
  such condition already there, whoever set it. "Until" dates, other conditions
  and nested trees are kept. A root that isn't an AND (OR, NOT) becomes a
  subtree next to the date, so its meaning doesn't change. "Hide completely" is
  `showc: false` on the date only.
- `scheduler` reads the sections and writes the result through
  `formatactions::section()->update()`, so the course cache is rebuilt and
  `course_section_updated` is logged. It writes only where the JSON changes,
  so reapplying a schedule is a no-op.
- `schedule_store` remembers the last applied schedule per course (section
  **ids**, not numbers, so reordering sections doesn't break it) to prefill the
  form. An observer deletes it with the course. No personal data, so the
  privacy provider is a null provider.

Section 0 is never touched. Subsections (delegated sections) are recognised by
`section_info->component` and skipped; they open with their parent. Don't use
`get_listed_section_info_all()` for this, because it lists subsections as
ordinary sections while `mod_subsection` is disabled, which is 4.5's default.

A section hidden with the eye icon (`visible = 0`) is never shown or hidden by
the plugin; it still gets its date, and the table marks it *Hidden* so a teacher
is not misled into thinking it will open on that date.

The *Skip these sections* autocomplete keeps its scroll position through
`amd/src/keep_scroll.js`: core re-renders the suggestions after every pick
and scrolls to the first one. The module leans on core's autocomplete markup,
so check it still works (the Behat scenario does) after a Moodle upgrade.
`amd/build` must be rebuilt after editing the source — Moodle's `grunt amd`
from the Moodle root, or any babel AMD transform + terser that produces a
**named** `define("local_weekopener/keep_scroll", ...)`.

Moodle 4.5 names every new section "New section", so the form and table label
sections as `N. name`.

Course backup and restore carry the dates, because they are core restrictions.
The stored schedule isn't carried, so a restored course's form falls back to
its defaults.

## Conventions

- Terse code that explains itself. Comments only where the *why* is not obvious.
- Classes in `classes/` under the `local_weekopener` namespace.
- Short file docblocks (`@package local_weekopener`).
- Every user-visible string goes through `get_string()`.
- Unit tests for pure logic, `advanced_testcase` for anything touching the DB.
  New behaviour ships with tests.
- Check current Moodle API usage against the source in the container
  (`/var/www/html`) or `ctx7` rather than recalled signatures.

## Branching & committing

One branch per feature or fix (`feat/...`, `fix/...`); do not commit to `main`.
CI (`.github/workflows/ci.yml`) runs phplint, validate, PHPUnit and Behat
against Moodle 4.5 / PHP 8.2.
