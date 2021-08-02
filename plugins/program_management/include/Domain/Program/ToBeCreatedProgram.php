<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program;

use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramIsATeamException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class ToBeCreatedProgram
{
    /**
     * @var int
     */
    private $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws ProgramAccessException
     * @throws ProgramIsATeamException
     */
    public static function fromId(BuildProgram $build_program, int $id, \PFUser $user): self
    {
        $build_program->ensureProgramIsProjectAndUserIsAdminOf($id, UserIdentifier::fromPFUser($user));

        return new self($id);
    }
}
