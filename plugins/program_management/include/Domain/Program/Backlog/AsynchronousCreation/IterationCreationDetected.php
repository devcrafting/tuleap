<?php

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class IterationCreationDetected
{
    public function __construct(
        public IterationIdentifier        $iteration,
        public IterationTrackerIdentifier $tracker,
        public ProgramIncrementIdentifier $program_increment,
        public UserIdentifier             $user,
        public ChangesetIdentifier        $changeset,
    )
    {
    }
}
