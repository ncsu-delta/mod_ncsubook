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
 * Copy ncsubook chapter
 *
 * @package    mod_ncsubook
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');

$cmid                                       = required_param('cmid', PARAM_INT);        // Book Course Module ID.
$chapterid                                  = required_param('chapterid', PARAM_INT);   // Chapter ID.

$cm                                         = get_coursemodule_from_id('ncsubook', $cmid, 0, false, MUST_EXIST);
$course                                     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook                                   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$chapter                                    = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);
$ncsubookblocks                            = $DB->get_records_select('ncsubook_blocks', "chapterid = " . $chapterid);

// Setup the new chapter object we are copying..
$newchapter                                 = new StdClass;
$newchapter->ncsubookid                     = $chapter->ncsubookid;
$newchapter->type                           = $chapter->type;
$newchapter->pagenum                        = $chapter->pagenum + 1;
$newchapter->subchapter                     = $chapter->subchapter;
$newchapter->title                          = $chapter->title;
$newchapter->additionaltitle                = $chapter->additionaltitle;
$newchapter->content                        = $chapter->content;
$newchapter->contentformat                  = $chapter->contentformat;
$newchapter->hidden                         = $chapter->hidden;
$newchapter->importsrc                      = $chapter->importsrc;
$newchapter->showparenttitle                = $chapter->showparenttitle;
$newchapter->timecreated                    = time();
$newchapter->timemodified                   = time();

// Attempt to insert the record.
$newchapter->id                             = $DB->insert_record('ncsubook_chapters', $newchapter);

// Now copy the blocks...
foreach ($ncsubookblocks as $block) {
    $newblock                               = new StdClass();
    $newblock->chapterid                    = $newchapter->id;
    $newblock->type                         = $block->type;
    $newblock->title                        = $block->title;
    $newblock->content                      = $block->content;
    $newblock->contentformat                = $block->contentformat;
    $newblock->blockorder                   = $block->blockorder;
    $newblock->timecreated                  = time();
    $newblock->timemodified                 = time();

    $newblocks[]                            = $newblock;
    unset($newblock);
}

// Attempt to insert the new records.
$lastinsertid                               = $DB->insert_records('ncsubook_blocks', $newblocks);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/ncsubook:edit', $context);

// Bring up the newly copied chapter.
$url                                        = $CFG->wwwroot . '/mod/ncsubook/edit.php?cmid=' . $cmid . '&chapterid=' . $newchapter->id;
header("Location: " . $url);
return;
