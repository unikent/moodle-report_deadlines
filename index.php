<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Deadlines report
 *
 * @package    report_deadlines
 * @copyright  2014 Jake Blatchford <J.Blatchford@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $OUTPUT, $DB;

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Page parameters.
$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);
$showpast = optional_param('showpast', 0, PARAM_BOOL);

$headings = array(
    "Type",
    "Module Shortcode",
    "Activity Name",
    "Start Date",
    "Due Date",
    "Actions",
    "Students on course"
);

admin_externalpage_setup('reportdeadlines', '', null, '', array('pagelayout' => 'report'));

$PAGE->set_title(get_string('pluginname', 'report_deadlines'));
$PAGE->requires->js_init_call('M.report_deadlines.init', array(), false, array(
    'name' => 'report_deadlines',
    'fullpath' => '/report/deadlines/module.js'
));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deadlines', 'report_deadlines'));

$table = new html_table();
$table->head = $headings;

$table->colclasses = array(
    'leftalign assignment',
    'leftalign students_on_assignment',
    'leftalign students_with_grades',
    'leftalign students_on_course'
);
$table->id = 'deadlines';
$table->attributes['class'] = 'admintable generaltable';
$table->data = array();

$deadlines = array();

// MUC - Can we grab from cache?
$cachekey = 'deadlines_' . $showpast;
$cache = cache::make('report_deadlines', 'report_deadlines');
$content = $cache->get($cachekey);

if ($content !== false) {
    $deadlines = $content;
} else {
    // Turnitin deadlines.
    $tdeadlines = \report_deadlines\turnitintool::get_deadlines($showpast);

    if (!empty($tdeadlines)) {
        $deadlines = array_merge($deadlines, $tdeadlines);
    }

    // Quiz deadlines.
    $qdeadlines = \report_deadlines\quiz::get_deadlines($showpast);

    if (!empty($qdeadlines)) {
        $deadlines = array_merge($deadlines, $qdeadlines);
    }

    // Assign deadlines.
    $adeadlines = \report_deadlines\assign::get_deadlines($showpast);

    if (!empty($adeadlines)) {
        $deadlines = array_merge($deadlines, $adeadlines);
    }

    // Sort deadlines by date.
    usort($deadlines, function($a, $b) {
        if ($a->end >= $b->end) {
            return 1;
        }
        return 0;
    });

    // Set cache.
    $cache->set($cachekey, $deadlines);
}

$baseurl = new moodle_url('index.php', array('perpage' => $perpage));

// Grab page of data.
$deadlines = array_slice($deadlines, $page * $perpage, $perpage);

foreach ($deadlines as $data) {
    $row = array();
    $row[] = s($data->type);
    $row[] = s($data->course);
    $row[] = s($data->name);
    $row[] = s(date("Y-m-d H:i", $data->start));
    $row[] = s(date("Y-m-d H:i", $data->end));
    $row[] = s($data->activity);
    $row[] = s($data->enrolled_students);

    $table->data[] = $row;
}

echo \html_writer::checkbox('showpast', true, $showpast, 'Show Past Deadlines?', array(
    'id' => 'showpastchk'
));

echo html_writer::table($table);

echo $OUTPUT->paging_bar(count($deadlines), $page, $perpage, $baseurl);

echo $OUTPUT->footer();
