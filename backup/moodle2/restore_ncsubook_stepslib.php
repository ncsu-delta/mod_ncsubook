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
 * Define all the restore steps that will be used by the restore_ncsubook_activity_task
 *
 * @package    mod_ncsubook
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to restore one ncsubook activity
 */
class restore_ncsubook_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('ncsubook', '/activity/ncsubook');
        $paths[] = new restore_path_element('ncsubook_chapter', '/activity/ncsubook/chapter');
        $paths[] = new restore_path_element('ncsubook_block', '/activity/ncsubook/chapter/block');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process ncsubook tag information
     * @param array $data information
     */
    protected function process_ncsubook($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('ncsubook', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process chapter tag information
     * @param array $data information
     */
    protected function process_ncsubook_chapter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->ncsubookid = $this->get_new_parentid('ncsubook');

        $newitemid = $DB->insert_record('ncsubook_chapters', $data);
        $this->set_mapping('ncsubook_chapter', $oldid, $newitemid, true);
    }

    /**
     * Process block tag information
     * @param array $data information
     */
    protected function process_ncsubook_block($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->chapterid = $this->get_new_parentid('ncsubook_chapter');

        $newitemid = $DB->insert_record('ncsubook_blocks', $data);
        $this->set_mapping('ncsubook_block', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        global $DB;

        // Add ncsubook related files
        $this->add_related_files('mod_ncsubook', 'intro', null);
        $this->add_related_files('mod_ncsubook', 'chapter', 'ncsubook_chapter');
    }
}
