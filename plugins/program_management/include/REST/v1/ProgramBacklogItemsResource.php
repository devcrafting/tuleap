<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\CrossReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TitleValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\URIRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\UserStory\IsOpenRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\FeatureNotAccessException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStoryRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;

final class ProgramBacklogItemsResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;
    public const  ROUTE     = 'program_backlog_items';

    /**
     * Get content of a feature
     *
     * In a feature, get all elements planned in team and linked to a program increment
     *
     * @url    GET {id}/children
     * @access hybrid
     *
     * @param int $id     Id of the feature
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return UserStoryRepresentation[]
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    public function getChildren(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user_manager                  = \UserManager::instance();
        $user_retriever                = new UserManagerAdapter($user_manager);
        $artifact_factory              = \Tracker_ArtifactFactory::instance();
        $artifact_retriever            = new ArtifactFactoryAdapter($artifact_factory);
        $tracker_of_artifact_retriever = new TrackerOfArtifactRetriever($artifact_retriever);

        $tracker_factory                   = \TrackerFactory::instance();
        $user_story_representation_builder = new UserStoryRetriever(
            new ArtifactsLinkedToParentDao(),
            new PlanDao(),
            new BackgroundColorRetriever(
                new BackgroundColorBuilder(new BindDecoratorRetriever()),
                $artifact_retriever,
                $user_retriever
            ),
            new VerifyIsVisibleFeatureAdapter($artifact_factory, $user_retriever),
            new TitleValueRetriever($artifact_retriever),
            new URIRetriever($artifact_retriever),
            new CrossReferenceRetriever($artifact_retriever),
            new IsOpenRetriever($artifact_retriever),
            $tracker_of_artifact_retriever,
            new ArtifactVisibleVerifier($artifact_factory, $user_retriever),
            $tracker_of_artifact_retriever
        );

        $user = $user_manager->getCurrentUser();
        try {
            $user_stories = $user_story_representation_builder->retrieveStories(
                $id,
                UserProxy::buildFromPFUser($user)
            );

            $linked_children = [];
            foreach ($user_stories as $user_story) {
                $user_story_representation = UserStoryRepresentation::build(
                    $tracker_factory,
                    $user_story
                );
                if ($user_story_representation) {
                    $linked_children[] = $user_story_representation;
                }
            }

            Header::sendPaginationHeaders($limit, $offset, count($linked_children), self::MAX_LIMIT);

            return array_slice($linked_children, $offset, $limit);
        } catch (FeatureIsNotPlannableException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        } catch (FeatureNotAccessException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }


    /**
     * @url OPTIONS {id}/children
     *
     * @param int $id Id of the feature
     */
    public function optionsContent(int $id): void
    {
        Header::allowOptionsGet();
    }
}
