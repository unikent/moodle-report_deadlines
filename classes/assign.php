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
 * Assign functions for Deadline Report
 *
 * @package    report_deadlines
 * @copyright  2014 Jake Blatchford <J.Blatchford@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_deadlines;

defined('MOODLE_INTERNAL') || die();

class assign {
    public static function get_deadlines($showpast) {
        global $DB;

        $where = $showpast ? '' : 'WHERE duedate > unix_timestamp()';

        $sql =
<<<SQL
    SELECT
        b.assign_id as id,
        'Assign' as type,
        b.name as name,
        b.course as course,
        b.start as start,
        b.end as end,
        b.submissions as activity,
        COUNT(ue.id) as enrolled_students
    FROM
        (SELECT
            a.id as assign_id,
                a.course as course_id,
                a.name as name,
                a.allowsubmissionsfromdate as start,
                a.duedate as end,
                c.shortname as course,
                COUNT(ass.userid) as submissions
        FROM
            {assign} a
        INNER JOIN {course} c ON c.id = a.course
        LEFT OUTER JOIN {assign_submission} ass ON ass.assignment = a.id
        $where
        GROUP BY a.id) b
            INNER JOIN
        {enrol} e ON e.courseid = b.course_id
            INNER JOIN
        {user_enrolments} ue ON ue.enrolid = e.id
    WHERE
        e.roleid IN (SELECT
                id
            FROM
                {role}
            WHERE
                shortname = 'student'
                    OR shortname = 'sds_student')
    GROUP BY b.assign_id
    ORDER BY b.end ASC, b.assign_id ASC
SQL;

        return $DB->get_records_sql($sql, array());
    }
}
