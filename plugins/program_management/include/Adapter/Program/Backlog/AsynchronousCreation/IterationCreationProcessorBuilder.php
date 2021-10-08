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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\ChangesetRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProjectReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class IterationCreationProcessorBuilder implements BuildIterationCreationProcessor
{
    public function getProcessor(): ProcessIterationCreation
    {
        $logger               = \BackendLogger::getDefaultLogger('program_management_syslog');
        $artifact_factory     = \Tracker_ArtifactFactory::instance();
        $tracker_factory      = \TrackerFactory::instance();
        $form_element_factory = \Tracker_FormElementFactory::instance();
        $program_DAO          = new ProgramDao();
        $project_manager      = \ProjectManager::instance();
        $event_manager        = \EventManager::instance();
        $user_retriever       = new UserManagerAdapter(\UserManager::instance());
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $message_logger       = MessageLog::buildFromLogger($logger);
        $visibility_verifier  = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);

        $synchronized_fields_gatherer = new SynchronizedFieldsGatherer(
            $tracker_factory,
            new \Tracker_Semantic_TitleFactory(),
            new \Tracker_Semantic_DescriptionFactory(),
            new \Tracker_Semantic_StatusFactory(),
            new SemanticTimeframeBuilder(
                new SemanticTimeframeDao(),
                $form_element_factory,
                $tracker_factory,
                new LinksRetriever(
                    new ArtifactLinkFieldValueDao(),
                    $artifact_factory
                )
            ),
            $form_element_factory
        );

        $artifact_creator = new ArtifactCreatorAdapter(
            TrackerArtifactCreator::build(
                \Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
                \Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                $logger
            ),
            $tracker_factory,
            $user_retriever,
            new ChangesetValuesFormatter(
                new ArtifactLinkValueFormatter(),
                new DescriptionValueFormatter()
            )
        );

        $mirrors_creator = new IterationsCreator(
            $transaction_executor,
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            new StatusValueMapper($form_element_factory),
            $synchronized_fields_gatherer,
            $artifact_creator,
            new MirroredTimeboxesDao(),
            $visibility_verifier,
            new TrackerOfArtifactRetriever($artifact_factory, $tracker_factory),
            $message_logger
        );

        return new IterationCreationProcessor(
            $message_logger,
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever($artifact_factory, $form_element_factory),
            new ChangesetRetriever($artifact_factory),
            $program_DAO,
            new ProgramAdapter(
                $project_manager,
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    $event_manager
                ),
                $program_DAO,
                $user_retriever
            ),
            $program_DAO,
            new ProjectReferenceRetriever($project_manager),
            $mirrors_creator
        );
    }
}
