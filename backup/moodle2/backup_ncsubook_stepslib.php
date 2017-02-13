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
 * This file is part of the NC State Book plugin
 *
 * The NC State Book plugin is an extension of mod_book with some additional
 * blocks to aid in organizing and presenting content. This plugin was originally
 * developed for North Carolina State University.
 *
 * @package mod_ncsubook
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to backup one ncsubook activity
 */
class backup_ncsubook_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated
        $ncsubook     = new backup_nested_element('ncsubook', array('id'), array('course', 'name', 'intro', 'introformat', 'numbering', 'revision', 'timecreated', 'timemodified'));
        // $chapters = new backup_nested_element('chapters');
        $chapter  = new backup_nested_element('chapter', array('id'), array('ncsubookid', 'type', 'pagenum', 'subchapter', 'title', 'additionaltitle', 'content', 'contentformat', 'hidden', 'timemcreated', 'timemodified', 'importsrc',));
        $block  = new backup_nested_element('block', array('id'), array('chapterid', 'type', 'title', 'content', 'contentformat', 'blockorder', 'timecreated', 'timemodified',));

        $ncsubook->add_child($chapter);
        $chapter->add_child($block);

        // Define sources
        $ncsubook->set_source_table('ncsubook', array('id' => backup::VAR_ACTIVITYID));
        $chapter->set_source_table('ncsubook_chapters', array('ncsubookid' => backup::VAR_PARENTID));
        $block->set_source_table('ncsubook_blocks', array('chapterid' => backup::VAR_PARENTID));

        // Define file annotations
        $ncsubook->annotate_files('mod_ncsubook', 'intro', null); // This file area hasn't itemid
        $block->annotate_files('mod_ncsubook', 'chapter', 'chapterid');

        // Return the root element (ncsubook), wrapped into standard activity structure
        return $this->prepare_activity_structure($ncsubook);
    }
}
