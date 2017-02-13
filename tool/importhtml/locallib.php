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
 * HTML import lib
 *
 * @package    ncsubooktool_importhtml
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot . '/mod/ncsubook/locallib.php');

/**
 * Import HTML pages packaged into one zip archive
 *
 * @param stored_file $package
 * @param string $type type of the package ('typezipdirs' or 'typezipfiles')
 * @param stdClass $ncsubook
 * @param context_module $context
 * @param bool $verbose
 */
function toolncsubook_importhtml_import_chapters($package, $type, $ncsubook, $context, $verbose = true) {
    global $DB, $OUTPUT;

    $fs             = get_file_storage();
    $chapterfiles   = toolncsubook_importhtml_get_chapter_files($package, $type);
    $packer         = get_file_packer('application/zip');
    $chapters       = [];
    $fs->delete_area_files($context->id, 'mod_ncsubook', 'importhtmltemp', 0);
    $package->extract_to_storage($packer, $context->id, 'mod_ncsubook', 'importhtmltemp', 0, '/');

    // $datafiles = $fs->get_area_files($context->id, 'mod_ncsubook', 'importhtmltemp', 0, 'id', false);
    // echo "<pre>";p(var_export($datafiles, true));
    if ($verbose) {
        echo $OUTPUT->notification(get_string('importing', 'ncsubooktool_importhtml'), 'notifysuccess');
    }
    if ($type == 0) {
        $chapterfile = reset($chapterfiles);
        if ($file = $fs->get_file_by_hash($context->id . '/mod_ncsubook/importhtmltemp/0/' . $chapterfile->pathname)) {
            $htmlcontent = toolncsubook_importhtml_fix_encoding($file->get_content());
            $htmlchapters = toolncsubook_importhtml_parse_headings(toolncsubook_importhtml_parse_body($htmlcontent));
            // TODO: process h1 as main chapter and h2 as subchapters
        }
    } else {
        foreach ($chapterfiles as $chapterfile) {
            if ($file = $fs->get_file_by_hash(sha1('/' . $context->id . '/mod_ncsubook/importhtmltemp/0/' . $chapterfile->pathname))) {
                $chapter = new stdClass();
                $htmlcontent = toolncsubook_importhtml_fix_encoding($file->get_content());

                $chapter->ncsubookid    = $ncsubook->id;
                $chapter->pagenum       = $DB->get_field_sql('SELECT MAX(pagenum) FROM {ncsubook_chapters} WHERE ncsubookid = ?', [$ncsubook->id]) + 1;
                $chapter->importsrc     = directory_separator . $chapterfile->pathname;
                $chapter->content       = toolncsubook_importhtml_parse_styles($htmlcontent);
                                        . toolncsubook_importhtml_parse_body($htmlcontent);
                $chapter->title         = toolncsubook_importhtml_parse_title($htmlcontent, $chapterfile->pathname);
                $chapter->contentformat = FORMAT_HTML;
                $chapter->hidden        = 0;
                $chapter->timecreated   = time();
                $chapter->timemodified  = time();
                if (preg_match('/_sub(\/|\.htm)/i', $chapter->importsrc)) { // If filename or directory ends with *_sub treat as subchapters
                    $chapter->subchapter = 1;
                } else {
                    $chapter->subchapter = 0;
                }

                $chapter->id = $DB->insert_record('ncsubook_chapters', $chapter);
                $chapters[$chapter->id] = $chapter;

                \mod_ncsubook\event\chapter_created::create_from_chapter($ncsubook, $context, $chapter)->trigger();
            }
        }
    }

    if ($verbose) {
        echo $OUTPUT->notification(get_string('relinking', 'ncsubooktool_importhtml'), 'notifysuccess');
    }
    $allchapters = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum');
    foreach ($chapters as $chapter) {
        // find references to all files and copy them + relink them
        $matches = null;
        if (preg_match_all('/(src|codebase|name|href)\s*=\s*"([^"]+)"/i', $chapter->content, $matches)) {
            $filerecord = ['contextid' => $context->id, 'component' => 'mod_ncsubook', 'filearea' => 'chapter', 'itemid' => $chapter->id];
            foreach ($matches[0] as $i => $match) {
                $filepath = dirname($chapter->importsrc).'/'.$matches[2][$i];
                $filepath = toolncsubook_importhtml_fix_path($filepath);

                if (strtolower($matches[1][$i]) === 'href') {
                    // skip linked html files, we will try chapter relinking later
                    foreach ($allchapters as $target) {
                        if ($target->importsrc === $filepath) {
                            continue 2;
                        }
                    }
                }

                if ($file = $fs->get_file_by_hash(sha1('/' . $context->id . '/mod_ncsubook/importhtmltemp/0' . $filepath))) {
                    if (!$oldfile = $fs->get_file_by_hash(sha1('/' . $context->id . '/mod_ncsubook/chapter/' . $chapter->id . $filepath))) {
                        $fs->create_file_from_storedfile($filerecord, $file);
                    }
                    $chapter->content = str_replace($match, $matches[1][$i] . '="@@PLUGINFILE@@' . $filepath .'"', $chapter->content);
                }
            }
            $DB->set_field('ncsubook_chapters', 'content', $chapter->content, ['id' => $chapter->id]);
        }
    }
    unset($chapters);

    $allchapters = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum');
    foreach ($allchapters as $chapter) {
        $newcontent = $chapter->content;
        $matches    = null;
        if (preg_match_all('/(href)\s*=\s*"([^"]+)"/i', $chapter->content, $matches)) {
            foreach ($matches[0] as $i => $match) {
                if (strpos($matches[2][$i], ':') !== false or strpos($matches[2][$i], '@') !== false) {
                    // it is either absolute or pluginfile link
                    continue;
                }
                $chapterpath = dirname($chapter->importsrc).'/'.$matches[2][$i];
                $chapterpath = toolncsubook_importhtml_fix_path($chapterpath);
                foreach ($allchapters as $target) {
                    if ($target->importsrc === $chapterpath) {
                        $newcontent = str_replace($match, 'href="'
                                                    . new moodle_url('/mod/ncsubook/view.php', ['id' => $context->instanceid, 'chapter' => $target->id])
                                                    . '"', $newcontent
                                                 );
                    }
                }
            }
        }
        if ($newcontent !== $chapter->content) {
            $DB->set_field('ncsubook_chapters', 'content', $newcontent, ['id' => $chapter->id]);
        }
    }

    $fs->delete_area_files($context->id, 'mod_ncsubook', 'importhtmltemp', 0);

    // update the revision flag - this takes a long time, better to refetch the current value
    $ncsubook = $DB->get_record('ncsubook', ['id' => $ncsubook->id]);
    $DB->set_field('ncsubook', 'revision', $ncsubook->revision + 1, ['id' => $ncsubook->id]);
}

/**
 * Parse the headings of the imported package of type 'typeonefile'
 * (currently unsupported)
 *
 * @param string $html html content to parse
 * @todo implement this once the type 'typeonefile' is enabled
 */
function toolncsubook_importhtml_parse_headings($html) {
}

/**
 * Parse the links to external css sheets of the imported html content
 *
 * @param string $html html content to parse
 * @return string all the links to external css sheets
 */
function toolncsubook_importhtml_parse_styles($html) {
    $styles = '';
    if (preg_match('/<head[^>]*>(.+)<\/head>/is', $html, $matches)) {
        $head = $matches[1];
        if (preg_match_all('/<link[^>]+rel="stylesheet"[^>]*>/i', $head, $matches)) { // Extract links to css.
            $matchcount = count($matches[0]);
            for ($i = 0; $i < $matchcount; $i++) {
                $styles .= $matches[0][$i] . "\n";
            }
        }
    }
    return $styles;
}

/**
 * Normalize paths to be absolute
 *
 * @param string $path original path with MS/relative separators
 * @return string the normalized and cleaned absolute path
 */
function toolncsubook_importhtml_fix_path($path) {
    $path = str_replace('\\', '/', $path); // anti MS hack
    $path = directory_separator . ltrim($path, './'); // dirname() produces . for top level files + our paths start with /
    $cnt  = substr_count($path, '..');

    for ($i = 0; $i < $cnt; $i++) {
        $path = preg_replace('|[^/]+/\.\./|', '', $path, 1);
    }

    $path = clean_param($path, PARAM_PATH);
    return $path;
}

/**
 * Convert some html content to utf8, getting original encoding from html headers
 *
 * @param string $html html content to convert
 * @return string html content converted to utf8
 */
function toolncsubook_importhtml_fix_encoding($html) {
    if (preg_match('/<head[^>]*>(.+)<\/head>/is', $html, $matches)) {
        $head = $matches[1];
        if (preg_match('/charset=([^"]+)/is', $head, $matches)) {
            $enc = $matches[1];
            return textlib::convert($html, $enc, 'utf-8');
        }
    }
    return iconv('UTF-8', 'UTF-8//IGNORE', $html);
}

/**
 * Extract the body from any html contents
 *
 * @param string $html the html to parse
 * @return string the contents of the body
 */
function toolncsubook_importhtml_parse_body($html) {
    $matches = null;
    if (preg_match('/<body[^>]*>(.+)<\/body>/is', $html, $matches)) {
        return $matches[1];
    } else {
        return '';
    }
}

/**
 * Extract the title of any html content, getting it from the title tag
 *
 * @param string $html the html to parse
 * @param string $default default title to apply if no title is found
 * @return string the resulting title
 */
function toolncsubook_importhtml_parse_title($html, $default) {
    $matches = null;
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
        return $matches[1];
    } else {
        return $default;
    }
}

/**
 * Returns all the html files (chapters) from a file package
 *
 * @param stored_file $package file to be processed
 * @param string $type type of the package ('typezipdirs' or 'typezipfiles')
 *
 * @return array the html files found in the package
 */
function toolncsubook_importhtml_get_chapter_files($package, $type) {
    $packer         = get_file_packer('application/zip');
    $files          = $package->list_files($packer);
    $tophtmlfiles   = [];
    $subhtmlfiles   = [];
    $topdirs        = [];

    foreach ($files as $file) {
        if (empty($file->pathname)) {
            continue;
        }
        if (substr($file->pathname, -1) === '/') {
            if (substr_count($file->pathname, '/') !== 1) {
                // skip subdirs
                continue;
            }
            if (!isset($topdirs[$file->pathname])) {
                $topdirs[$file->pathname] = array();
            }

        } else {
            $mime = mimeinfo('icon', $file->pathname);
            if ($mime !== 'html') {
                continue;
            }
            $level = substr_count($file->pathname, '/');
            if ($level === 0) {
                $tophtmlfiles[$file->pathname] = $file;
            } else if ($level === 1) {
                $subhtmlfiles[$file->pathname] = $file;
                $dir = preg_replace('|/.*$|', '', $file->pathname);
                $topdirs[$dir][$file->pathname] = $file;
            } else {
                // lower levels are not interesting
                continue;
            }
        }
    }

    collatorlib::ksort($tophtmlfiles, collatorlib::SORT_NATURAL);
    collatorlib::ksort($subhtmlfiles, collatorlib::SORT_NATURAL);
    collatorlib::ksort($topdirs, collatorlib::SORT_NATURAL);

    $chapterfiles = [];

    if ($type == 2) {
        $chapterfiles = $tophtmlfiles;

    } else if ($type == 1) {
        foreach ($topdirs as $dir => $htmlfiles) {
            if (empty($htmlfiles)) {
                continue;
            }
            collatorlib::ksort($htmlfiles, collatorlib::SORT_NATURAL);
            if (isset($htmlfiles[$dir.'/index.html'])) {
                $htmlfile = $htmlfiles[$dir.'/index.html'];
            } else if (isset($htmlfiles[$dir.'/index.htm'])) {
                $htmlfile = $htmlfiles[$dir.'/index.htm'];
            } else if (isset($htmlfiles[$dir.'/Default.htm'])) {
                $htmlfile = $htmlfiles[$dir.'/Default.htm'];
            } else {
                $htmlfile = reset($htmlfiles);
            }
            $chapterfiles[$htmlfile->pathname] = $htmlfile;
        }
    } else if ($type == 0) {
        if ($tophtmlfiles) {
            if (isset($tophtmlfiles['index.html'])) {
                $htmlfile = $tophtmlfiles['index.html'];
            } else if (isset($tophtmlfiles['index.htm'])) {
                $htmlfile = $tophtmlfiles['index.htm'];
            } else if (isset($tophtmlfiles['Default.htm'])) {
                $htmlfile = $tophtmlfiles['Default.htm'];
            } else {
                $htmlfile = reset($tophtmlfiles);
            }
        } else {
            $htmlfile = reset($subhtmlfiles);
        }
        $chapterfiles[$htmlfile->pathname] = $htmlfile;
    }

    return $chapterfiles;
}
