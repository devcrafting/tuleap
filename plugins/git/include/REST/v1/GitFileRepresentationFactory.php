<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use Tuleap\Git\Exceptions\GitRepoRefNotFoundException;
use Tuleap\Git\GitPHP\Pack;
use Tuleap\Git\GitPHP\ProjectProvider;

class GitFileRepresentationFactory
{

    /**
     *
     * @return GitFileContentRepresentation
     * @throws \GitRepositoryException
     * @throw GitRepoRefNotFoundException
     */
    public function getGitFileRepresentation($path_to_file, $ref, \GitRepository $git_repository)
    {
        $name     = basename($path_to_file);
        $provider = new ProjectProvider($git_repository);
        $project  = $provider->GetProject();
        $commit   = $project->GetCommit($ref);
        if ($commit === null) {
            throw new \GitRepositoryException(sprintf('Commit for the reference \'%s\' not found', $ref));
        }
        $hash            = $commit->PathToHash($path_to_file);
        $data            = $project->GetObject($hash, $type_int);
        $encoded_content = base64_encode($data);
        if (Pack::OBJ_BLOB !== $type_int) {
            throw new \GitRepositoryException(sprintf('\'%s\' is not a file', $name));
        }
        $file = $project->GetBlob($hash);
        $size = $file->GetSize();

        $git_file = new GitFileContentRepresentation();
        $git_file->build($name, $path_to_file, $encoded_content, $size);
        return $git_file;
    }
}
