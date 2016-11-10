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
 * Description of ncsubook restore task
 *
 * @package    mod_ncsubook
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ncsubook/backup/moodle2/restore_ncsubook_stepslib.php'); // Because it exists (must)

class restore_ncsubook_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     *
     * @return void
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_ncsubook_activity_structure_step('ncsubook_structure', 'ncsubook.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return array
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('ncsubook', array('intro'), 'ncsubook');
        $contents[] = new restore_decode_content('ncsubook_chapters', array('content'), 'ncsubook_chapter');
        // Gary Harris - 4/25/2013
        // Added the following line for block content
        $contents[] = new restore_decode_content('ncsubook_blocks', array('content'), 'ncsubook_block');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     *
     * @return array
     */
    static public function define_decode_rules() {
        $rules = array();

        // List of ncsubooks in course
        // Gary Harris - 4/25/2013
        // Removed an underscore from the first argument in all of the restore_decode_rule lines below. The underscore was
        // after the 'NCSU' part of the string.
        // End GDH
        $rules[] = new restore_decode_rule('NCSUBOOKINDEX', '/mod/ncsubook/index.php?id=$1', 'course');

        // ncsubook by cm->id
        $rules[] = new restore_decode_rule('NCSUBOOKVIEWBYID', '/mod/ncsubook/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('NCSUBOOKVIEWBYIDCH', '/mod/ncsubook/view.php?id=$1&amp;chapterid=$2', array('course_module', 'ncsubook_chapter'));

        // ncsubook by ncsubook->id
        $rules[] = new restore_decode_rule('NCSUBOOKVIEWBYB', '/mod/ncsubook/view.php?b=$1', 'ncsubook');
        $rules[] = new restore_decode_rule('NCSUBOOKVIEWBYBCH', '/mod/ncsubook/view.php?b=$1&amp;chapterid=$2', array('ncsubook', 'ncsubook_chapter'));

        // Convert old ncsubook links MDL-33362 & MDL-35007
        $rules[] = new restore_decode_rule('NCSUBOOKSTART', '/mod/ncsubook/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('NCSUBOOKCHAPTER', '/mod/ncsubook/view.php?id=$1&amp;chapterid=$2', array('course_module', 'ncsubook_chapter'));

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * ncsubook logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * @return array
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('ncsubook', 'add', 'view.php?id={course_module}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'update', 'view.php?id={course_module}&chapterid={ncsubook_chapter}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'update', 'view.php?id={course_module}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'view', 'view.php?id={course_module}&chapterid={ncsubook_chapter}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'view', 'view.php?id={course_module}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'print', 'tool/print/index.php?id={course_module}&chapterid={ncsubook_chapter}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'print', 'tool/print/index.php?id={course_module}', '{ncsubook}');
        $rules[] = new restore_log_rule('ncsubook', 'exportimscp', 'tool/exportimscp/index.php?id={course_module}', '{ncsubook}');
        // To convert old 'generateimscp' log entries
        $rules[] = new restore_log_rule('ncsubook', 'generateimscp', 'tool/generateimscp/index.php?id={course_module}', '{ncsubook}',
                'ncsubook', 'exportimscp', 'tool/exportimscp/index.php?id={course_module}', '{ncsubook}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     *
     * @return array
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('ncsubook', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
