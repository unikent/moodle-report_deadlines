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

// page parameters
$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);    // how many per page

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

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deadlines', 'report_deadlines'));

$table = new html_table();
$table->head = $headings;

$table->colclasses = array('leftalign assignment', 'leftalign students_on_assignment', 'leftalign students_with_grades', 'leftalign students_on_course');
$table->id = 'deadlines';
$table->attributes['class'] = 'admintable generaltable';
$table->data = array();

$deadlines = array();

// MUC - Can we grab from cache?
$cache = cache::make('report_deadlines', 'report_deadlines');
$cache_content = $cache->get('deadlines');

if ($cache_content !== false) {
    $deadlines = $cache_content;
} else {
    // turnitin deadlines
    $t_deadlines = \report_deadlines\turnitintool::get_deadlines();

    if (!empty($t_deadlines)) {
        $deadlines = array_merge($deadlines, $t_deadlines);
    }

    // quiz deadlines
    $q_deadlines = \report_deadlines\quiz::get_deadlines();

    if (!empty($q_deadlines)) {
        $deadlines = array_merge($deadlines, $q_deadlines);
    }

    // assign deadlines
    $a_deadlines = \report_deadlines\assign::get_deadlines();

    if (!empty($a_deadlines)) {
        $deadlines = array_merge($deadlines, $a_deadlines);
    }

    // sort deadlines by date
    usort($deadlines, function($a, $b) {
        if ($a->end >= $b->end) {
            return 1;
        }
        return 0;
    });

    // set cache
    $cache->set('deadlines', $deadlines);
}

$baseurl = new moodle_url('index.php', array('perpage' => $perpage));
echo $OUTPUT->paging_bar(count($deadlines), $page, $perpage, $baseurl);

// grab page of data
$deadlines = array_slice($deadlines, $page*$perpage, $perpage);

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

echo html_writer::table($table);

echo $OUTPUT->footer();
