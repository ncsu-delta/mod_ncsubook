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

$id             = required_param('id', PARAM_INT);        // Course Module ID
$chapterid      = required_param('chapterid', PARAM_INT); // Chapter ID
$up             = optional_param('up', 0, PARAM_BOOL);
$cm             = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook       = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context        = context_module::instance($cm->id);
$chapter        = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);
$oldchapters    = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum', 'id, pagenum, subchapter');

require_login($course, false, $cm);
require_sesskey();
require_capability('mod/ncsubook:edit', $context);

$nothing        = false;
$chapters       = [];
$chs            = 0;
$che            = 0;
$ts             = 0;
$te             = 0;
$i              = 1;
$found          = false;

// Create new ordered array and find chapters to be moved.
foreach ($oldchapters as $ch) {
    $chapters[$i] = $ch;
    if ($chapter->id == $ch->id) {
        $chs  = $i;
        $che  = $chs;
        if ($ch->subchapter) {
            $found = true;  // Subchapter moves alone.
        }
    } else if ($chs) {
        if ($ch->subchapter) {
            $che = $i;      // Chapter with subchapter(s).
        } else {
            $found = true;
        }
    }
    $i++;
}

// Find target chapter(s).
$chaptercount = count($chapters);
if ($chapters[$chs]->subchapter) { // Moving single subchapter up or down.
    if ($up) {
        if ($chs == 1) {
            $nothing = true; // Already first.
        } else {
            $ts = $chs - 1;
            $te = $ts;
        }
    } else { // Down.
        if ($che == $chaptercount) {
            $nothing = true; // Already last.
        } else {
            $ts = $che + 1;
            $te = $ts;
        }
    }
} else { // Moving chapter and looking for next/previous chapter.
    if ($up) {
        if ($chs == 1) {
            $nothing = true; // Already first.
        } else {
            $te = $chs - 1;
            for ($i = $chs - 1; $i >= 1; $i--) {
                if ($chapters[$i]->subchapter) {
                    $ts = $i;
                } else {
                    $ts = $i;
                    break;
                }
            }
        }
    } else { // Down.
        if ($che == $chaptercount) {
            $nothing = true; // Already last.
        } else {
            $ts             = $che + 1;
            $found          = false;

            for ($i = $che + 1; $i <= $chaptercount; $i++) {
                if ($chapters[$i]->subchapter) {
                    $te = $i;
                } else {
                    if ($found) {
                        break;
                    } else {
                        $te = $i;
                        $found = true;
                    }
                }
            }
        }
    }
}

// Recreated newly sorted list of chapters.
if (!$nothing) {
    $newchapters = [];

    if ($up) {
        if ($ts > 1) {
            for ($i = 1; $i < $ts; $i++) {
                $newchapters[] = $chapters[$i];
            }
        }
        for ($i = $chs; $i <= $che; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        for ($i = $ts; $i <= $te; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        if ($che < $chaptercount) {
            $chaptercount = count($chapters);
            for ($i = $che; $i <= $chaptercount; $i++) {
                $newchapters[$i] = $chapters[$i];
            }
        }
    } else {
        if ($chs > 1) {
            for ($i = 1; $i < $chs; $i++) {
                $newchapters[] = $chapters[$i];
            }
        }
        for ($i = $ts; $i <= $te; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        for ($i = $chs; $i <= $che; $i++) {
            $newchapters[$i] = $chapters[$i];
        }
        if ($te < $chaptercount) {
            for ($i = $te; $i <= $chaptercount; $i++) {
                $newchapters[$i] = $chapters[$i];
            }
        }
    }

    // Store chapters in the new order.
    $i = 1;
    foreach ($newchapters as $ch) {
        $ch->pagenum = $i;
        $DB->update_record('ncsubook_chapters', $ch);

        $params = [
            'context'   => $context,
            'objectid'  => $ch->id
        ];
        $event = \mod_ncsubook\event\chapter_updated::create($params);
        $event->trigger();
        $i++;
    }
}

ncsubook_preload_chapters($ncsubook); // fix structure
$DB->set_field('ncsubook', 'revision', $ncsubook->revision + 1, ['id' => $ncsubook->id]);

redirect('view.php?id=' . $cm->id . '&chapterid=' . $chapter->id);

