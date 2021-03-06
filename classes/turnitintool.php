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
 * Turnitin functions for Deadline Report
 *
 * @package    report_deadlines
 * @copyright  2014 Jake Blatchford <J.Blatchford@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_deadlines;

defined('MOODLE_INTERNAL') || die();

class turnitintool {
    public static function get_deadlines($showpast) {
        global $DB;

        $where = $showpast ? '' : 'WHERE tp.dtdue > unix_timestamp()';

        $sql =
<<<SQL
    SELECT
        a.id as id,
        'Turnitin' as type,
        a.name as name,
        a.course as course,
        a.start as start,
        a.end as end,
        a.submissions as activity,
        COUNT(ra.id) as enrolled_students
    FROM
        (
            SELECT
                t.id as id,
                CONCAT(t.name, ' - ', tp.partname) as name,
                t.course as course_id,
                c.shortname as course,
                tp.dtstart as start,
                tp.dtdue as end,
                COUNT(ts.userid) as submissions
            FROM
                {turnitintool} t
            INNER JOIN {course} c ON c.id = t.course
            INNER JOIN {turnitintool_parts} tp ON tp.turnitintoolid = t.id
            LEFT OUTER JOIN {turnitintool_submissions} ts ON ts.turnitintoolid = t.id AND ts.submission_part = tp.id
            $where
            GROUP BY t.id , tp.id
        ) a
    INNER JOIN {context} ctx
        ON ctx.instanceid = a.course_id
        AND ctx.contextlevel=:contextlevel
    INNER JOIN {role_assignments} ra
        ON ra.contextid = ctx.id
    INNER JOIN {role} r
        ON r.id=ra.roleid
    WHERE
        r.shortname IN ("student", "sds_student")
    GROUP BY a.id
SQL;

        return $DB->get_records_sql($sql, array(
            'contextlevel' => \CONTEXT_COURSE
        ));
    }
}
