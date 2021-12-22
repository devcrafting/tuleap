<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use DOMDocument;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentNameGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraServerChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ListFieldChangeInitialValueRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLValueEnhancer;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraCloudCommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraServerCommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\DataChangesetXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ChangelogSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\CurrentSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\InitialSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Permissions\PermissionsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportAllIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportCreatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDefaultCriteriaExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDoneIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportOpenIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportTableExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportUpdatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlTQLReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\StoryPointFieldExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserInfoQuerier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClientReplay;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\XML\XMLTracker;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

class JiraXmlExporterTest extends TestCase
{
    use ForgeConfigSandbox;

    public function debugTracesProvider(): iterable
    {
        yield 'SBX' => [
            'fixtures_path' => __DIR__ . '/_fixtures/SBX',
            'is_jira_cloud' => false,
            'users' => [
                'john.doe@example.com' => UserTestBuilder::anActiveUser()->withId(101)->withUserName('john_doe')->build(),
            ],
            'jira_issue_key' => 'SBX',
            'jira_issue_type_id' => '10102',
            'tests' => function (\SimpleXMLElement $tracker_xml) {
                self::assertStringEqualsFile(__DIR__ . '/_fixtures/SBX/tracker.xml', self::getTidyXML($tracker_xml));
            },
        ];

        yield 'IXMC' => [
            'fixtures_path' => __DIR__ . '/_fixtures/IXMC',
            'is_jira_cloud' => false,
            'users' => [
                'user_1@example.com' => UserTestBuilder::anActiveUser()->withId(101)->withUserName('user_1')->build(),
                'user_2@example.com' => UserTestBuilder::anActiveUser()->withId(102)->withUserName('user_2')->build(),
                'user_3@example.com' => UserTestBuilder::anActiveUser()->withId(103)->withUserName('user_3')->build(),
                'user_4@example.com' => UserTestBuilder::anActiveUser()->withId(104)->withUserName('user_4')->build(),
                'user_5@example.com' => UserTestBuilder::anActiveUser()->withId(104)->withUserName('user_5')->build(),
            ],
            'jira_issue_key' => 'IXMC',
            'jira_issue_type_id' => '10105',
            'tests' => function (\SimpleXMLElement $tracker_xml) {
                self::assertStringEqualsFile(__DIR__ . '/_fixtures/IXMC/tracker.xml', self::getTidyXML($tracker_xml));
            },
        ];

        yield 'SP' => [
            'fixtures_path' => __DIR__ . '/_fixtures/SP',
            'is_jira_cloud' => true,
            'users' => [
                'manuel.vacelet@example.com' => UserTestBuilder::anActiveUser()->withId(101)->withUserName('manuel_vacelet')->build(),
                'thomas.cottier@example.com' => UserTestBuilder::anActiveUser()->withId(102)->withUserName('thomas_cottier')->build(),
                'manon.midy@example.com' => UserTestBuilder::anActiveUser()->withId(103)->withUserName('manon_midy')->build(),
                'thomas.gorka@example.com' => UserTestBuilder::anActiveUser()->withId(104)->withUserName('thomas_gorka')->build(),
            ],
            'jira_issue_key' => 'SP',
            'jira_issue_type_id' => '10001',
            'tests' => function (\SimpleXMLElement $tracker_xml) {
                self::assertStringEqualsFile(__DIR__ . '/_fixtures/SP/tracker.xml', self::getTidyXML($tracker_xml));
            },
        ];
    }

    /**
     * @dataProvider debugTracesProvider
     */
    public function testImportFromDebugTraces(string $fixture_path, bool $is_jira_cloud, array $users, string $jira_issue_key, string $jira_issue_type_id, callable $tests): void
    {
        $root = vfsStream::setup();

        \ForgeConfig::set('tmp_dir', $root->url());

        $logger = new NullLogger();

        if ($is_jira_cloud) {
            $jira_client               = JiraClientReplay::buildJiraCloud($fixture_path);
            $changelog_entries_builder = new JiraCloudChangelogEntriesBuilder(
                $jira_client,
                $logger
            );
            $comment_values_builder    = new JiraCloudCommentValuesBuilder(
                $jira_client,
                $logger
            );
        } else {
            $jira_client               = JiraClientReplay::buildJiraServer($fixture_path);
            $changelog_entries_builder = new JiraServerChangelogEntriesBuilder(
                $jira_client,
                $logger
            );
            $comment_values_builder    = new JiraServerCommentValuesBuilder(
                $jira_client,
                $logger
            );
        }

        $error_collector = new ErrorCollector();

        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $field_xml_exporter = new FieldXmlExporter(
            new XML_SimpleXMLCDATAFactory(),
            new FieldNameFormatter()
        );

        $jira_field_mapper         = new JiraToTuleapFieldTypeMapper($field_xml_exporter, $error_collector, $logger);
        $report_table_exporter     = new XmlReportTableExporter();
        $default_criteria_exporter = new XmlReportDefaultCriteriaExporter();

        $tql_report_exporter = new XmlTQLReportExporter(
            $default_criteria_exporter,
            $cdata_factory,
            $report_table_exporter
        );

        $creation_state_list_value_formatter = new CreationStateListValueFormatter();
        $user_manager                        = $this->createMock(\UserManager::class);

        $forge_user = UserTestBuilder::buildWithId(TrackerImporterUser::ID);

        $user_manager->method('getAllUsersByEmail')->willReturnCallback(function ($email) use ($users) {
            if (isset($users[$email])) {
                return [$users[$email]];
            }
            throw new \Exception('User email ' . $email . ' is missing in test setup');
        });
        $user_manager->method('getUserById')->willReturnCallback(function ($id) use ($forge_user, $users) {
            if ($id == TrackerImporterUser::ID) {
                return $forge_user;
            }
            foreach ($users as $user) {
                if ($user->getId() == $id) {
                    return $user;
                }
            }
            throw new \Exception('User id ' . $id . ' is missing in test setup');
        });

        $jira_user_on_tuleap_cache = new JiraUserOnTuleapCache(
            new JiraTuleapUsersMapping(),
            $forge_user
        );

        $jira_user_retriever = new JiraUserRetriever(
            $logger,
            $user_manager,
            $jira_user_on_tuleap_cache,
            new JiraUserInfoQuerier(
                $jira_client,
                $logger
            ),
            $forge_user
        );

        $attachment_name_generator = new class implements AttachmentNameGenerator {

            private int $i = 0;

            public function getName(): string
            {
                return 'file_' . $this->i++;
            }
        };

        $exporter = new JiraXmlExporter(
            $logger,
            $jira_client,
            $error_collector,
            new JiraFieldRetriever($jira_client, $logger),
            $jira_field_mapper,
            $jira_user_retriever,
            new XmlReportExporter(),
            new PermissionsXMLExporter(),
            new ArtifactsXMLExporter(
                $jira_client,
                $user_manager,
                new DataChangesetXMLExporter(
                    new XML_SimpleXMLCDATAFactory(),
                    new FieldChangeXMLExporter(
                        $logger,
                        new FieldChangeDateBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeStringBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeTextBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeFloatBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeListBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                            new UserXMLExporter(
                                $user_manager,
                                new UserXMLExportedCollection(
                                    new XML_RNGValidator(),
                                    new XML_SimpleXMLCDATAFactory()
                                )
                            )
                        ),
                        new FieldChangeFileBuilder(),
                        new FieldChangeArtifactLinksBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                        ),
                    ),
                    new IssueSnapshotCollectionBuilder(
                        $changelog_entries_builder,
                        new CurrentSnapshotBuilder(
                            $logger,
                            $creation_state_list_value_formatter,
                            $jira_user_retriever
                        ),
                        new InitialSnapshotBuilder(
                            $logger,
                            new ListFieldChangeInitialValueRetriever(
                                $creation_state_list_value_formatter,
                                $jira_user_retriever
                            )
                        ),
                        new ChangelogSnapshotBuilder(
                            $creation_state_list_value_formatter,
                            $logger,
                            $jira_user_retriever
                        ),
                        $comment_values_builder,
                        $logger,
                        $jira_user_retriever
                    ),
                    new CommentXMLExporter(
                        new XML_SimpleXMLCDATAFactory(),
                        new CommentXMLValueEnhancer()
                    ),
                    $logger
                ),
                new AttachmentCollectionBuilder(),
                new AttachmentXMLExporter(
                    new AttachmentDownloader($jira_client, $logger, $attachment_name_generator),
                    new XML_SimpleXMLCDATAFactory()
                ),
                $logger
            ),
            new SemanticsXMLExporter(),
            new ContainersXMLCollectionBuilder(),
            new AlwaysThereFieldsExporter(
                $field_xml_exporter
            ),
            new StoryPointFieldExporter(
                $field_xml_exporter,
                $logger,
            ),
            new XmlReportAllIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter
            ),
            new XmlReportOpenIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportDoneIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportCreatedRecentlyExporter($tql_report_exporter),
            new XmlReportUpdatedRecentlyExporter($tql_report_exporter),
            new \EventManager(),
        );

        $platform_configuration_collection = new PlatformConfiguration();

        $tracker_for_export = (new XMLTracker('T200', 'bug'))
            ->withName('Bugs')
            ->withDescription('Bug')
            ->withColor(TrackerColor::default());

        $xml          = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $trackers_xml = $xml->addChild('trackers');
        $tracker_xml  = $tracker_for_export->export($trackers_xml);

        $exporter->exportJiraToXml(
            $platform_configuration_collection,
            $tracker_xml,
            'https://jira.example.com',
            $jira_issue_key,
            new IssueType($jira_issue_type_id, 'Bogue', false),
            new FieldAndValueIDGenerator(),
            new LinkedIssuesCollection(),
        );

        // Uncomment below to update the fixture
        //$tidy_xml = self::getTidyXML($tracker_xml, );
        //file_put_contents($fixture_path . '/tracker.xml', $tidy_xml);

        $tests($tracker_xml);
    }

    private static function getTidyXML(\SimpleXMLElement $xml): string
    {
        $domxml                     = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput       = true;
        $domxml->loadXML($xml->asXML());
        return $domxml->saveXML();
    }
}
