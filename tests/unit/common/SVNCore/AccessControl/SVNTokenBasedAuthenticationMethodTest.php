<?php
/**
 * Copyright (c) Enalean 2022-Present. All rights reserved
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

namespace Tuleap\SVNCore\AccessControl;

use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class SVNTokenBasedAuthenticationMethodTest extends TestCase
{
    /**
     * @var \SVN_TokenHandler&\PHPUnit\Framework\MockObject\Stub
     */
    private $token_handler;
    private SVNTokenBasedAuthenticationMethod $auth_method;

    protected function setUp(): void
    {
        $this->token_handler = $this->createStub(\SVN_TokenHandler::class);
        $this->auth_method   = new SVNTokenBasedAuthenticationMethod($this->token_handler, new NullLogger());
    }

    public function testAuthenticationCanBeSuccessful(): void
    {
        $this->token_handler->method('isTokenValid')->willReturn(true);
        $is_auth = $this->auth_method->isAuthenticated(UserTestBuilder::anActiveUser()->build(), new ConcealedString('valid_token'), new NullServerRequest());

        self::assertTrue($is_auth);
    }

    public function testAuthenticationIsRejectedWhenTokenIsNotValid(): void
    {
        $this->token_handler->method('isTokenValid')->willReturn(false);
        $is_auth = $this->auth_method->isAuthenticated(UserTestBuilder::anActiveUser()->build(), new ConcealedString('incorrect_token'), new NullServerRequest());

        self::assertFalse($is_auth);
    }
}
