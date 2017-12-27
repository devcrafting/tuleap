<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Agiledashboard\FormElement;

use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;

class BurnupCacheChecker
{
    /**
     * @var BurnupCacheGenerator
     */
    private $cache_generator;
    /**
     * @var ChartConfigurationValueChecker
     */
    private $chart_value_checker;
    /**
     * @var BurnupCacheDao
     */
    private $burnup_cache_dao;
    /**
     * @var ChartCachedDaysComparator
     */
    private $cache_days_comparator;

    public function __construct(
        BurnupCacheGenerator $cache_generator,
        ChartConfigurationValueChecker $chart_value_checker,
        BurnupCacheDao $burnup_cache_dao,
        ChartCachedDaysComparator $cache_days_comparator
    ) {
        $this->cache_generator       = $cache_generator;
        $this->chart_value_checker   = $chart_value_checker;
        $this->burnup_cache_dao      = $burnup_cache_dao;
        $this->cache_days_comparator = $cache_days_comparator;
    }

    public function isBurnupUnderCalculation(Tracker_Artifact $artifact, TimePeriodWithoutWeekEnd $time_period, \PFUser $user)
    {
        $is_burnup_under_calculation = false;

        if ($this->isCacheCompleteForBurnup($artifact, $time_period, $user) === false
            && $this->cache_generator->isCacheBurnupAlreadyAsked($artifact) === false
        ) {
            $this->cache_generator->forceBurnupCacheGeneration($artifact->getId());
            $is_burnup_under_calculation = true;
        } else if ($this->cache_generator->isCacheBurnupAlreadyAsked($artifact)) {
            $is_burnup_under_calculation = true;
        }

        return $is_burnup_under_calculation;
    }

    private function isCacheCompleteForBurnup(
        Tracker_Artifact $artifact,
        TimePeriodWithoutWeekEnd $time_period,
        \PFUser $user
    ) {
        if ($this->chart_value_checker->hasStartDate($artifact, $user)) {
            $cached_days = $this->burnup_cache_dao->getCachedDays(
                $artifact->getId()
            );

            return $this->cache_days_comparator->isNumberOfCachedDaysExpected($time_period, $cached_days['cached_days']);
        }

        return true;
    }
}
