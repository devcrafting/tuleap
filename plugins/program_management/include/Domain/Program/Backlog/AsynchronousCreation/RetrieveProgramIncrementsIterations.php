<?php

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

interface RetrieveProgramIncrementsIterations
{
    function retrieve(ProgramIncrementIdentifier $program_increment_id, UserIdentifier $user) : ProgramIncrementIterations;
}
