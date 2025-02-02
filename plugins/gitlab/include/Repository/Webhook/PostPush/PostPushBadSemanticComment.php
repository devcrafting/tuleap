<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Tuleap\Tracker\Artifact\Closure\BadSemanticCommentInCommonMarkFormat;

/**
 * @psalm-immutable
 */
final class PostPushBadSemanticComment implements BadSemanticCommentInCommonMarkFormat
{
    private function __construct(private string $comment)
    {
    }

    public static function fromUserClosingTheArtifact(UserClosingTheArtifact $committer_username): self
    {
        return new self(
            sprintf(
                '%s attempts to close this artifact from GitLab but neither done nor status semantic defined.',
                $committer_username->getName()
            )
        );
    }

    public function getBody(): string
    {
        return $this->comment;
    }
}
