<?php

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

class ProgramIncrementIterations
{
    /*
     * @var list<ProgramIncrementIteration>
     */
    private array $iterations;

    public function __construct(ProgramIncrementIteration ...$iterations)
    {
        $this->iterations = $iterations;
    }

    public function detectNewIterationsCreation(): array {
        $detected_iterations = [];
        foreach ($this->iterations as $iteration)
        {
            if($iteration->just_linked && $iteration->is_visible) {
                $detected_iterations[] = new IterationCreationDetected();
            }
        }
        return $detected_iterations;
    }
}
