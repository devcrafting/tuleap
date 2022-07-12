<?php

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveProgramIncrementsIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIterationHasBeenLinkedBefore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

class ProgramIncrementIterationsRetriever implements RetrieveProgramIncrementsIterations
{
    private SearchIterations $iteration_searcher;
    private VerifyIsVisibleArtifact $visibility_verifier;

    public function __construct(
        SearchIterations $iteration_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        private VerifyIterationHasBeenLinkedBefore $link_verifier)
    {
        $this->iteration_searcher = $iteration_searcher;
        $this->visibility_verifier = $visibility_verifier;
    }

    function retrieve(ProgramIncrementIdentifier $program_increment_id, UserIdentifier $user): ProgramIncrementIterations
    {
        $iterations = [];
        foreach ($this->iteration_searcher->searchIterations($program_increment_id) as $id)
        {
            $visible = $this->visibility_verifier->isVisible($id, $user);
            $iteration_id = new IterationIdentifier($id);
            $linked_before = $this->link_verifier->hasIterationBeenLinkedBefore(
                $program_increment_id,
                $iteration_id
            );
            // $last_changeset_id = ...
            $iterations[] = new ProgramIncrementIteration($iteration_id, !$linked_before, $visible);
        }
        return new ProgramIncrementIterations($iterations);
    }
}
