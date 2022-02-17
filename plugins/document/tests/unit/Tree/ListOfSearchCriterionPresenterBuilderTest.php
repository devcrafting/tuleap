<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Docman_SettingsBo;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Test\PHPUnit\TestCase;

class ListOfSearchCriterionPresenterBuilderTest extends TestCase
{
    public function testItShouldAlwaysReturnTypeCriterion(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([]);
        $metadata_factory->method('appendAllListOfValues');

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $status_mapper = new ItemStatusMapper($docman_settings);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $status_mapper, $project);

        self::assertCount(1, $criteria);
        self::assertEquals('type', $criteria[0]->name);
        self::assertEquals(
            ['folder', 'file', 'embedded', 'wiki', 'empty'],
            array_map(
                static fn(SearchCriterionListOptionPresenter $option): string => $option->value,
                $criteria[0]->options
            )
        );
    }

    public function testItShouldOmitWikiTypeIfProjectDoesNotUseWikiService(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([]);
        $metadata_factory->method('appendAllListOfValues');

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(false);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $status_mapper = new ItemStatusMapper($docman_settings);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $status_mapper, $project);

        self::assertCount(1, $criteria);
        self::assertEquals('type', $criteria[0]->name);
        self::assertEquals(
            ['folder', 'file', 'embedded', 'empty'],
            array_map(
                static fn(SearchCriterionListOptionPresenter $option): string => $option->value,
                $criteria[0]->options
            )
        );
    }

    /**
     * @testWith ["title"]
     *           ["description"]
     *           ["owner"]
     *           ["create_date"]
     *           ["update_date"]
     *           ["obsolescence_date"]
     */
    public function testItShouldReturnCriteriaBasedOnSupportedMetadata(string $metadata_name): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setSpecial(true);
        $metadata->setLabel($metadata_name);
        $metadata->setName($metadata_name);
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);
        $metadata_factory->method('appendAllListOfValues');

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $status_mapper = new ItemStatusMapper($docman_settings);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $status_mapper, $project);

        self::assertCount(2, $criteria);
        self::assertEquals($metadata_name, $criteria[1]->name);
    }

    public function testItShouldReturnHardcodedStatusMetadata(): void
    {
        $metadata = new \Docman_ListMetadata();
        $metadata->setSpecial(true);
        $metadata->setLabel('status');
        $metadata->setName('status');

        $none = new \Docman_MetadataListOfValuesElement();
        $none->setId(100);
        $none->setStatus('P');
        $none->setName('None');

        $draft = new \Docman_MetadataListOfValuesElement();
        $draft->setId(101);
        $draft->setStatus('A');
        $draft->setName('Draft');

        $deleted = new \Docman_MetadataListOfValuesElement();
        $deleted->setId(102);
        $deleted->setStatus('D');
        $deleted->setName('Whatever');

        $elements = [$none, $draft, $deleted];
        $metadata->setListOfValueElements($elements);

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $status_mapper = new ItemStatusMapper($docman_settings);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);
        $metadata_factory->method('appendAllListOfValues');

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $status_mapper, $project);

        self::assertCount(2, $criteria);
        self::assertEquals('status', $criteria[1]->name);
        self::assertEquals(
            ['none', 'draft'],
            array_map(
                static fn(SearchCriterionListOptionPresenter $option): string => $option->value,
                $criteria[1]->options
            )
        );
    }

    public function testItShouldReturnCustomTextMetadataAsWell(): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setSpecial(false);
        $metadata->setLabel('field_2');
        $metadata->setName('whatever');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $status_mapper = new ItemStatusMapper($docman_settings);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);
        $metadata_factory->method('appendAllListOfValues');

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $status_mapper, $project);

        self::assertCount(2, $criteria);
        self::assertEquals('field_2', $criteria[1]->name);
    }

    public function testItShouldReturnCustomListMetadataAsWell(): void
    {
        $metadata = new \Docman_ListMetadata();
        $metadata->setSpecial(false);
        $metadata->setLabel('field_2');
        $metadata->setName('whatever');

        $none = new \Docman_MetadataListOfValuesElement();
        $none->setId(100);
        $none->setStatus('P');
        $none->setName('None');

        $draft = new \Docman_MetadataListOfValuesElement();
        $draft->setId(101);
        $draft->setStatus('A');
        $draft->setName('Draft');

        $deleted = new \Docman_MetadataListOfValuesElement();
        $deleted->setId(102);
        $deleted->setStatus('D');
        $deleted->setName('Whatever');

        $elements = [$none, $draft, $deleted];
        $metadata->setListOfValueElements($elements);

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $status_mapper = new ItemStatusMapper($docman_settings);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);
        $metadata_factory->method('appendAllListOfValues');

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $status_mapper, $project);

        self::assertCount(2, $criteria);
        self::assertEquals('field_2', $criteria[1]->name);
    }
}
