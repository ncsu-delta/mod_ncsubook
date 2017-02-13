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
 * Book imscp export lib
 *
 * @package    ncsubooktool_exportimscp
 * @copyright  2001-3001 Antonio Vicent          {@link http://ludens.es}
 * @copyright  2001-3001 Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  2011 Petr Skoda                   {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/mod/ncsubook/locallib.php');

/**
 * Export one ncsubook as IMSCP package
 *
 * @param stdClass $ncsubook ncsubook instance
 * @param context_module $context
 * @return bool|stored_file
 */
function ncsubooktool_exportimscp_build_package($ncsubook, $context) {
    global $DB;

    $fs = get_file_storage();

    if ($packagefile = $fs->get_file($context->id, 'ncsubooktool_exportimscp', 'package', $ncsubook->revision, '/', 'imscp.zip')) {
        return $packagefile;
    }

    // fix structure and test if chapters present
    if (!ncsubook_preload_chapters($ncsubook)) {
        print_error('nochapters', 'ncsubooktool_exportimscp');
    }

    // prepare temp area with package contents
    ncsubooktool_exportimscp_prepare_files($ncsubook, $context);

    $packer = get_file_packer('application/zip');
    $areafiles = $fs->get_area_files($context->id, 'ncsubooktool_exportimscp', 'temp', $ncsubook->revision, "sortorder, itemid, filepath, filename", false);
    $files = array();
    foreach ($areafiles as $file) {
        $path = $file->get_filepath().$file->get_filename();
        $path = ltrim($path, '/');
        $files[$path] = $file;
    }
    unset($areafiles);
    $packagefile = $packer->archive_to_storage($files, $context->id, 'ncsubooktool_exportimscp', 'package', $ncsubook->revision, '/', 'imscp.zip');

    // drop temp area
    $fs->delete_area_files($context->id, 'ncsubooktool_exportimscp', 'temp', $ncsubook->revision);

    // delete older versions
    $sql = "SELECT DISTINCT itemid
              FROM {files}
             WHERE contextid = :contextid AND component = 'ncsubooktool_exportimscp' AND itemid < :revision";
    $params = array('contextid'=>$context->id, 'revision'=>$ncsubook->revision);
    $revisions = $DB->get_records_sql($sql, $params);
    foreach ($revisions as $rev => $unused) {
        $fs->delete_area_files($context->id, 'ncsubooktool_exportimscp', 'temp', $rev);
        $fs->delete_area_files($context->id, 'ncsubooktool_exportimscp', 'package', $rev);
    }

    return $packagefile;
}

/**
 * Prepare temp area with the files used by ncsubook html contents
 *
 * @param stdClass $ncsubook ncsubook instance
 * @param context_module $context
 */
function ncsubooktool_exportimscp_prepare_files($ncsubook, $context) {
    global $CFG, $DB;

    $fs = get_file_storage();

    $temp_file_record = array('contextid'=>$context->id, 'component'=>'ncsubooktool_exportimscp', 'filearea'=>'temp', 'itemid'=>$ncsubook->revision);
    $chapters = $DB->get_records('ncsubook_chapters', array('ncsubookid'=>$ncsubook->id), 'pagenum');
    $chapterresources = array();
    foreach ($chapters as $chapter) {
        $chapterresources[$chapter->id] = array();
        $files = $fs->get_area_files($context->id, 'mod_ncsubook', 'chapter', $chapter->id, "sortorder, itemid, filepath, filename", false);
        foreach ($files as $file) {
            $temp_file_record['filepath'] = '/'.$chapter->pagenum.$file->get_filepath();
            $fs->create_file_from_storedfile($temp_file_record, $file);
            $chapterresources[$chapter->id][] = $chapter->pagenum.$file->get_filepath().$file->get_filename();
        }
        if ($file = $fs->get_file($context->id, 'ncsubooktool_exportimscp', 'temp', $ncsubook->revision, "/$chapter->pagenum/", 'index.html')) {
            // this should not exist
            $file->delete();
        }
        $content = ncsubooktool_exportimscp_chapter_content($chapter, $context);
        $index_file_record = array('contextid'=>$context->id, 'component'=>'ncsubooktool_exportimscp', 'filearea'=>'temp',
                'itemid'=>$ncsubook->revision, 'filepath'=>"/$chapter->pagenum/", 'filename'=>'index.html');
        $fs->create_file_from_string($index_file_record, $content);
    }

    $css_file_record = array('contextid'=>$context->id, 'component'=>'ncsubooktool_exportimscp', 'filearea'=>'temp',
            'itemid'=>$ncsubook->revision, 'filepath'=>"/css/", 'filename'=>'styles.css');
    $fs->create_file_from_pathname($css_file_record, dirname(__FILE__).'/imscp.css');

    // Init imsmanifest and others
    $imsmanifest = '';
    $imsitems = '';
    $imsresources = '';

    // Moodle and Book version
    $moodle_release = $CFG->release;
    $moodle_version = $CFG->version;
    $ncsubook_version   = $DB->get_field('modules', 'version', array('name'=>'ncsubook'));
    $ncsubookname       = format_string($ncsubook->name, true, array('context'=>$context));

    // Load manifest header
        $imsmanifest .= '<?xml version="1.0" encoding="UTF-8"?>
<!-- This package has been created with Moodle ' . $moodle_release . ' (' . $moodle_version . ') http://moodle.org/, NC State Book module version ' . $ncsubook_version . ' - https://github.com/skodak/moodle-mod_ncsubook -->
<!-- One idea and implementation by Eloy Lafuente (stronk7) and Antonio Vicent (C) 2001-3001 -->
<manifest xmlns="http://www.imsglobal.org/xsd/imscp_v1p1" xmlns:imsmd="http://www.imsglobal.org/xsd/imsmd_v1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="MANIFEST-' . md5($CFG->wwwroot . '-' . $ncsubook->course . '-' . $ncsubook->id) . '" xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://www.imsglobal.org/xsd/imsmd_v1p2 imsmd_v1p2p2.xsd">
  <organizations default="MOODLE-' . $ncsubook->course . '-' . $ncsubook->id . '">
    <organization identifier="MOODLE-' . $ncsubook->course . '-' . $ncsubook->id . '" structure="hierarchical">
      <title>' . htmlspecialchars($ncsubookname) . '</title>';

    // To store the prev level (ncsubook only have 0 and 1)
    $prevlevel = null;
    $currlevel = 0;
    foreach ($chapters as $chapter) {
        // Calculate current level ((ncsubook only have 0 and 1)
        $currlevel = empty($chapter->subchapter) ? 0 : 1;
        // Based upon prevlevel and current one, decide what to close
        if ($prevlevel !== null) {
            // Calculate the number of spaces (for visual xml-text formating)
            $prevspaces = substr('                ', 0, $currlevel * 2);

            // Same level, simply close the item
            if ($prevlevel == $currlevel) {
                $imsitems .= $prevspaces . '        </item>' . "\n";
            }
            // Bigger currlevel, nothing to close
            // Smaller currlevel, close both the current item and the parent one
            if ($prevlevel > $currlevel) {
                $imsitems .= '          </item>' . "\n";
                $imsitems .= '        </item>' . "\n";
            }
        }
        // Update prevlevel
        $prevlevel = $currlevel;

        // Calculate the number of spaces (for visual xml-text formatting)
        $currspaces = substr('                ', 0, $currlevel * 2);

        $chaptertitle = format_string($chapter->title, true, array('context'=>$context));

        // Add the imsitems
        $imsitems .= $currspaces .'        <item identifier="ITEM-' . $ncsubook->course . '-' . $ncsubook->id . '-' . $chapter->pagenum .'" isvisible="true" identifierref="RES-' .
                $ncsubook->course . '-' . $ncsubook->id . '-' . $chapter->pagenum . "\">\n" .
                $currspaces . '         <title>' . htmlspecialchars($chaptertitle) . '</title>' . "\n";

        // Add the imsresources
        // First, check if we have localfiles
        $localfiles = array();
        foreach ($chapterresources[$chapter->id] as $localfile) {
            $localfiles[] = "\n" . '      <file href="' . $localfile . '" />';
        }
        // Now add the dependency to css
        $cssdependency = "\n" . '      <dependency identifierref="RES-' . $ncsubook->course . '-'  . $ncsubook->id . '-css" />';
        // Now build the resources section
        $imsresources .= '    <resource identifier="RES-' . $ncsubook->course . '-'  . $ncsubook->id . '-' . $chapter->pagenum . '" type="webcontent" xml:base="' .
                $chapter->pagenum . '/" href="index.html">' . "\n" .
                '      <file href="' . $chapter->pagenum . '/index.html" />' . implode($localfiles) . $cssdependency . "\n".
                '    </resource>' . "\n";
    }

    // Close items (the latest chapter)
    // Level 1, close 1
    if ($currlevel == 0) {
        $imsitems .= '        </item>' . "\n";
    }
    // Level 2, close 2
    if ($currlevel == 1) {
        $imsitems .= '          </item>' . "\n";
        $imsitems .= '        </item>' . "\n";
    }

    // Define the css common resource
    $cssresource = '    <resource identifier="RES-' . $ncsubook->course . '-'  . $ncsubook->id . '-css" type="webcontent" xml:base="css/" href="styles.css">
      <file href="css/styles.css" />
    </resource>' . "\n";

    // Add imsitems to manifest
    $imsmanifest .= "\n" . $imsitems;
    // Close the organization
    $imsmanifest .= "    </organization>
  </organizations>";
    // Add resources to manifest
    $imsmanifest .= "\n  <resources>\n" . $imsresources . $cssresource . "  </resources>";
    // Close manifest
    $imsmanifest .= "\n</manifest>\n";

    $manifest_file_record = array('contextid'=>$context->id, 'component'=>'ncsubooktool_exportimscp', 'filearea'=>'temp',
            'itemid'=>$ncsubook->revision, 'filepath'=>"/", 'filename'=>'imsmanifest.xml');
    $fs->create_file_from_string($manifest_file_record, $imsmanifest);
}

/**
 * Returns the html contents of one ncsubook's chapter to be exported as IMSCP
 *
 * @param stdClass $chapter the chapter to be exported
 * @param context_module $context context the chapter belongs to
 * @return string the contents of the chapter
 */
function ncsubooktool_exportimscp_chapter_content($chapter, $context) {

    $options = new stdClass();
    $options->noclean = true;
    $options->context = $context;

    $chaptercontent = str_replace('@@PLUGINFILE@@/', '', $chapter->content);
    $chaptercontent = format_text($chaptercontent, $chapter->contentformat, $options);

    $chaptertitle = format_string($chapter->title, true, array('context'=>$context));

    $content = '';
    $content .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">' . "\n";
    $content .= '<html>' . "\n";
    $content .= '<head>' . "\n";
    $content .= '<meta http-equiv="content-type" content="text/html; charset=utf-8" />' . "\n";
    $content .= '<link rel="stylesheet" type="text/css" href="../css/styles.css" />' . "\n";
    $content .= '<title>' . $chaptertitle . '</title>' . "\n";
    $content .= '</head>' . "\n";
    $content .= '<body>' . "\n";
    $content .= '<h1 id="header">' . $chaptertitle . '</h1>' ."\n";
    $content .= $chaptercontent . "\n";
    $content .= '</body>' . "\n";
    $content .= '</html>' . "\n";

    return $content;
}
