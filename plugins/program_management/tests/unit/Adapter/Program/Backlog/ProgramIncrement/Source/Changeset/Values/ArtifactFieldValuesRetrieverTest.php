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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\UnsupportedTitleFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;

final class ArtifactFieldValuesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private function getRetriever(): ArtifactFieldValuesRetriever
    {
        return new ArtifactFieldValuesRetriever();
    }

    public function dataProviderMethodUnderTest(): array
    {
        return [
            'when title value is not found'       => [fn(
                ReplicationData $replication,
                SynchronizedFields $fields
            ) => $this->getRetriever()->getTitleValue($replication, $fields)],
            'when description value is not found' => [fn(
                ReplicationData $replication,
                SynchronizedFields $fields
            ) => $this->getRetriever()->getDescriptionValue($replication, $fields)],
            'when start date value is not found'  => [fn(
                ReplicationData $replication,
                SynchronizedFields $fields
            ) => $this->getRetriever()->getStartDateValue($replication, $fields)]
        ];
    }

    /**
     * @dataProvider dataProviderMethodUnderTest
     */
    public function testItThrowsWhenChangesetValuesAreNotFound(callable $method_under_test): void
    {
        $fields           = SynchronizedFieldsBuilder::build();
        $source_changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $source_changeset->method('getValue')->willReturn(null);
        $source_changeset->method('getId')->willReturn(1);
        $replication = ReplicationDataBuilder::buildWithChangeset($source_changeset);

        $this->expectException(ChangesetValueNotFoundException::class);
        $method_under_test($replication, $fields);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $fields           = SynchronizedFieldsBuilder::build();
        $changeset_value  = $this->createStub(\Tracker_Artifact_ChangesetValue_Text::class);
        $source_changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $source_changeset->method('getValue')->willReturn($changeset_value);
        $replication = ReplicationDataBuilder::buildWithChangeset($source_changeset);

        $this->expectException(UnsupportedTitleFieldException::class);
        $this->getRetriever()->getTitleValue($replication, $fields);
    }

    public function testItReturnsTitleValueFromReplicationAndSynchronizedFields(): void
    {
        $fields          = SynchronizedFieldsBuilder::build();
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_String::class);
        $changeset_value->method('getValue')->willReturn('My title');
        $source_changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $source_changeset->method('getValue')->willReturn($changeset_value);
        $replication = ReplicationDataBuilder::buildWithChangeset($source_changeset);

        self::assertSame('My title', $this->getRetriever()->getTitleValue($replication, $fields));
    }

    public function testItReturnsDescriptionValueFromReplicationAndSynchronizedFields(): void
    {
        $fields          = SynchronizedFieldsBuilder::build();
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Text::class);
        $changeset_value->method('getValue')->willReturn('My description');
        $changeset_value->method('getFormat')->willReturn('text');
        $source_changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $source_changeset->method('getValue')->willReturn($changeset_value);
        $relication = ReplicationDataBuilder::buildWithChangeset($source_changeset);

        $text_value = $this->getRetriever()->getDescriptionValue($relication, $fields);
        self::assertSame('My description', $text_value->getValue());
        self::assertSame('text', $text_value->getFormat());
    }

    public function testItReturnsStartDateValueFromReplicationAndSynchronizedFields(): void
    {
        $fields          = SynchronizedFieldsBuilder::build();
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Date::class);
        $changeset_value->method('getDate')->willReturn('2020-10-01');
        $source_changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $source_changeset->method('getValue')->willReturn($changeset_value);
        $replication = ReplicationDataBuilder::buildWithChangeset($source_changeset);

        self::assertSame('2020-10-01', $this->getRetriever()->getStartDateValue($replication, $fields));
    }
}
