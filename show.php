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
 * Show/hide ncsubook chapter
 *
 * @package    mod_ncsubook
 * @copyright  2004-2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id        = required_param('id', PARAM_INT);        // Course Module ID
$chapterid = required_param('chapterid', PARAM_INT); // Chapter ID

$cm = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$ncsubook = $DB->get_record('ncsubook', array('id'=>$cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();

$context = context_module::instance($cm->id);
require_capability('mod/ncsubook:edit', $context);

$PAGE->set_url('/mod/ncsubook/show.php', array('id'=>$id, 'chapterid'=>$chapterid));

$chapter = $DB->get_record('ncsubook_chapters', array('id'=>$chapterid, 'ncsubookid'=>$ncsubook->id), '*', MUST_EXIST);

// Switch hidden state.
$chapter->hidden = $chapter->hidden ? 0 : 1;

// Update record.
$DB->update_record('ncsubook_chapters', $chapter);
$params = array(
    'context' => $context,
    'objectid' => $chapter->id
);
$event = \mod_ncsubook\event\chapter_updated::create($params);
$event->add_record_snapshot('ncsubook_chapters', $chapter);
$event->trigger();



// Change visibility of subchapters too.
if (!$chapter->subchapter) {
    $chapters = $DB->get_records('ncsubook_chapters', array('ncsubookid'=>$ncsubook->id), 'pagenum', 'id, subchapter, hidden');
    $found = 0;
    foreach ($chapters as $ch) {
        if ($ch->id == $chapter->id) {
            $found = 1;
        } else if ($found and $ch->subchapter) {
            $ch->hidden = $chapter->hidden;
            $DB->update_record('ncsubook_chapters', $ch);

            $params = array(
                'context' => $context,
                'objectid' => $ch->id
            );
            $event = \mod_ncsubook\event\chapter_updated::create($params);
            $event->trigger();
        } else if ($found) {
            break;
        }
    }
}

ncsubook_preload_chapters($ncsubook); // fix structure
$DB->set_field('ncsubook', 'revision', $ncsubook->revision+1, array('id'=>$ncsubook->id));

redirect('view.php?id='.$cm->id.'&chapterid='.$chapter->id);

