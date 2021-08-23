<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\GatherFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;

final class FieldValuesGathererRetriever implements RetrieveFieldValuesGatherer
{
    private \Tracker_ArtifactFactory $artifact_factory;

    public function __construct(\Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    public function getFieldValuesGatherer(ReplicationData $replication): GatherFieldValues
    {
        $program_increment_id = $replication->getArtifact()->getId();
        $full_artifact        = $this->artifact_factory->getArtifactById($program_increment_id);
        if (! $full_artifact) {
            throw new PendingArtifactNotFoundException($program_increment_id, (int) $replication->getUser()->getId());
        }
        $changeset_id   = $replication->getChangeset()->getId();
        $full_changeset = $full_artifact->getChangeset($changeset_id);
        if (! $full_changeset) {
            throw new PendingArtifactChangesetNotFoundException($program_increment_id, $changeset_id);
        }
        return new ArtifactFieldValuesRetriever($full_changeset);
    }
}
