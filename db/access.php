<?php
/**
 * Capability definitions.
 *
 * @package local_weekopener
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Set the dates a course's sections open on.
    'local/weekopener:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],
];
