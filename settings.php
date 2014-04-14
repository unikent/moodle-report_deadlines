<?php

/**
 * Upcoming deadlines report settings
 *
 * @package    report
 * @subpackage deadlines
 * @copyright  2014 Jake Blatchford
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportdeadlines', get_string('deadlines', 'report_deadlines'), "$CFG->wwwroot/report/deadlines/index.php"));

// no report settings
$settings = null;
