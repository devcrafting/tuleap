<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Dao;

class TestManagementDataBuilder extends REST_TestDataBuilder
{
    const PROJECT_TEST_MGMT_SHORTNAME = 'test-mgmt';
    const ISSUE_TRACKER_SHORTNAME     = 'bugs';

    const USER_TESTER_NAME   = 'rest_api_ttm_1';
    const USER_TESTER_PASS   = 'welcome0';
    const USER_TESTER_STATUS = 'A';

    public function __construct() {
        parent::__construct();
        $this->instanciateFactories();

        $this->template_path   = dirname(__FILE__).'/_fixtures/';
    }

    public function setUp()
    {
        echo 'Setup TestManagement REST tests configuration';

        $this->installPlugin();
        $this->activatePlugin('testmanagement');

        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $user->setPassword(self::USER_TESTER_PASS);
        $this->user_manager->updateDb($user);

        $project  = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_MGMT_SHORTNAME);
        $trackers = $this->tracker_factory->getTrackersByGroupId($project->getID());

        foreach ($trackers as $tracker) {
            if ($tracker->getItemName() === 'campaign') {
                $campaign_tracker_id = $tracker->getId();
            } elseif ($tracker->getItemName() === 'test_def') {
                $test_def_tracker_id = $tracker->getId();
            } elseif ($tracker->getItemName() === 'test_exec') {
                $test_exec_tracker_id = $tracker->getId();
            } elseif ($tracker->getItemName() === 'bugs') {
                $issue_tracker_id = $tracker->getId();
            }
        }

        $this->configureTestManagementPluginForProject(
            $project,
            $campaign_tracker_id,
            $test_def_tracker_id,
            $test_exec_tracker_id,
            $issue_tracker_id
        );
    }

    private function configureTestManagementPluginForProject(
        Project $project,
        $campaign_tracker_id,
        $test_def_tracker_id,
        $test_exec_tracker_id,
        $issue_tracker_id
    ) {
        $config = new Config(new Dao());
        $config->setProjectConfiguration($project, $campaign_tracker_id, $test_def_tracker_id, $test_exec_tracker_id, $issue_tracker_id);
    }

    private function installPlugin() {
        $dbtables = new DBTablesDAO();
        $dbtables->updateFromFile(dirname(__FILE__).'/../../db/install.sql');
    }
}
