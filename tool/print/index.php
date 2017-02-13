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
 * Book printing
 *
 * @package    ncsubooktool_print
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

require(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id        = required_param('id', PARAM_INT);           // Course Module ID
$chapterid = optional_param('chapterid', 0, PARAM_INT); // Chapter ID

// =========================================================================
// security checks START - teachers and students view
// =========================================================================

$cm             = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook       = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context        = context_module::instance($cm->id);
// read chapters
$chapters       = ncsubook_preload_chapters($ncsubook);
$strncsubooks   = get_string('modulenameplural', 'mod_ncsubook');
$strncsubook    = get_string('modulename', 'mod_ncsubook');
$strtop         = get_string('top', 'mod_ncsubook');

require_course_login($course, true, $cm);
require_capability('mod/ncsubook:read', $context);
require_capability('ncsubooktool/print:print', $context);

// Check all variables.
if ($chapterid) {
    // Single chapter printing - only visible!
    $chapter = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);
} else {
    // Complete ncsubook.
    $chapter = false;
}

$PAGE->set_url('/mod/ncsubook/print.php', ['id' => $id, 'chapterid' => $chapterid]);

unset($id);
unset($chapterid);

// Security checks END.

@header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
@header('Pragma: no-cache');
@header('Expires: ');
@header('Accept-Ranges: none');
@header('Content-type: text/html; charset=utf-8');

if ($chapter) {
    if ($chapter->hidden) {
        require_capability('mod/ncsubook:viewhiddenchapters', $context);
    }

    \ncsubooktool_print\event\chapter_printed::create_from_chapter($ncsubook, $context, $chapter)->trigger();

    // page header
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
      <title><?=format_string($ncsubook->name, true, ['context' => $context])?></title>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <meta name="description" content="<?=s(format_string($ncsubook->name, true, ['context' => $context]))?>" />
      <link rel="stylesheet" type="text/css" href="../../styles.css">
      <link rel="stylesheet" type="text/css" href="../../styles/print.css">
    </head>
    <body>
    <a name="top"></a>
    <h1 class="ncsubook_title"><?=format_string($ncsubook->name, true, ['context' => $context])?></h1>
    <div class="chapter">
    <?php
    /*
    echo '<link rel="stylesheet" type="text/css" href="../../styles.css">';
    echo '<link rel="stylesheet" type="text/css" href="../../styles/print.css">';
    */
    // chapter itself
    $chaptercsstag  = ncsubook_get_chapter_css_tag($chapter->id);
    $csstagstring   = 'generalbox ncsubook_content '.$chaptercsstag;

    echo $OUTPUT->box_start($csstagstring);

    $hidden     = $chapter->hidden ? 'dimmed_text' : '';
    $currtitle  = ncsubook_get_chapter_title($chapter->id, $chapters, $ncsubook, $context);

    if ($chapter->additionaltitle) {
        echo '<div class="ncsubook_chapter_additional_title1">' . $chapter->additionaltitle . '</div>';
    } else {
        echo '<div class="ncsubook_chapter_booktitle1">' . $ncsubook->name . '</div>';
    }

    echo '<div class="ncsubook_chapter_title_div">';
    if ($chaptercsstag == 'ncsubook_introduction_chapter') {
        echo '<div align="left" class="intro_icon_container">' . file_get_contents('../../pix/introicon.svg') . '</div>';
    } else if ($chaptercsstag == 'ncsubook_learningobjectives_chapter') {
        echo '<div align="left" class="checklist_icon_container">' . file_get_contents('../../pix/checklisticon.svg') . '</div>';
    } else if ($chaptercsstag == 'ncsubook_summary_chapter') {
        echo '<div align="left" class="checklist_icon_container">' . file_get_contents('../../pix/checklisticon.svg') . '</div>';
    }
    echo '<div class="ncsubook_chapter_title">' . $currtitle . '</div>';
    if ($chaptercsstag == 'ncsubook_basic_chapter') {
        echo '<hr class="ncsubook_hr">';
    }
    if ($chapter->additionaltitle) {
        echo '<div align="right" class="ncsubook_chapter_additional_title2">' . $chapter->additionaltitle . '</div>';
    } else {
        echo '<div align="right" class="ncsubook_chapter_booktitle2">' . $ncsubook->name . '</div>';
    }
    echo '</div>';


    // Gary Harris - 4/15/2013
    // Get the list of blocks for this chapter and then loop through them getting the CSS classname
    // and header text for each block. Place the content for each block inside of its own DIV with
    // its classname and header text (if it exists).
    // End GDH
    $blocks             = ncsubook_get_chapter_content($chapter->id);
    $chapter->content   = '<div class="ncsubook-content">';
    $blockarray         = ncsubook_rearrange_content_blocks($blocks);

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

} else {
    \ncsubooktool_print\event\book_printed::create_from_book($ncsubook, $context)->trigger();

    $allchapters        = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum');
    $ncsubook->intro    = file_rewrite_pluginfile_urls($ncsubook->intro, 'pluginfile.php', $context->id, 'mod_ncsubook', 'intro', null);

    // page header
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
      <title><?=format_string($ncsubook->name, true, ['context' => $context])?></title>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <meta name="description" content="<?=s(format_string($ncsubook->name, true, ['noclean' => true, 'context' => $context]))?>" />
      <link rel="stylesheet" type="text/css" href="print.css" />
      <link rel="stylesheet" type="text/css" href="../../styles.css">
      <link rel="stylesheet" type="text/css" href="../../styles/print.css">
    </head>
    <body>
    <a name="top"></a>
    <h1 class="ncsubook_title"><?=format_string($ncsubook->name, true, ['context' => $context])?></h1>
    <p class="ncsubook_summary"><?=format_text($ncsubook->intro, $ncsubook->introformat, ['noclean' => true, 'context' => $context])?></p>
    <div class="ncsubook_info"><table>
    <!-- <tr>
    <td><?=get_string('site')?>:</td>
    <td><a href="<?=$CFG->wwwroot?>"><?=format_string($SITE->fullname, true, ['context' => $context])?></a></td>
    </tr><tr> -->
    <td><?=get_string('course')?>:</td>
    <td><?=format_string($course->fullname, true, ['context' => $context])?></td>
    </tr><tr>
    <td><?=get_string('modulename', 'mod_ncsubook')?>:</td>
    <td><?=format_string($ncsubook->name, true, ['context' => $context])?></td>
    </tr><tr>
    <td><?=get_string('printedby', 'ncsubooktool_print')?>:</td>
    <td><?=fullname($USER, true)?></td>
    </tr><tr>
    <td><?=get_string('printdate', 'ncsubooktool_print')?>:</td>
    <td><?=userdate(time())?></td>
    </tr>
    </table></div>
    <?php
    list($toc, $titles) = ncsubooktool_print_get_toc($chapters, $ncsubook, $cm);
    echo $toc;
    // chapters
    $link1 = $CFG->wwwroot . '/mod/ncsubook/view.php?id=' . $course->id . '&chapterid=';
    $link2 = $CFG->wwwroot . '/mod/ncsubook/view.php?id=' . $course->id;
    /*
    echo '<link rel="stylesheet" type="text/css" href="../../styles.css">';
    echo '<link rel="stylesheet" type="text/css" href="../../styles/print.css">';
    */
    foreach ($chapters as $ch) {
        $chaptercsstag  = ncsubook_get_chapter_css_tag($ch->id);
        $csstagstring   = 'generalbox ncsubook_content ' . $chaptercsstag;
        $chapter        = $allchapters[$ch->id];

        echo $OUTPUT->box_start($csstagstring);
        if ($chapter->hidden) {
            continue;
        }
        if ($chaptercsstag == 'ncsubook_introduction_chapter') {
            echo '<div style="margin-top: 15px;" align="left" class="intro_icon_container">' . file_get_contents('../../pix/introicon.svg') . '</div>';
        } else if ($chaptercsstag == 'ncsubook_learningobjectives_chapter') {
            echo '<div style="margin-top: 15px;" align="left" class="checklist_icon_container">' . file_get_contents('../../pix/checklisticon.svg') . '</div>';
        } else if ($chaptercsstag == 'ncsubook_summary_chapter') {
            echo '<div style="margin-top: 15px;" align="left" class="checklist_icon_container">' . file_get_contents('../../pix/checklisticon.svg') . '</div>';
        }
        echo '<div class="ncsubook_chapter"><a name="ch' . $ch->id . '"></a>';
        if (!$chapter->subchapter) {
            echo '<h2 class="ncsubook_chapter_title">' . $titles[$ch->id] . '</h2>';
        } else {
            echo '<h3 class="ncsubook_chapter_title">' . $titles[$ch->id] . '</h3>';
        }
        echo '</div>';

        $blocks             = ncsubook_get_chapter_content($ch->id);
        $chapter->content   = '<div class="ncsubook-content">';
        $blockarray         = ncsubook_rearrange_content_blocks($blocks);

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

        $content = str_replace($link1, '#ch', $chapter->content);
        $content = str_replace($link2, '#top', $content);
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_ncsubook', 'chapter', $ch->id);
        echo format_text($content, $chapter->contentformat, ['noclean' => true, 'context' => $context]);
        echo '</div>';
        echo $OUTPUT->box_end();
        // echo '<a href="#toc">'.$strtop.'</a>';
    }
    echo '</body> </html>';
}

