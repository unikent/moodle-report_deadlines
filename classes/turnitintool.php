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
    public static function get_deadlines() {
        global $DB;

        $sql =
<<<SQL
    SELECT
        t.id as id,
        "Turnitin" as type,
        CONCAT(t.name, " - ", tp.partname) as name,
        c.shortname as course,
        tp.dtstart as start,
        tp.dtdue as end,
        "--" as activity,
        COUNT(ue.userid) as enrolled_students
    FROM
        {turnitintool} t
            INNER JOIN
        {turnitintool_parts} tp ON tp.turnitintoolid = t.id
            INNER JOIN
        {enrol} e ON e.courseid = t.course
            INNER JOIN
        {course} c ON c.id = t.course
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
            AND tp.dtdue > UNIX_TIMESTAMP()
    GROUP BY t.id , tp.id
    ORDER BY tp.dtdue ASC , tp.id ASC
SQL;

        return $DB->get_records_sql($sql, array());
    }
}
