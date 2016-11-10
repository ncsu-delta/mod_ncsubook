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
 * Move ncsubook chapter
 *
 * @package    mod_ncsubook
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id            = required_param('id', PARAM_INT);        // Course Module ID
$origblockid   = required_param('blockid', PARAM_INT); // Block ID
$chapterid     = required_param('chapterid', PARAM_INT);
$up            = optional_param('up', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$ncsubook = $DB->get_record('ncsubook', array('id'=>$cm->instance), '*', MUST_EXIST);
$chapter = $DB->get_record('ncsubook_chapters', array('id'=>$chapterid), '*', MUST_EXIST);

require_login($course, false, $cm);
// require_sesskey();

$context = context_module::instance($cm->id);
require_capability('mod/ncsubook:edit', $context);

if ($up == 0) {
    $current_origblock_pos = $DB->get_record('ncsubook_blocks', array('id'=>$origblockid), 'blockorder', MUST_EXIST);
    $next_origblock_pos = $current_origblock_pos->blockorder + 1;

    $replaceblockid = $DB->get_record('ncsubook_blocks', array('blockorder' => $next_origblock_pos, 'chapterid' => $chapterid), 'id', MUST_EXIST);
    $next_replaceblock_pos = $current_origblock_pos->blockorder;

    $DB->update_record('ncsubook_blocks', array('id'=>$origblockid, 'blockorder'=>$next_origblock_pos));
    $DB->update_record('ncsubook_blocks', array('id'=>$replaceblockid->id, 'blockorder'=>$next_replaceblock_pos));
} elseif ($up == 1) {
    $current_origblock_pos = $DB->get_record('ncsubook_blocks', array('id'=>$origblockid), 'blockorder', MUST_EXIST);
    $next_origblock_pos = $current_origblock_pos->blockorder - 1;

    $replaceblockid = $DB->get_record('ncsubook_blocks', array('blockorder' => $next_origblock_pos, 'chapterid' => $chapterid), 'id', MUST_EXIST);
    $next_replaceblock_pos = $current_origblock_pos->blockorder;

    $DB->update_record('ncsubook_blocks', array('id'=>$origblockid, 'blockorder'=>$next_origblock_pos));
    $DB->update_record('ncsubook_blocks', array('id'=>$replaceblockid->id, 'blockorder'=>$next_replaceblock_pos));
}





/* $chapter = $DB->get_record('ncsubook_chapters', array('id'=>$chapterid, 'ncsubookid'=>$ncsubook->id), '*', MUST_EXIST);
$oldchapters = $DB->get_records('ncsubook_chapters', array('ncsubookid'=>$ncsubook->id), 'pagenum', 'id, pagenum, subchapter');

$nothing = 0;

$chapters = array();
$chs = 0;
$che = 0;
$ts = 0;
$te = 0;
// create new ordered array and find chapters to be moved
$i = 1;
$found = 0;
foreach ($oldchapters as $ch) {
    $chapters[$i] = $ch;
    if ($chapter->id == $ch->id) {
        $chs = $i;
        $che = $chs;
        if ($ch->subchapter) {
            $found = 1;// Subchapter moves alone.
        }
    } else if ($chs) {
        if ($found) {
            // Nothing.
        } else if ($ch->subchapter) {
            $che = $i; // Chapter with subchapter(s).
        } else {
            $found = 1;
        }
    }
    $i++;
}

// Find target chapter(s).
if ($chapters[$chs]->subchapter) { // Moving single subchapter up or down.
    if ($up) {
        if ($chs == 1) {
            $nothing = 1; // Already first.
        } else {
            $ts = $chs - 1;
            $te = $ts;
        }
    } else { // Down.
        if ($che == count($chapters)) {
            $nothing = 1; // Already last.
        } else {
            $ts = $che + 1;
            $te = $ts;
        }
    }
} else { // Moving chapter and looking for next/previous chapter.
    if ($up) { // Up.
        if ($chs == 1) {
            $nothing = 1; // Already first.
        } else {
            $te = $chs - 1;
            for ($i = $chs-1; $i >= 1; $i--) {
                if ($chapters[$i]->subchapter) {
                    $ts = $i;
                } else {
                    $ts = $i;
                    break;
                }
            }
        }
    } else { // Down.
        if ($che == count($chapters)) {
            $nothing = 1; // Already last.
        } else {
            $ts = $che + 1;
            $found = 0;
            for ($i = $che+1; $i <= count($chapters); $i++) {
                if ($chapters[$i]->subchapter) {
                    $te = $i;
                } else {
                    if ($found) {
                        break;
                    } else {
                        $te = $i;
                        $found = 1;
                    }
                }
            }
        }
    }
}

// Recreated newly sorted list of chapters.
if (!$nothing) {
    $newchapters = array();

    if ($up) {
        if ($ts > 1) {
            for ($i=1; $i<$ts; $i++) {
                $newchapters[] = $chapters[$i];
            }
        }
        for ($i=$chs; $i<=$che; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        for ($i=$ts; $i<=$te; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        if ($che<count($chapters)) {
            for ($i=$che; $i<=count($chapters); $i++) {
                $newchapters[$i] = $chapters[$i];
            }
        }
    } else {
        if ($chs > 1) {
            for ($i=1; $i<$chs; $i++) {
                $newchapters[] = $chapters[$i];
            }
        }
        for ($i=$ts; $i<=$te; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        for ($i=$chs; $i<=$che; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        if ($te<count($chapters)) {
            for ($i=$te; $i<=count($chapters); $i++) {
                $newchapters[$i] = $chapters[$i];
            }
        }
    }

    // Store chapters in the new order.
    $i = 1;
    foreach ($newchapters as $ch) {
        $ch->pagenum = $i;
        $DB->update_record('ncsubook_chapters', $ch);
        $i++;
    }
} */

// ncsubook_preload_chapters($ncsubook); // fix structure
// $DB->set_field('ncsubook', 'revision', $ncsubook->revision+1, array('id'=>$ncsubook->id));

redirect('edit.php?cmid='.$cm->id.'&chapterid='.$chapter->id);

