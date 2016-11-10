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
 * NC State Book module language strings
 *
 * @package    mod_ncsubook
 * @copyright  2004-2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['modulename'] = 'NC State Book';
$string['modulename_help'] = 'The NC State Book module enables a teacher to create a multi-page resource in a book-like format, with pages and subpages for organization. NC State Books can contain media files as well as text and are useful for displaying lengthy passages of information which can be broken down into sections.

An NC State Book may be used

* To display reading material for individual modules of study
* As a staff departmental handbook
* As a showcase portfolio of student work

The "More help" link below will take you to a tutorial about the NC State Book. You may be asked to log in to the Moodle Projects server.';
$string['modulename_link'] = 'https://moodle-projects.wolfware.ncsu.edu/course/view.php?id=672';
$string['modulenameplural'] = 'NC State Books';
$string['pluginname'] = 'NC State Book';
$string['pluginadministration'] = 'NC State Book administration';

$string['toc'] = 'Table of contents';

$string['editingblock'] = 'Editing Block';
$string['chapters'] = 'Pages';
$string['event_chapter_created'] = 'Chapter created';
$string['event_chapter_deleted'] = 'Chapter deleted';
$string['event_chapter_updated'] = 'Chapter updated';
$string['event_chapter_viewed'] = 'Chapter viewed';
$string['event_instances_list_viewed'] = 'Instances list viewed';
$string['event_course_module_viewed'] = 'Course module viewed';
$string['editingchapter'] = 'Editing Page';
$string['editingchapterpagecontent'] = 'Edit Blocks';
$string['editingchaptertype'] = 'Select a Page Type';
$string['editingchaptertype_change'] = 'Change the Page Type';
$string['editingchaptertype_help'] = 'The design of a Page helps students identify the goal of the page. You may change Page Types and use them in ways different than outlined here. You do not have to use all of the Page Types. There are five different types of pages.

The five Page Types and their intended uses are:

* Introduction: Introduces the book and provides an overview of what will be covered. The Introduction page includes a Learning Objectives block.
* Learning Objectives: Provides a space for describing the learning objectives for a book in greater detail, particularly if the block on the Introduction page is not sufficient. The Learning Objectives page ncludes a Call Out 1 block.
* Basic: Contains the bulk of book content. Basic pages are best used for presenting the general "lecture" or learning content of a book.
* Assignment: Grabs the students attention for an assignment or activity. It includes an Assessment Wide block by default.
* Summary: Wraps up the main learning points of a book.';
$string['changingchaptertype'] = 'Change the Page Type';
$string['changingchaptertype_help'] = 'The design of a Page helps students identify the goal of the page. You may change Page Types and use them in ways different than outlined here. You do not have to use all of the Page Types. There are five different types of pages.

The five Page Types and their intended uses are:

* Introduction: Introduces the book and provides an overview of what will be covered. The Introduction page includes a Learning Objectives block.
* Learning Objectives: Provides a space for describing the learning objectives for a book in greater detail, particularly if the block on the Introduction page is not sufficient. The Learning Objectives page ncludes a Call Out 1 block.
* Basic: Contains the bulk of book content. Basic pages are best used for presenting the general "lecture" or learning content of a book.
* Assignment: Grabs the students attention for an assignment or activity. It includes an Assessment Wide block by default.
* Summary: Wraps up the main learning points of a book.';
$string['editingchapter1'] = 'Editing Introduction Page';
$string['editingchapter2'] = 'Editing Learning Objectives Page';
$string['editingchapter3'] = 'Editing Basic Page';
$string['editingchapter4'] = 'Editing Assignment Page';
$string['editingchapter5'] = 'Editing Summary Page';
$string['alternatebooktitle'] = 'Custom Book Title';
$string['alternatebooktitle_help'] = 'Normally the book title is displayed as a heading above the content
 and in the breadcrumb navigation.

If a custom title is entered, it will replace the title displayed as a
 heading above the content. The title in the breadcrumb navigation will
 remain the same as the main book title.';
$string['addnewblock'] = 'Select a Block Type';
$string['addnewblock_help'] = 'Blocks in the NC State Book are used for structuring content, and to highlight certain types of content in a consistent way.
For example, you might have a "Go a Step Further" or "Bonus Points" or a "For Reflection" block. Content blocks are for basic page content. All other blocks
are for specialized content. It is best to always use these specialized blocks for a consistent purpose. While blocks are already titled, you should change
the title in these specialized block types to suit your needs.

Block Types

* Content: Used for creating paragraph content on pages. The title is not displayed on the page, but is used to identify different content blocks when designing your page.
* Learning Objectives: Right-justified, this block is 1/3 the width of the page. By default, this is included in the Introduction page to display the learning objectives for that book.
* Activity Wide: Full-width. Has a solid, darker-colored bar at the top where the title is displayed, and a lighter-colored background where content will go.
* Activity Narrow: Right-justified, this block is 1/3 the width of the page. Has a solid, darker-colored bar at the top where the title is displayed, and a lighter -colored background where content will go.
* Call Out 1: Right-justified, this block is 1/3 the width of the page. Has a solid, light-colored background where the block title and content will go.
* Call Out 2: Right-justified, this block is 1/3 the width of the page. Has a line on the left to visually separate the block title and content from the page content.
* Assessment Wide: Full-width. This block has a solid, darker-colored border around the block and a lighter background where the block title and content go.
* Assessment Narrow: Right-justified, this block is 1/3 the width of the page. This block has a solid, darker colored border around the block and a lighter background where the block title and content go.
';
$string['chaptertitle'] = 'Page Title';
$string['blocktitle'] = 'Block Title';
$string['blocktitle_help'] = 'Block titles are displayed at the top of each block, except in the case of content blocks. Here it is used only to help
organize multiple content blocks during editing.';
$string['content'] = 'Content';
$string['subchapter'] = 'Subpage';
$string['showparenttitle'] = 'Display the title of the parent page if this is a subpage';
$string['nocontent'] = 'No content has been added to this book yet.';
$string['numbering'] = 'Table of contents formatting';
$string['numbering_help'] = '* None - Page and subpage titles are listed in the order they appear and there is no difference in how they look
* Numbers - Page and subpage titles are numbered 1, 2, 3, etc for pages and #.1, #.2, #.3, etc for subpages where # is the number of the page they are below
* Bullets - Subpage titles are indented and displayed with bullets in the table of contents
* Indented - Subpage titles are indented in the table of contents';
$string['numbering0'] = 'None';
$string['numbering1'] = 'Numbers';
$string['numbering2'] = 'Bullets';
$string['numbering3'] = 'Indented';
$string['numberingoptions'] = 'Available options for page formatting';
$string['numberingoptions_desc'] = 'Options for displaying pages and subpages in the table of contents';
$string['addafter'] = 'Add new page';
$string['confchapterdelete'] = 'Do you really want to delete this page?';
$string['confchapterdeleteall'] = 'Do you really want to delete this page and all its subpages?';
$string['top'] = 'top';
$string['navprev'] = 'Previous';
$string['navnext'] = 'Next';
$string['navexit'] = 'Exit book';
$string['ncsubook:addinstance'] = 'Add a new NC State Book';
$string['ncsubook:read'] = 'Read NC State Book';
$string['ncsubook:edit'] = 'Edit NC State Book pages';
$string['ncsubook:viewhiddenchapters'] = 'View hidden NC State Book pages';
$string['errorchapter'] = 'Error reading page of book.';

$string['page-mod-ncsubook-x'] = 'Any book module page';
$string['subchapternotice'] = '(Only available once the first page has been created)';
$string['subplugintype_ncsubooktool'] = 'NC State Book tool';
$string['subplugintype_ncsubooktool_plural'] = 'NC State Book tools';

