<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline;

use PFUser;

class BaselineArtifactService
{
    /** @var BaselineArtifactRepository */
    private $baseline_artifact_repository;

    public function __construct(BaselineArtifactRepository $baseline_artifact_repository)
    {
        $this->baseline_artifact_repository = $baseline_artifact_repository;
    }

    /**
     * @return array BaselineArtifact[] All artifacts directly linked to given baseline's artifact, as they were at
     * baseline's snapshot date.
     * @throws BaselineArtifactNotFoundException
     */
    public function findFirstLevelByBaseline(PFUser $current_user, Baseline $baseline): array
    {
        $baseline_artifact = $this->baseline_artifact_repository->findByIdAt(
            $current_user,
            $baseline->getArtifact()->getId(),
            $baseline->getSnapshotDate()
        );
        if ($baseline_artifact === null) {
            throw new BaselineArtifactNotFoundException();
        }
        return $this->findByBaselineAndIds(
            $current_user,
            $baseline,
            $baseline_artifact->getLinkedArtifactIds()
        );
    }

    /**
     * @return array BaselineArtifact[] Artifacts with given ids, as they were at baseline's snapshot date.
     */
    public function findByBaselineAndIds(PFUser $current_user, ?Baseline $baseline, array $artifact_ids): array
    {
        return array_map(
            function (int $id) use ($current_user, $baseline) {
                return $this->baseline_artifact_repository->findByIdAt(
                    $current_user,
                    $id,
                    $baseline->getSnapshotDate()
                );
            },
            $artifact_ids
        );
    }
}
