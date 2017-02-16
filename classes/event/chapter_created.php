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

namespace mod_ncsubook\event;
defined('MOODLE_INTERNAL') || die();

/**
 * mod_book chapter created event class.
 *
 * @package    mod_ncsubook
 * @since      Moodle 2.6
 * @copyright  2014 Steve Bader
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chapter_created extends \core\event\base {

    public static function create_from_chapter(\stdClass $ncsubook, \context_module $context, \stdClass $chapter) {
        $data = [
                  'context'   => $context,
                  'objectid'  => $chapter->id,
                ];
        /** @var chapter_created $event */
        $event = self::create($data);
        $event->add_record_snapshot('ncsubook', $ncsubook);
        $event->add_record_snapshot('ncsubook_chapters', $chapter);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The chapter {$this->objectid} of the NC State Book {$this->contextinstanceid} has been created.";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return [$this->courseid,
                'ncsubook',
                'add chapter',
                'view.php?id=' . $this->contextinstanceid . '&chapterid=' . $this->objectid,
                $this->objectid,
                $this->contextinstanceid
               ];
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_chapter_created', 'mod_ncsubook');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/ncsubook/view.php', ['id' => $this->contextinstanceid, 'chapterid' => $this->objectid]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud']         = 'c';
        $this->data['edulevel']     = self::LEVEL_TEACHING;
        $this->data['objecttable']  = 'ncsubook_chapters';
    }
    
    public static function get_objectid_mapping() {
        return array('db' => 'ncsubook_chapters', 'restore' => 'ncsubook_chapter');
    }

}
