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
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/filelib.php');

/**
 * The following defines are used to define how the chapters and subchapters of a ncsubook should be displayed in that table of contents.
 * NCSU_BOOK_NUM_NONE        No special styling will applied and the editor will be able to do what ever thay want in the title
 * NCSU_BOOK_NUM_NUMBERS     Chapters and subchapters are numbered (1, 1.1, 1.2, 2, ...)
 * NCSU_BOOK_NUM_BULLETS     Subchapters are indented and displayed with bullets
 * NCSU_BOOK_NUM_INDENTED    Subchapters are indented
 */
define('NCSU_BOOK_NUM_NONE',     '0');
define('NCSU_BOOK_NUM_NUMBERS',  '1');
define('NCSU_BOOK_NUM_BULLETS',  '2');
define('NCSU_BOOK_NUM_INDENTED', '3');

define('NCSU_BOOK_CHAPTER_TYPE_INTRODUCTION',           '0');
define('NCSU_BOOK_CHAPTER_TYPE_LEARNING_OBJECTIVES',    '1');
define('NCSU_BOOK_CHAPTER_TYPE_LEARNING_BASIC',         '2');
define('NCSU_BOOK_CHAPTER_TYPE_LEARNING_ASSIGNMENT',    '3');
define('NCSU_BOOK_CHAPTER_TYPE_LEARNING_SUMMARY',       '4');

/**
 * Preload ncsubook chapters and fix toc structure if necessary.
 *
 * Returns array of chapters with standard 'pagenum', 'id, pagenum, subchapter, title, hidden'
 * and extra 'parent, number, subchapters, prev, next'.
 * Please note the content/text of chapters is not included.
 *
 * @param  stdClass $ncsubook
 * @return array of id=>chapter
 */
function ncsubook_preload_chapters($ncsubook) {
    global $DB;
    $fields                                 = 'id, pagenum, subchapter, title, hidden';
    $chapters                               = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum', $fields);
    if (!$chapters) {
        return array();
    }

    $prev                                   = null;
    $prevsub                                = null;
    $first                                  = true;
    $hidesub                                = true;
    $parent                                 = null;
    $pagenum                                = 0;        // Chapter sort.
    $mainchapternum                         = 0;        // Main chapter num.
    $subchapternum                          = 0;        // Subchapter num.

    foreach ($chapters as $id => $ch) {
        $oldch                              = clone($ch);
        $pagenum++;
        $ch->pagenum                        = $pagenum;
        if ($first) {
            // The ncsubook can not start with a subchapter.
            $ch->subchapter                 = 0;
            $first                          = false;
        }
        if (!$ch->subchapter) {
            if ($ch->hidden) {
                if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                    $ch->number             = 'x';
                } else {
                    $ch->number             = null;
                }
            } else {
                $mainchapternum++;
                $ch->number                 = $mainchapternum;
            }
            // Redundant as previously set. $subchapternum = 0;.
            // Redundate as previously set. $prevsub = null;.
            $hidesub                        = $ch->hidden;
            $parent                         = $ch->id;
            $ch->parent                     = null;
            $ch->subchapters                = array();
        } else {
            $ch->parent                                 = $parent;
            $ch->subchapters                            = null;
            $chapters[$parent]->subchapters[$ch->id]    = $ch->id;
            if ($hidesub) {
                // all subchapters in hidden chapter must be hidden too
                $ch->hidden                 = 1;
            }
            if ($ch->hidden) {
                if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                    $ch->number             = 'x';
                } else {
                    $ch->number             = null;
                }
            } else {
                $subchapternum++;
                $ch->number                 = $subchapternum;
            }
        }

        if ($oldch->subchapter != $ch->subchapter or $oldch->pagenum != $ch->pagenum or $oldch->hidden != $ch->hidden) {
            // Update only if something changed.
            $DB->update_record('ncsubook_chapters', $ch);
        }
        $chapters[$id]                      = $ch;
    }

    return $chapters;
}

/**
 * Returns the title for a given chapter
 *
 * @param int $chid
 * @param array $chapters
 * @param stdClass $ncsubook
 * @param context_module $context
 * @return string
 */
function ncsubook_get_chapter_title($chid, $chapters, $ncsubook, $context) {
    $ch                                     = $chapters[$chid];
    $title                                  = trim(format_string($ch->title, true, ['context' => $context]));
    $numbers                                = array();

    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
        if ($ch->parent and $chapters[$ch->parent]->number) {
            $numbers[]                      = $chapters[$ch->parent]->number;
        }
        if ($ch->number) {
            $numbers[]                      = $ch->number;
        }
    }

    if ($numbers) {
        $title = implode('.', $numbers) . ' ' . $title;
    }

    return $title;
}

/**
 * Add the ncsubook TOC sticky block to the 1st region available
 *
 * @param array $chapters
 * @param stdClass $chapter
 * @param stdClass $ncsubook
 * @param stdClass $cm
 * @param bool $edit
 */
function ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit) {
    global $OUTPUT, $PAGE;

    /* Gary Harris - 4/3/2013
    // The following function actually builds the HTML content for the table of contents
    // block that appears in the sidebar and puts it in $toc which will become the content
    // property of the $bc (block content) object below.
    // End GDH
    */
    $toc                                    = ncsubook_get_toc($chapters, $chapter, $ncsubook, $cm, $edit, 0);
    $bc                                     = new block_contents();
    $bc->title                              = get_string('toc', 'mod_ncsubook');
    $bc->attributes['class']                = 'block';
    $bc->content                            = $toc;

    /* Gary Harris - 4/3/2013
    // Get the first available region to place the sticky block built above and add
    // our new block to that region.
    // End GDH
    */
    $regions                                = $PAGE->blocks->get_regions();
    $firstregion                            = reset($regions);
    $PAGE->blocks->add_fake_block($bc, $firstregion);
}

/**
 * Generate toc structure
 *
 * @param array $chapters
 * @param stdClass $chapter
 * @param stdClass $ncsubook
 * @param stdClass $cm
 * @param bool $edit
 * @return string
 */
function ncsubook_get_toc($chapters, $chapter, $ncsubook, $cm, $edit) {
    global $USER, $OUTPUT;

    $toc                                    = '';
    $nch                                    = 0;   // Chapter number.
    $ns                                     = 0;   // Subchapter number.
    $first                                  = 1;

    $context = context_module::instance($cm->id);

    switch ($ncsubook->numbering) {
        case NCSU_BOOK_NUM_NONE:
            $toc                            .= html_writer::start_tag('div', array('class' => 'ncsubook_toc_none'));
        break;
        case NCSU_BOOK_NUM_NUMBERS:
            $toc                            .= html_writer::start_tag('div', array('class' => 'ncsubook_toc_numbered'));
        break;
        case NCSU_BOOK_NUM_BULLETS:
            $toc                            .= html_writer::start_tag('div', array('class' => 'ncsubook_toc_bullets'));
        break;
        case NCSU_BOOK_NUM_INDENTED:
            $toc                            .= html_writer::start_tag('div', array('class' => 'ncsubook_toc_indented'));
        break;
    }

    if ($edit) { // Teacher's TOC.
        $toc                                .= html_writer::start_tag('ul');
        $i                                  = 0;
        foreach ($chapters as $ch) {
            $i++;
            $title                          = trim(format_string($ch->title, true, ['context' => $context]));
            if (!$ch->subchapter) {

                if ($first) {
                    $toc                    .= html_writer::start_tag('li');
                } else {
                    $toc                    .= html_writer::end_tag('ul');
                    $toc                    .= html_writer::end_tag('li');
                    $toc                    .= html_writer::start_tag('li');
                }

                if (!$ch->hidden) {
                    $nch++;
                    $ns                     = 0;
                    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                        $title              = "$nch $title";
                    }
                } else {
                    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                        $title              = "x $title";
                    }
                    $title                  = html_writer::tag('span', $title, ['class' => 'dimmed_text']);
                }
            } else {

                if ($first) {
                    $toc                    .= html_writer::start_tag('li');
                    $toc                    .= html_writer::start_tag('ul');
                    $toc                    .= html_writer::start_tag('li');
                } else {
                    $toc                    .= html_writer::start_tag('li');
                }

                if (!$ch->hidden) {
                    $ns++;
                    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                        $title              = "$nch.$ns $title";
                    }
                } else {
                    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                        if (empty($chapters[$ch->parent]->hidden)) {
                            $title          = "$nch.x $title";
                        } else {
                            $title          = "x.x $title";
                        }
                    }
                    $title = html_writer::tag('span', $title, ['class' => 'dimmed_text']);
                }
            }

            if ($ch->id == $chapter->id) {
                $toc                        .= html_writer::tag('strong', $title);
            } else {
                $toc                        .= html_writer::link(
                                                                  new moodle_url('view.php', ['id' => $cm->id, 'chapterid' => $ch->id]),
                                                                  $title,
                                                                  ['title' => s($title)]
                                                                );
            }
            $toc                            .= '<br />';
            if ($i != 1) {
                $toc                        .= html_writer::link(
                                                                  new moodle_url('move.php', ['id' => $cm->id, 'chapterid' => $ch->id, 'up' => '1', 'sesskey' => $USER->sesskey]),
                                                                  $OUTPUT->pix_icon('t/up', get_string('up')),
                                                                  ['title' => get_string('up')]
                                                                );
            }
            if ($i != count($chapters)) {
                $toc                        .= html_writer::link(
                                                                  new moodle_url('move.php', ['id' => $cm->id, 'chapterid' => $ch->id, 'up' => '0', 'sesskey' => $USER->sesskey]),
                                                                  $OUTPUT->pix_icon('t/down', get_string('down')),
                                                                  ['title' => get_string('down')]
                                                                );
            }
            $toc                            .= html_writer::link(
                                                                  new moodle_url('edit.php', ['cmid' => $cm->id, 'chapterid' => $ch->id]),
                                                                  $OUTPUT->pix_icon('t/edit', get_string('edit')),
                                                                  ['title' => get_string('edit')]
                                                                );
            $toc                            .= html_writer::link(
                                                                  new moodle_url('delete.php', ['id' => $cm->id, 'chapterid' => $ch->id, 'sesskey' => $USER->sesskey]),
                                                                  $OUTPUT->pix_icon('t/delete', get_string('delete')),
                                                                  ['title' => get_string('delete')]
                                                                );
            if ($ch->hidden) {
                $toc                        .= html_writer::link(
                                                                  new moodle_url('show.php', ['id' => $cm->id, 'chapterid' => $ch->id, 'sesskey' => $USER->sesskey]),
                                                                  $OUTPUT->pix_icon('t/show', get_string('show')),
                                                                  ['title' => get_string('show')]
                                                                );
            } else {
                $toc                        .= html_writer::link(
                                                                  new moodle_url('show.php', ['id' => $cm->id, 'chapterid' => $ch->id, 'sesskey' => $USER->sesskey]),
                                                                  $OUTPUT->pix_icon('t/hide', get_string('hide')),
                                                                  ['title' => get_string('hide')]
                                                                );
            }
            $toc                            .= html_writer::link(
                                                                  new moodle_url('copy.php', ['cmid' => $cm->id, 'chapterid' => $ch->id]),
                                                                  $OUTPUT->pix_icon('t/copy', get_string('copy')),
                                                                  ['title' => get_string('copy')]
                                                                );
            $toc                            .= html_writer::link(
                                                                  new moodle_url('pre-edit.php', ['cmid' => $cm->id, 'pagenum' => $ch->pagenum]),
                                                                  $OUTPUT->pix_icon('add', get_string('addafter', 'mod_ncsubook'), 'mod_ncsubook'),
                                                                  ['title' => get_string('addafter', 'mod_ncsubook')]
                                                                );

            if (!$ch->subchapter) {
                $toc                        .= html_writer::start_tag('ul');
            } else {
                $toc                        .= html_writer::end_tag('li');
            }
            $first                          = 0;
        }

        $toc                                .= html_writer::end_tag('ul') . html_writer::end_tag('li') . html_writer::end_tag('ul');

    } else { // Normal students view.
        $toc                                .= html_writer::start_tag('ul');
        foreach ($chapters as $ch) {
            $title                          = trim(format_string($ch->title, true, ['context' => $context]));
            if (!$ch->hidden) {
                if (!$ch->subchapter) {
                    $nch++;
                    $ns                     = 0;

                    if ($first) {
                        $toc                .= html_writer::start_tag('li');
                    } else {
                        $toc                .= html_writer::end_tag('ul') . html_writer::end_tag('li') . html_writer::start_tag('li');
                    }

                    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                          $title            = "$nch $title";
                    }
                } else {
                    $ns++;

                    if ($first) {
                        $toc                .= html_writer::start_tag('li') . html_writer::start_tag('ul') . html_writer::start_tag('li');
                    } else {
                        $toc                .= html_writer::start_tag('li');
                    }

                    if ($ncsubook->numbering == NCSU_BOOK_NUM_NUMBERS) {
                          $title            = "$nch.$ns $title";
                    }
                }
                if ($ch->id == $chapter->id) {
                    $toc                    .= html_writer::tag('strong', $title);
                } else {
                    $toc                    .= html_writer::link(
                                                                  new moodle_url('view.php', ['id' => $cm->id, 'chapterid' => $ch->id]),
                                                                  $title,
                                                                  ['title' => s($title)]
                                                                );
                }

                if (!$ch->subchapter) {
                    $toc                    .= html_writer::start_tag('ul');
                } else {
                    $toc                    .= html_writer::end_tag('li');
                }

                $first                      = 0;
            }
        }

        $toc                                .= html_writer::end_tag('ul') . html_writer::end_tag('li') . html_writer::end_tag('ul');

    }

    $toc                                    .= html_writer::end_tag('div');

    $toc                                    = str_replace('<ul></ul>', '', $toc); // Cleanup of invalid structures.

    return $toc;
}


/**
 * File browsing support class
 *
 * @copyright  2010-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ncsubook_file_info extends file_info {
    /** @var stdClass Course object */
    protected $course;
    /** @var stdClass Course module object */
    protected $cm;
    /** @var array Available file areas */
    protected $areas;
    /** @var string File area to browse */
    protected $filearea;

    /**
     * Constructor
     *
     * @param file_browser $browser file_browser instance
     * @param stdClass $course course object
     * @param stdClass $cm course module object
     * @param stdClass $context module context
     * @param array $areas available file areas
     * @param string $filearea file area to browse
     */
    public function __construct($browser, $course, $cm, $context, $areas, $filearea) {
        parent::__construct($browser, $context);
        $this->course   = $course;
        $this->cm       = $cm;
        $this->areas    = $areas;
        $this->filearea = $filearea;
    }

    /**
     * Returns list of standard virtual file/directory identification.
     * The difference from stored_file parameters is that null values
     * are allowed in all fields
     * @return array with keys contextid, filearea, itemid, filepath and filename
     */
    public function get_params() {
        return array('contextid'    => $this->context->id,
                     'component'    => 'mod_ncsubook',
                     'filearea'     => $this->filearea,
                     'itemid'       => null,
                     'filepath'     => null,
                     'filename'     => null);
    }

    /**
     * Returns localised visible name.
     * @return string
     */
    public function get_visible_name() {
        return $this->areas[$this->filearea];
    }

    /**
     * Can I add new files or directories?
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Is directory?
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Returns list of children.
     * @return array of file_info instances
     */
    public function get_children() {
        return $this->get_filtered_children('*', false, true);
    }

    /**
     * Help function to return files matching extensions or their count
     *
     * @param string|array $extensions, either '*' or array of lowercase extensions, i.e. array('.gif','.jpg')
     * @param bool|int $countonly if false returns the children, if an int returns just the
     *    count of children but stops counting when $countonly number of children is reached
     * @param bool $returnemptyfolders if true returns items that don't have matching files inside
     * @return array|int array of file_info instances or the count
     */
    private function get_filtered_children($extensions = '*', $countonly = false, $returnemptyfolders = false) {
        global $DB;
        $params     = ['contextid'  => $this->context->id,
                       'component'  => 'mod_ncsubook',
                       'filearea'   => $this->filearea,
                       'ncsubookid' => $this->cm->instance
                      ];

        $sql        = 'SELECT DISTINCT bc.id, bc.pagenum
                        FROM {files} f, {ncsubook_chapters} bc
                        WHERE f.contextid = :contextid
                        AND f.component = :component
                        AND f.filearea = :filearea
                        AND bc.ncsubookid = :ncsubookid
                        AND bc.id = f.itemid';

        if (!$returnemptyfolders) {
            $sql                    .= ' AND filename <> :emptyfilename';
            $params['emptyfilename'] = '.';
        }

        list($sql2, $params2) = $this->build_search_files_sql($extensions, 'f');
        $sql        .= ' ' . $sql2;
        $params     = array_merge($params, $params2);

        if ($countonly === false) {
            $sql    .= ' ORDER BY bc.pagenum';
        }

        $rs         = $DB->get_recordset_sql($sql, $params);
        $children   = array();

        foreach ($rs as $record) {
            if ($child = $this->browser->get_file_info($this->context, 'mod_ncsubook', $this->filearea, $record->id)) {
                if ($returnemptyfolders || $child->count_non_empty_children($extensions)) {
                    $children[] = $child;
                }
            }
            if ($countonly !== false && count($children) >= $countonly) {
                break;
            }
        }
        $rs->close();
        if ($countonly !== false) {
            return count($children);
        }
        return $children;
    }

    /**
     * Returns list of children which are either files matching the specified extensions
     * or folders that contain at least one such file.
     *
     * @param string|array $extensions, either '*' or array of lowercase extensions, i.e. array('.gif','.jpg')
     * @return array of file_info instances
     */
    public function get_non_empty_children($extensions = '*') {
        return $this->get_filtered_children($extensions, false);
    }

    /**
     * Returns the number of children which are either files matching the specified extensions
     * or folders containing at least one such file.
     *
     * @param string|array $extensions, for example '*' or array('.gif','.jpg')
     * @param int $limit stop counting after at least $limit non-empty children are found
     * @return int
     */
    public function count_non_empty_children($extensions = '*', $limit = 1) {
        return $this->get_filtered_children($extensions, $limit);
    }

    /**
     * Returns parent file_info instance
     * @return file_info or null for root
     */
    public function get_parent() {
        return $this->browser->get_file_info($this->context);
    }
}

function ncsubook_get_chapter_content($chapterid) {
    global $DB;
    $blockdata = false;
    $qry       = 'SELECT a.*, b.csselementtype
                  FROM {ncsubook_blocks} a
                  INNER JOIN {ncsubook_blocktype} b on a.type = b.id
                  WHERE chapterid = ?
                  ORDER by a.blockorder';
    $blockdata = $DB->get_records_sql($qry, [$chapterid]);

    return $blockdata;
}

function ncsubook_get_block_class($blocktype) {
    global $DB;
    $blockclass = false;

    $blockclass = $DB->get_record('ncsubook_blocktype', ['id' => $blocktype], 'cssclassname');
    return $blockclass->cssclassname;
}


function ncsubook_get_block_header($blockid) {
    global $DB;
    $blockheader = '';

    $blockheader = $DB->get_record('ncsubook_blocks', array('id' => $blockid), 'title');
    return $blockheader->title;
}

/* Gary Harris - 4/18/2013
 * The following function rearranges an array of block objects based on the csselementtype of the object.
 * If it sees a content block followed by any number of float blocks, it puts the float blocks above the
 * content block. For example, suppose the array of objects had the following csselementtypes:
 *      content, float, float, content, non-float, non-float
 * It would return an array of objects of the order:
 *      float, float, content, content, non-float, non-float.
 * End GDH */
function ncsubook_rearrange_content_blocks($blocks) {
    $blockarr               = array();
    $contentblock           = '';
    $contentblockfound      = false;
    $blockcount             = count((array)$blocks);
    $currentblockcount      = 0;

    foreach ($blocks as $block) {
        $currentblockcount++;
        if ($block->csselementtype == 'content' && !$contentblockfound) {
            $contentblock       = $block;
            $contentblockfound  = true;
        } else if ($block->csselementtype == 'content' && $contentblockfound) {
            $blockarr[]         = $contentblock;
            $contentblock       = $block;
        } else if ($block->csselementtype == 'float') {
            $blockarr[]         = $block;
        } else if ($block->csselementtype != 'float' && $contentblockfound) {
            $blockarr[]         = $contentblock;
            $blockarr[]         = $block;
            $contentblock       = '';
            $contentblockfound  = false;
        } else if ($block->csselementtype != 'float' && !$contentblockfound) {
            $blockarr[]         = $block;
        }

        if ($currentblockcount == $blockcount && $contentblockfound) {
            $blockarr[] = $contentblock;
        }
    }
    return $blockarr;
}

function ncsubook_get_latest_pagenum_from_bookid($bookid) {
    global $DB;

    $result = $DB->get_record_sql('select max(pagenum) as maxpagenum from {ncsubook_chapters} where ncsubookid = ?', [$bookid]);
    if (!is_numeric($result)) {
        return 0;
    }
    return $result;
}

function ncsubook_add_default_blocks($chapter) {
    global $DB;

    if (empty($chapter->id) or !isSet($chapter->type)) {
        return false;
    }

    $blocks                 = array();
    $content                = new stdClass();
    $content->chapterid     = $chapter->id;
    // $content->type       = $chapter->type;
    $content->title         = 'Content';
    $content->contentformat = FORMAT_HTML;
    $content->blockorder    = 1;
    $content->timecreated   = time();
    $content->timemodified  = time();

    // Gary Harris - 4/9/2013.
    // The types below are block types which are stored in the ncsubook_blocktype table.
    // End GDH.
    switch ($chapter->type) {
        case 5:
            // Insert Default content block for summary chapter types.
            $content->content = 'Content goes here.';
            $content->type              = 1;
            $blocks['content']          = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 4:
            // Insert default content block for Assignment chapter types.
            $content->content           = 'Content goes here.';
            $content->type              = 1;
            $blocks['content']          = $DB->insert_record( 'ncsubook_blocks', $content );

            // Insert default Assignment Wide block for Assignment chapter types.
            $assignment                 = new stdClass();
            $assignment->chapterid      = $chapter->id;
            $assignment->type           = 7;
            $assignment->title          = 'Assessment Wide View';
            $assignment->content        = 'Assignment: Here is your next assignment.';
            $assignment->contentformat  = FORMAT_HTML;
            $assignment->blockorder     = 2;
            $assignment->timecreated    = time();
            $assignment->timemodified   = time();
            $blocks['assignmentwide']   = $DB->insert_record('ncsubook_blocks', $assignment);
        break;
        case 3:
            // Insert default content block for Basic chapter types.
            $content->content           = 'Content goes here.';
            $content->type              = 1;
            $blocks['content']          = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 2:
            // Insert default content block for Learning Objectives chapter types.
            $content->content           = 'Content goes here.';
            $content->type              = 1;
            $blocks['content']          = $DB->insert_record( 'ncsubook_blocks', $content );

            // Insert default Call Out 1 block for Learning Objective chapter types.
            $callout                    = new stdClass();
            $callout->chapterid         = $chapter->id;
            $callout->type              = 5;
            $callout->title             = 'Call Out 1';
            $callout->content           = 'Critical Focus Points: <ul><li>Focus Point 1</li><li>Focus Point 2</li><li>...</li></ul>.';
            $callout->contentformat     = FORMAT_HTML;
            $callout->blockorder        = 2;
            $callout->timecreated       = time();
            $callout->timemodified      = time();
            $blocks['callout1']         = $DB->insert_record('ncsubook_blocks', $callout);
        break;
        case 1:
            // Insert default content block for Introduction chapter types.
            $content->content           = 'Content goes here.';
            $content->type              = 1;
            $blocks['content']          = $DB->insert_record( 'ncsubook_blocks', $content );

            // Insert default Learning Objective block for Introduction chapter types
            $objectives                 = new stdClass();
            $objectives->chapterid      = $chapter->id;
            $objectives->type           = 2;
            $objectives->title          = 'Learning Objectives';
            $objectives->content        = 'After completing this unit you will be able to: <ul><li>Objective 1</li><li>Objective 2</li><li>...</li></ul>.';
            $objectives->contentformat  = FORMAT_HTML;
            $objectives->blockorder     = 2;
            $objectives->timecreated    = time();
            $objectives->timemodified   = time();
            $blocks['learningobjectives'] = $DB->insert_record('ncsubook_blocks', $objectives);
        break;
        default:
            // Insert default content block for Introduction chapter types
            $content->content           = 'Content goes here.';
            $content->type              = 1;
            $blocks['content']          = $DB->insert_record( 'ncsubook_blocks', $content );
    }

    if ( !empty($blocks) ) {
        return $blocks;
    }

    return false;
}

function ncsubook_get_block_list($chapterid) {
    global $DB;
    $qry    = 'SELECT id, title
                FROM {ncsubook_blocks}
                WHERE chapterid = ?
                ORDER BY blockorder ASC';
    $result = $DB->get_records_sql($qry, [$chapterid]);
    return $result;
}

function ncsubook_update_chapter_info($fields) {
    global $DB;

    $result = $DB->update_record("ncsubook_chapters", $fields);

    return $result;
}

function ncsubook_add_block($block, $chapterid) {
    global $DB;

    if (empty($chapterid) or !isSet($block)) {
        return false;
    }

    $maxblocks              = ncsubook_get_max_block_order($chapterid);
    foreach ($maxblocks as $key => $value) {
        $maxblockorder      = $value->maxblockorder;
    }

    $blocks                     = array();
    $content                    = new stdClass();
    $content->chapterid         = $chapterid;
    $content->type              = $block;
    $content->contentformat     = FORMAT_HTML;
    $content->blockorder        = $maxblockorder + 1;
    $content->timecreated       = time();
    $content->timemodified      = time();

    switch ($content->type) {
        case 8:
            $content->title     = 'Assessment Narrow';
            $content->content   = 'Content goes here.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 7:
            $content->title     = 'Assessment Wide';
            $content->content   = 'Content goes here.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 6:
            $content->title     = 'Call Out 2';
            $content->content   = 'Critical Focus Points: <ul><li>Focus Point 1</li><li>Focus Point 2</li><li>...</li></ul>.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 5:
            $content->title     = 'Call Out 1';
            $content->content   = 'Critical Focus Points: <ul><li>Focus Point 1</li><li>Focus Point 2</li><li>...</li></ul>.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 4:
            $content->title     = 'Activity Narrow';
            $content->content   = 'Content goes here.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 3:
            $content->title     = 'Activity Wide';
            $content->content   = 'Content goes here.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 2:
            $content->title     = 'Learning Objectives';
            $content->content   = 'After completing this unit you will be able to: <ul><li>Objective 1</li><li>Objective 2</li><li>...</li></ul>.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
        case 1:
            $content->title     = 'Content';
            $content->content   = 'Content goes here.';
            $blocks['content']  = $DB->insert_record( 'ncsubook_blocks', $content );
        break;
    }

    if (!empty( $blocks) ) {
        return $blocks;
    }

    return false;
}

function ncsubook_count_content_blocks($chapterid) {
    global $DB;
    $qry    = 'SELECT COUNT(*) AS numcontentblocks
                FROM {ncsubook_blocks}
                WHERE chapterid = ?
                AND type = ?';
    $result = $DB->count_records_sql($qry, [$chapterid, '1']);
    return $result;
}

function ncsubook_get_blocktype($blockid) {
    global $DB;
    $qry    = 'SELECT type
                FROM {ncsubook_blocks}
                WHERE id = ?';
    $result = $DB->get_records_sql($qry, array($blockid));
    return $result;
}

function ncsubook_get_max_block_order($chapterid) {
    global $DB;
    $qry    = 'SELECT MAX(blockorder) AS maxblockorder
                FROM {ncsubook_blocks}
                WHERE chapterid = ?';
    $result = $DB->get_records_sql($qry, [$chapterid]);
    return $result;
}

function ncsubook_reorder_blocks ($chapterid) {
    global $DB;
    $qry    = 'SELECT id, blockorder
                FROM {ncsubook_blocks}
                WHERE chapterid = ?
                ORDER BY blockorder ASC';
    // Get the list of blocks in order.
    $blocks = $DB->get_records_sql($qry, [$chapterid]);

    // Cycle through each block record and reorder the blocks 1 thru however many blocks there are.
    $newblockordervalue     = 1;
    foreach ($blocks as $block) {
        $block->blockorder  = $newblockordervalue;
        $result             = $DB->update_record('ncsubook_blocks', $block);
        $newblockordervalue++;
    }
    return true;
}

function ncsubook_get_chapter_css_tag($chapterid) {
    global $DB;
    $qry    = 'SELECT b.cssclassname
                FROM {ncsubook_chapters} a
                INNER JOIN {ncsubook_chaptertype} b on a.type = b.id
                WHERE a.id = ?';
    $result = $DB->get_records_sql($qry, [$chapterid]);
    foreach ($result as $value) {
        return $value->cssclassname;
    }
}


function ncsubook_get_parent_chapter_title($bookid, $chapterpagenum) {
    global $DB;

    $table  = 'ncsubook_chapters';
    $select = 'ncsubookid = ? and pagenum < ? and subchapter = 0';
    $params = [$bookid, $chapterpagenum];
    $sort   = 'pagenum desc';
    $result = $DB->get_records_select($table, $select, $params, $sort, 'title', 0, 1);
    foreach ($result as $record) {
        return $record->title;
    }
}
