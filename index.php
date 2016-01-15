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
$format = optional_param('format', '', PARAM_ALPHA);

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
    unset($tdeadlines);

    // TurnitinTwo deadlines.
    $ttdeadlines = \report_deadlines\turnitintooltwo::get_deadlines($showpast);
    if (!empty($ttdeadlines)) {
        $deadlines = array_merge($deadlines, $ttdeadlines);
    }
    unset($ttdeadlines);

    // Quiz deadlines.
    $qdeadlines = \report_deadlines\quiz::get_deadlines($showpast);
    if (!empty($qdeadlines)) {
        $deadlines = array_merge($deadlines, $qdeadlines);
    }
    unset($qdeadlines);

    // Assign deadlines.
    $adeadlines = \report_deadlines\assign::get_deadlines($showpast);
    if (!empty($adeadlines)) {
        $deadlines = array_merge($deadlines, $adeadlines);
    }
    unset($adeadlines);

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

$baseurl = new moodle_url('/report/deadlines/index.php', array(
    'perpage' => $perpage,
    'format' => $format
));

if($format=="graph") {
    // Grab 200 submissions
    $graphdata = array_slice($deadlines, 0, 200);

    $timesummary = array();
    foreach ($graphdata as $data) {
        // round date to hour
        $time = $data->end - ($data->end % 3600);

        if(!isset($timesummary[$time])) {
            $timesummary[$time] = array("activity" => $data->activity, "enrolled_students" => $data->enrolled_students);
        } else {
            $timesummary[$time]["activity"] += $data->activity;
            $timesummary[$time]["enrolled_students"] += $data->enrolled_students;
        }
    }

    $graphchartdata = array();
    foreach($timesummary as $time => $g) {
        $timestring = "Date(" . date("Y", $time) . "," . (date("m", $time)-1) . "," . date("d", $time) . "," . date("G", $time) . "," . date("i", $time) .")";
        $graphchartdata[] = array($timestring, $g["activity"], $g["enrolled_students"]);
    }

    // Add columns onto array
    $columns = array(
        array("type" => "datetime", "label" => "Date"),
        array("type" => "number", "label" => "Activity"),
        array("type" => "number", "label" => "Students on modules")
    );
    array_unshift($graphchartdata, $columns);

    $graph_json = json_encode($graphchartdata);
}

if ($format == 'csv') {
    require_once($CFG->libdir . "/csvlib.class.php");

    $export = new csv_export_writer();
    $export->set_filename('Deadlines-Report');
    $export->add_data($table->head);
}

// Grab page of data.
$deadlineset = array_slice($deadlines, $page * $perpage, $perpage);

foreach ($deadlineset as $data) {
    $row = array();
    $row[] = s($data->type);
    $row[] = s($data->course);
    $row[] = s($data->name);
    $row[] = s(date("Y-m-d H:i", $data->start));
    $row[] = s(date("Y-m-d H:i", $data->end));
    $row[] = s($data->activity);
    $row[] = s($data->enrolled_students);

    $table->data[] = $row;

    if ($format == 'csv') {
        $export->add_data($row);
    }
}

if ($format == 'csv') {
    $export->download_file();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deadlines', 'report_deadlines'));

echo \html_writer::checkbox('showpast', true, $showpast, 'Show Past Deadlines?', array(
    'id' => 'showpastchk'
));

$link = new \moodle_url($baseurl);
$link->param('format', 'graph');
$link = \html_writer::tag('a', 'Show Deadline Graph', array(
    'href' => $link
));
echo '<p>'.$link.'</p>';

if ($format == 'graph') {
    echo "<script>deadline_report_data = {$timesummary}</script>";
    echo <<<CHARTJS
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable(deadline_report_data);

        var options = {
          title: 'Deadline Report',
          width: '900px',
          legend: {position: 'bottom'},
          hAxis: {title: 'Date', format: 'EEE d HH:mm'},
          isStacked: 'true'
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
CHARTJS;
    echo '<div id="chart_div" style="width: 900px; height: 500px;"></div>';
}

echo html_writer::table($table);

echo $OUTPUT->paging_bar(count($deadlines), $page, $perpage, $baseurl);

$link = new \moodle_url($baseurl);
$link->param('perpage', 999999);
$link->param('format', 'csv');
$link = \html_writer::tag('a', 'Download as CSV', array(
    'href' => $link
));
echo '<p>'.$link.'</p>';

echo $OUTPUT->footer();
