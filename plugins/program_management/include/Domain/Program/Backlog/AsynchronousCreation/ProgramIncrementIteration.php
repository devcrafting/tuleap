<?php

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;

class ProgramIncrementIteration
{
    public function __construct(public IterationIdentifier $iteration, public bool $just_linked, public bool $is_visible)
    {
    }
}
