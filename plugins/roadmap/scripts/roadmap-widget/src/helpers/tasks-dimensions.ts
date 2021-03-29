/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import type { Task, TaskDimension, TimePeriod } from "../type";
import { getLeftForDate } from "./left-postion";
import { Styles } from "./styles";

export function getDimensions(task: Task, time_period: TimePeriod): TaskDimension {
    const left = getLeftForTask(task, time_period);
    const width = getWidthForTask(task, time_period, left);

    return { left, width };
}

function getLeftForTask(task: Task, time_period: TimePeriod): number {
    if (task.start) {
        return getLeftForDate(task.start, time_period);
    }

    if (task.end) {
        return getLeftForDate(task.end, time_period);
    }

    return 0;
}

function getWidthForTask(task: Task, time_period: TimePeriod, left: number): number {
    if (task.start && task.end && task.start.toISOString() !== task.end.toISOString()) {
        return Math.max(
            getLeftForDate(task.end, time_period) - left,
            Styles.TASK_BAR_MIN_WIDTH_IN_PX
        );
    }

    return Styles.MILESTONE_WIDTH_IN_PX;
}
