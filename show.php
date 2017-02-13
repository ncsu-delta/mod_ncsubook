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

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id         = required_param('id', PARAM_INT);        // Course Module ID
$chapterid  = required_param('chapterid', PARAM_INT); // Chapter ID

$cm         = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context    = context_module::instance($cm->id);
$chapter    = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);
require_login($course, false, $cm);
require_sesskey();
require_capability('mod/ncsubook:edit', $context);

$PAGE->set_url('/mod/ncsubook/show.php', ['id' => $id, 'chapterid' => $chapterid]);

// Switch hidden state.
$chapter->hidden = $chapter->hidden ? 0 : 1;

// Update record.
$DB->update_record('ncsubook_chapters', $chapter);
$params     = [
                'context' => $context,
                'objectid' => $chapter->id
              ];

$event      = \mod_ncsubook\event\chapter_updated::create($params);

$event->add_record_snapshot('ncsubook_chapters', $chapter);
$event->trigger();



// Change visibility of subchapters too.
if (!$chapter->subchapter) {
    $chapters   = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum', 'id, subchapter, hidden');
    $found      = false;
    foreach ($chapters as $ch) {
        if ($ch->id == $chapter->id) {
            $found = true;
        } else if ($found and $ch->subchapter) {
            $ch->hidden = $chapter->hidden;
            $DB->update_record('ncsubook_chapters', $ch);

            $params = [
                        'context' => $context,
                        'objectid' => $ch->id
                      ];
            $event = \mod_ncsubook\event\chapter_updated::create($params);
            $event->trigger();
        } else if ($found) {
            break;
        }
    }
}

ncsubook_preload_chapters($ncsubook); // fix structure
$DB->set_field('ncsubook', 'revision', $ncsubook->revision + 1, ['id' => $ncsubook->id]);

redirect('view.php?id=' . $cm->id. '&chapterid=' . $chapter->id);

