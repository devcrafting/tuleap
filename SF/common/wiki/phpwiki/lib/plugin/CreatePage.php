<?php // -*-php-*-
rcs_id('$Id$');
/**
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * This allows you to create a page geting the new pagename from a 
 * forms-based interface, and optionally with the initial content from 
 * some template.
 *
 * Put it <?plugin-form CreatePage ?> at some page, browse this page, 
 * enter the name of the page to create, then click the button.
 *
 * Usage: <?plugin-form CreatePage ?>
 * @author: Dan Frankowski
 */
class WikiPlugin_CreatePage
extends WikiPlugin
{
    function getName() {
        return _("CreatePage");
    }

    function getDescription() {
        return _("Create a Wiki page by the provided name.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array('s'            => false,
                     'initial_content' => false,
                     'template'     => false,
                     //'method'     => 'POST'
                     );
    }

    function run($dbi, $argstr, $request, $basepage) {
        extract($this->getArgs($argstr, $request));
        if (!$s)
            return '';
            
        // Prevent spaces at the start and end of a page name
        $s = trim($s);

        $param = array('action' => 'edit');
        if ($template and $dbi->isWikiPage($template)) {
            $param['template'] = $template;
        } elseif ($initial_content) { 
        // Warning! Potential URI overflow here on the GET redirect. Better use template.
            $param['initial_content'] = $initial_content;
        }
        // If the initial_content is too large, pre-save the content in the page 
        // and redirect without that argument.
        // URI length limit:
        //   http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.2.1
        $url = WikiURL($s, $param, 'absurl');
        if (strlen($url) > 255) {
            unset($param['initial_content']);
            $url = WikiURL($s, $param, 'absurl');
            $page = $dbi->getPage($s);
            $current = $page->getCurrentRevision();
            if ($current->getVersion()) {
                return $this->error(fmt("%s already exists",$s));
            } else {
                $user = $request->getUser();
                $meta = array('markup' => 2.0,
                              'author' => $user->getId());
                if ($param['template'] and !$initial_content) {
                    $tmplpage = $dbi->getPage($template);
                    $currenttmpl = $tmplpage->getCurrentRevision();
                    $initial_content = $currenttmpl->getPackedContent();
                    $meta['markup'] = $currenttmpl->_data['markup'];
                }
                $meta['summary'] = _("Created by CreatePage");
                $page->save($initial_content, 1, $meta);
            }
        }
        return HTML($request->redirect($url, true));
    }
};

// $Log$
// Revision 1.1  2005/04/12 13:33:33  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.4  2004/04/21 16:14:50  zorloc
// Prevent spaces at the start and end of a created page name -- submitted by Dan Frankowski (dfrankow).
//
// Revision 1.3  2004/03/24 19:41:04  rurban
// fixed the name
//
// Revision 1.2  2004/03/17 15:37:41  rurban
// properly support initial_content and template with URI length overflow workaround
//
// Revision 1.3  2004/03/16 16:25:05  dfrankow
// Support initial_content parameter
//
// Revision 1.2  2004/03/09 16:28:45  dfrankow
// Merge the RATING branch onto the main line
//
// Revision 1.1  2004/03/08 18:57:59  rurban
// Allow WikiForm overrides, such as method => POST, targetpage => [pagename]
// in the plugin definition.
// New simple CreatePage plugin by dfrankow.
//
// Revision 1.1.2.2  2004/02/23 21:22:29  dfrankow
// Add a little doc
//
// Revision 1.1.2.1  2004/02/21 15:29:19  dfrankow
// Allow a CreatePage edit box, as GUI syntactic sugar
//
// Revision 1.1.1.1  2004/01/29 14:30:28  dfrankow
// Right out of the 1.3.7 package
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
