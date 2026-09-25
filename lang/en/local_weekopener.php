<?php
/**
 * English strings.
 *
 * @package local_weekopener
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Week opener';
$string['privacy:metadata'] = 'The Week opener plugin stores course settings only, nothing about people.';
$string['weekopener:manage'] = 'Set the dates course sections open on';

$string['nav'] = 'Opening dates';
$string['intro'] = 'Give each section an opening date, one interval apart, from the starting section on. Sections before it open straight away. The dates are ordinary <em>Date</em> restrictions: they show in each section\'s <em>Restrict access</em> settings, can be changed there, and any other restrictions a section has are kept.';

$string['sectionlabel'] = '{$a->number}. {$a->name}';
$string['startsection'] = 'Starting section';
$string['startsection_help'] = 'The first section to get an opening date. Every section before it has its opening date removed, so it opens straight away.';
$string['starttime'] = 'Opens on';
$string['starttime_help'] = 'When the starting section opens. Each later section opens one interval after the one before it, at the same time of day.';
$string['intervaldays'] = 'Days between sections';
$string['intervaldays_invalid'] = 'Enter a whole number of days from 1 to 365.';
$string['excluded'] = 'Skip these sections';
$string['excluded_help'] = 'Skipped sections are left exactly as they are, and take no place in the schedule: the section after one opens on the date it would have had.';
$string['excluded_none'] = 'None';
$string['excluded_start'] = 'The starting section cannot be skipped.';
$string['whilelocked'] = 'Until a section opens';
$string['whilelocked_help'] = 'Whether students see a section\'s name, greyed out with the date it opens, or do not see the section at all until that date.';
$string['whilelocked_show'] = 'Show its name and when it opens';
$string['whilelocked_hide'] = 'Hide it completely';
$string['apply'] = 'Apply opening dates';
$string['applied'] = 'Opening dates applied. {$a} section(s) changed.';

$string['current'] = 'Current opening dates';
$string['opens'] = 'Opens';
$string['opens_now'] = 'Open now';
$string['skipped'] = 'Skipped';

$string['clear'] = 'Remove all opening dates';
$string['clear_desc'] = 'Removes the opening date from every section except the skipped ones. Other restrictions stay.';
$string['clear_confirm'] = 'Remove the opening date from every section except the skipped ones?';
$string['cleared'] = 'Opening dates removed. {$a} section(s) changed.';

$string['availabilityoff'] = 'Opening dates are date restrictions, and restrictions are switched off on this site. An administrator can switch them on under Site administration → Advanced features → Enable restricted access.';
$string['nosections'] = 'This course has no sections to schedule.';
