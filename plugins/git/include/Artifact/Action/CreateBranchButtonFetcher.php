<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\Artifact\Action;

use ForgeConfig;
use GitRepositoryFactory;
use PFUser;
use Project;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Git\REST\v1\Branch\BranchNameCreatorFromArtifact;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;
use Tuleap\Tracker\Artifact\Artifact;

final class CreateBranchButtonFetcher
{
    #[FeatureFlagConfigKey("Feature flag to allow users to create Git branches from artifacts")]
    public const FEATURE_FLAG_KEY = 'artifact-create-git-branches';

    public function __construct(
        private GitRepositoryFactory $git_repository_factory,
        private BranchNameCreatorFromArtifact $branch_name_creator_from_artifact,
        private JavascriptAssetGeneric $javascript_asset,
    ) {
    }

    public function getActionButton(Artifact $artifact, PFUser $user): ?AdditionalButtonAction
    {
        if (! ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY)) {
            return null;
        }

        $project = $artifact->getTracker()->getProject();
        if (! $this->doesProjectHaveRepositoriesUserCanRead($project, $user)) {
            return null;
        }

        $link_label = dgettext('tuleap-git', 'Create Git branch');
        $icon       = 'fas fa-code-branch';
        $link       = new AdditionalButtonLinkPresenter(
            $link_label,
            "",
            "",
            $icon,
            'artifact-create-git-branches',
            [
                [
                    'name'  => "project-id",
                    'value' => (string) $project->getID(),
                ],
                [
                    "name" => "git-branch-name-preview",
                    "value" => $this->branch_name_creator_from_artifact->getBaseBranchName($artifact),
                ],
            ],
        );

        return new AdditionalButtonAction(
            $link,
            $this->javascript_asset->getFileURL()
        );
    }

    private function doesProjectHaveRepositoriesUserCanRead(Project $project, PFUser $user): bool
    {
        foreach ($this->git_repository_factory->getAllRepositories($project) as $repository) {
            if ($repository->userCanRead($user)) {
                return true;
            }
        }

        return false;
    }
}
