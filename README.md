# local_weekopener

A Moodle plugin that gives every section of a course an opening date in one step.

Pick a starting section, the date it opens, and how many days apart the sections
should be (7 by default). Sections before the starting one open straight away.
From the starting one on, each section opens one interval after the last. Skipped
sections are left as they are and don't count toward the schedule. Until a section
opens, students either see its name greyed out with its opening date, or don't
see it at all.

The dates are Moodle's own *Date* restrictions. They show under each section's
*Restrict access*, can be edited there, and any other restrictions a section has
are kept.

Open it from a course's *More → Opening dates*.

## Requirements

Moodle 4.5+, PHP 8.2+, with *Enable restricted access* switched on (Moodle's default).

## Development

```bash
cp .env.example .env
docker compose up -d
```

Moodle comes up at <http://localhost:8380> (admin / `Admin1234!`) with the plugin
mounted at `local/weekopener`. Source edits are live without a rebuild. Test
commands are in [CLAUDE.md](CLAUDE.md).

## Installation on a real site

Copy this directory to `local/weekopener` in your Moodle root and visit
*Site administration → Notifications*.

## Licence

GNU GPL v3 or later.
