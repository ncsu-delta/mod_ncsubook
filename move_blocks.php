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

require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id             = required_param('id', PARAM_INT);       // Course Module ID
$origblockid    = required_param('blockid', PARAM_INT);  // Block ID
$chapterid      = required_param('chapterid', PARAM_INT);
$up             = optional_param('up', 0, PARAM_BOOL);
$cm             = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook       = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$chapter        = $DB->get_record('ncsubook_chapters', ['id' => $chapterid], '*', MUST_EXIST);
$context        = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/ncsubook:edit', $context);

if ($up == 0) {
    $currentorigblockpos    = $DB->get_record('ncsubook_blocks', ['id' => $origblockid], 'blockorder', MUST_EXIST);
    $nextorigblockpos       = $currentorigblockpos->blockorder + 1;
    $replaceblockid         = $DB->get_record('ncsubook_blocks', ['blockorder' => $nextorigblockpos, 'chapterid' => $chapterid], 'id', MUST_EXIST);
    $nextreplaceblockpos    = $currentorigblockpos->blockorder;

    $DB->update_record('ncsubook_blocks', ['id' => $origblockid, 'blockorder' => $nextorigblockpos]);
    $DB->update_record('ncsubook_blocks', ['id' => $replaceblockid->id, 'blockorder' => $nextreplaceblockpos]);
} else if ($up == 1) {
    $currentorigblockpos    = $DB->get_record('ncsubook_blocks', ['id' => $origblockid], 'blockorder', MUST_EXIST);
    $nextorigblockpos       = $currentorigblockpos->blockorder - 1;
    $replaceblockid         = $DB->get_record('ncsubook_blocks', ['blockorder' => $nextorigblockpos, 'chapterid' => $chapterid], 'id', MUST_EXIST);
    $nextreplaceblockpos    = $currentorigblockpos->blockorder;

    $DB->update_record('ncsubook_blocks', ['id' => $origblockid, 'blockorder' => $nextorigblockpos]);
    $DB->update_record('ncsubook_blocks', ['id' => $replaceblockid->id, 'blockorder' => $nextreplaceblockpos]);
}

redirect('edit.php?cmid='.$cm->id.'&chapterid='.$chapter->id);

