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
require_once($CFG->libdir . '/completionlib.php');

$id        = optional_param('id', 0, PARAM_INT);        // Course Module ID
$bid       = optional_param('b', 0, PARAM_INT);         // Book id
$chapterid = optional_param('chapterid', 0, PARAM_INT); // Chapter ID
$edit      = optional_param('edit', -1, PARAM_BOOL);    // Edit mode

// =========================================================================
// security checks START - teachers edit; students view
// =========================================================================
if ($id) {
    $cm         = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $ncsubook   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $ncsubook   = $DB->get_record('ncsubook', ['id' => $bid], '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('ncsubook', $ncsubook->id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $id         = $cm->id;
}
$context        = context_module::instance($cm->id);
$allowedit      = has_capability('mod/ncsubook:edit', $context);
$viewhidden     = has_capability('mod/ncsubook:viewhiddenchapters', $context);

require_course_login($course, true, $cm);
require_capability('mod/ncsubook:read', $context);

if ($allowedit) {
    if ($edit != -1 and confirm_sesskey()) {
        $USER->editing = $edit;
    } else {
        if (isset($USER->editing)) {
            $edit = $USER->editing;
        } else {
            $edit = 0;
        }
    }
} else {
    $edit = 0;
}

$chapters       = ncsubook_preload_chapters($ncsubook);

/* Gary Harris - 4/3/2013
// If we have no chapters, then we redirect them to the page where they can
// create the first chapter for the book.
// End GDH
*/
if ($allowedit and !$chapters) {
    redirect('pre-edit.php?cmid=' . $cm->id); // No chapters - add new one.
}

/* Gary Harris - 4//3/2013
// This section guarantees that we are going to the first chapter ID if no $chapter ID was
// provided in the query string. We loop through the $chapters list, but each internal section
// of the "foreach" loop has a break, so we only ever see the first object element of
// $chapters, and we grab that chapter ID if the user has edit capability or the chapter
// is not hidden.
// End GDH
*/
if ($chapterid == '0') { // Go to first chapter if no given.
    // SMBADER (6/18/2014):  Adding events for NC State Book.
    $params     = [
                    'context' => $context,
                    'objectid' => $ncsubook->id
                  ];
    $event      = \mod_ncsubook\event\course_module_viewed::create($params);

    $event->add_record_snapshot('ncsubook', $ncsubook);
    $event->trigger();

    foreach ($chapters as $ch) {
        if ($edit) {
            $chapterid = $ch->id;
            break;
        }
        if (!$ch->hidden) {
            $chapterid = $ch->id;
            break;
        }
    }
}

/* Gary Harris - 4/3/2013
// We are building the $courseurl because it is the URL that will be used
// if we run into a problem down the road. This URL will take us back to
// the main course page.
// End GDH
*/
$courseurl      = new moodle_url('/course/view.php', ['id' => $course->id]);

/* Gary Harris - 4/3/2013
// If we get to this point and there is no $chapterid to view, then this particular NC State Book
// has no content or all of the chapters were hidden, so we print a notice that the
// book has no content and display a Continue button which goes to $courseurl.
// End GDH
*/
if (!$chapterid) {
    $PAGE->set_url('/mod/ncsubook/view.php', ['id' => $id]);
    notice(get_string('nocontent', 'mod_ncsubook'), $courseurl->out(false));
}

/* Gary Harris - 4/3/2013
// If the chapter number does not exist or if the chapter is hidden, then we display
// an error message (Error reading chapter of book) and a Continue button which goes
// to $courseurl. Note: This error message is ugly and could probably be handled better.
// End GDH
*/
if ((!$chapter = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id])) or ($chapter->hidden and !$viewhidden)) {
    print_error('errorchapter', 'mod_ncsubook', $courseurl);
}

$PAGE->set_url('/mod/ncsubook/view.php', ['id' => $id, 'chapterid' => $chapterid]);


// Unset all page parameters.
unset($id);
unset($bid);
unset($chapterid);

// Security checks END.

// SMBADER (6/18/2014):  Adding events for NC State Book.
$params = [
            'context' => $context,
            'objectid' => $chapter->id
          ];
$event = \mod_ncsubook\event\chapter_viewed::create($params);
$event->add_record_snapshot('ncsubook_chapters', $chapter);
$event->trigger();

// Read standard strings.
$strncsubooks   = get_string('modulenameplural', 'mod_ncsubook');
$strncsubook    = get_string('modulename', 'mod_ncsubook');
$strtoc         = get_string('toc', 'mod_ncsubook');

// Prepare header.
$pagetitle      = $ncsubook->name . ": " . $chapter->title;
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

/* Gary Harris - 4/3/2013
// Here is where we build a fake block to place in the sidebar. The block contains a table of
// contents (toc), and the toc is a list of the chapters in the book. We send the function
// the complete list of $chapters so that they can be displayed, and we send it the current
// $chapter so that chapter name can be highlighted in the chapter list as the current chapter
// being viewed. By "highlighted," I mean to say not a link. See the interface for clarification.
// End GDH
*/
ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

/* Gary Harris - 4/3/2013
// Whenever you are viewing a chapter, there are previous and next arrows on the page that allow
// you to navigate to the previous and next chapters. The following loop gets the chapter IDs for
// those arrows and they are built into the icon URLs below.
// End GDH
*/
$previd     = null;
$nextid     = null;
$last       = null;
foreach ($chapters as $ch) {
    if (!$edit and $ch->hidden) {
        continue;
    }
    if ($last == $chapter->id) {
        $nextid = $ch->id;
        break;
    }
    if ($ch->id != $chapter->id) {
        $previd = $ch->id;
    }
    $last = $ch->id;
}

$chnavigation = '';
if ($previd) {
    $chnavigation   .= '<a title="' . get_string('navprev', 'ncsubook') . '" href="view.php?id=' . $cm->id
                    . '&amp;chapterid=' . $previd . '"><img src="' . $OUTPUT->pix_url('nav_prev', 'mod_ncsubook')
                    . '" class="bigicon" alt="' . get_string('navprev', 'ncsubook').'"/></a>';
} else {
    $chnavigation   .= '<img src="' . $OUTPUT->pix_url('nav_prev_dis', 'mod_ncsubook') . '" class="bigicon" alt="" />';
}
if ($nextid) {
    $chnavigation   .= '<a title="' . get_string('navnext', 'ncsubook') . '" href="view.php?id=' . $cm->id
                    . '&amp;chapterid=' . $nextid . '"><img src="' . $OUTPUT->pix_url('nav_next', 'mod_ncsubook')
                    . '" class="bigicon" alt="' . get_string('navnext', 'ncsubook') . '" /></a>';
} else {
    $sec = '';
    if ($section = $DB->get_record('course_sections', ['id' => $cm->section])) {
        $sec = $section->section;
    }
    if ($course->id == $SITE->id) {
        $returnurl = $CFG->wwwroot . '/';
    } else {
        $returnurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id . '#section-' . $sec;
    }
    // If this is the last chapter, then put the book exit icon with a link back to the main course page.
    $chnavigation   .= '<a title="' . get_string('navexit', 'ncsubook') . '" href="' . $returnurl . '"><img src="'
                    . $OUTPUT->pix_url('nav_exit', 'mod_ncsubook') . '" class="bigicon" alt="'
                    . get_string('navexit', 'ncsubook').'" /></a>';

    // We are cheating a bit here, viewing the last page means user has viewed the whole ncsubook.
    $completion     = new completion_info($course);
    $completion->set_module_viewed($cm);
}

// =====================================================
// Book display HTML code.
// =====================================================

echo $OUTPUT->header();

// Gary Harris - 4/3/2013.
// We put the prev/next arrows at the top and the bottom of the page to make navigation easier.
echo '<div class="navtop">' . $chnavigation . '</div>';

// Chapter itself.
$chaptercsstag      = ncsubook_get_chapter_css_tag($chapter->id);
$csstagstring       = 'generalbox ncsubook_content ' . $chaptercsstag;

echo $OUTPUT->box_start($csstagstring);

$hidden             = $chapter->hidden ? 'dimmed_text' : '';
$currtitle          = ncsubook_get_chapter_title($chapter->id, $chapters, $ncsubook, $context);
if ($chapter->additionaltitle) {
    echo '<div class="ncsubook_chapter_additional_title1">' . $chapter->additionaltitle . '</div>';
} else {
    echo '<div class="ncsubook_chapter_booktitle1">' . $ncsubook->name . '</div>';
}

$parentchaptertitle = '';
if ($chapter->subchapter && $chapter->showparenttitle) {
    $parentchaptertitle = ncsubook_get_parent_chapter_title($ncsubook->id, $chapter->pagenum);
}

echo '<div class="ncsubook_chapter_title_div">';
if ($chaptercsstag == 'ncsubook_introduction_chapter') {
    echo '<div align="left" class="intro_icon_container">' . file_get_contents('pix/introicon.svg') . '</div>';
} else if ($chaptercsstag == 'ncsubook_learningobjectives_chapter') {
    echo '<div align="left" class="checklist_icon_container">' . file_get_contents('pix/checklisticon.svg') . '</div>';
} else if ($chaptercsstag == 'ncsubook_summary_chapter') {
    echo '<div align="left" class="checklist_icon_container">' . file_get_contents('pix/checklisticon.svg') . '</div>';
}
if (empty($parentchaptertitle)) {
    echo '<div class="ncsubook_chapter_title">' . $currtitle . '</div>';
} else {
    echo '<div class="ncsubook_chapter_title">' . $parentchaptertitle . '<div class="ncsubook_subchapter_title">' . $currtitle . '</div></div>';
}
if ($chaptercsstag == 'ncsubook_basic_chapter') {
    echo '<hr class="ncsubook_hr">';
}
if ($chapter->additionaltitle) {
    echo '<div align="right" class="ncsubook_chapter_additional_title2">' . $chapter->additionaltitle . '</div>';
} else {
    echo '<div align="right" class="ncsubook_chapter_booktitle2">' . $ncsubook->name . '</div>';
}
echo '</div>';


/* Gary Harris - 4/15/2013
// Get the list of blocks for this chapter and then loop through them getting the CSS classname
// and header text for each block. Place the content for each block inside of its own DIV with
// its classname and header text (if it exists).
// End GDH
*/
$blocks             = ncsubook_get_chapter_content($chapter->id);
$chapter->content   = '<div class="ncsubook-content">';

if (core_useragent::get_device_type() == 'mobile') {
    $blockarray = $blocks;
} else {
    $blockarray = ncsubook_rearrange_content_blocks($blocks);
}
foreach ($blockarray as $block) {
    $blockclass     = ncsubook_get_block_class($block->type);
    $blockheader    = ncsubook_get_block_header($block->id);
    if ($blockclass == 'ncsubook_content_block') {
        $chapter->content .= '<div class="' . $blockclass . '">' . $block->content . '</div>';
    } else {
        if (strlen($blockheader)) {
            $chapter->content .= '<div class="' . $blockclass . '"><div class="block_title">' . $blockheader
                               . '</div><div class="block_content">' . $block->content . '</div></div>';
        } else {
            $chapter->content .= '<div class="' . $blockclass . '"><div class="block_content">' . $block->content . '</div></div>';
        }
    }
}
$chapter->content .= '</div>';

$chaptertext = file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $context->id, 'mod_ncsubook', 'chapter', $chapter->id);
echo format_text($chaptertext, $chapter->contentformat, ['noclean' => true, 'context' => $context]);

echo $OUTPUT->box_end();

// Gary Harris - 4/3/2013.
// We put the prev/next arrows at the top and the bottom of the page to make navigation easier.
echo '<div class="navbottom">' . $chnavigation . '</div>';

echo $OUTPUT->footer();
