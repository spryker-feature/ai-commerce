<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Session;

use Codeception\Test\Unit;
use Spryker\Client\Session\SessionClientInterface;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Session\StorefrontAssistantStreamSessionBinder;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Session
 * @group StorefrontAssistantStreamSessionBinderTest
 */
class StorefrontAssistantStreamSessionBinderTest extends Unit
{
    protected const string SESSION_KEY_CURRENCY = 'current-currency-iso-code';

    protected const string CURRENCY_ISO_CODE = 'CHF';

    protected const string SESSION_KEY_STREAM_WRITE = 'stream-write';

    protected AiCommerceYvesTester $tester;

    protected function _after(): void
    {
        parent::_after();

        $this->getSessionClient()->setContainer(new Session(new MockArraySessionStorage()));
    }

    public function testGivenASessionSnapshotWhenItIsBoundThenTheSessionClientServesItsAttributes(): void
    {
        // Arrange
        $storefrontAssistantStreamSessionBinder = $this->createStorefrontAssistantStreamSessionBinder();

        // Act
        $storefrontAssistantStreamSessionBinder->bindSessionAttributes([
            static::SESSION_KEY_CURRENCY => static::CURRENCY_ISO_CODE,
        ]);

        // Assert
        $this->assertSame(static::CURRENCY_ISO_CODE, $this->getSessionClient()->get(static::SESSION_KEY_CURRENCY));
    }

    public function testGivenASessionSnapshotWhenItIsBoundThenNoNativeSessionIsStarted(): void
    {
        // Arrange
        $storefrontAssistantStreamSessionBinder = $this->createStorefrontAssistantStreamSessionBinder();

        // Act
        $storefrontAssistantStreamSessionBinder->bindSessionAttributes([
            static::SESSION_KEY_CURRENCY => static::CURRENCY_ISO_CODE,
        ]);
        $this->getSessionClient()->set(static::SESSION_KEY_STREAM_WRITE, true);

        // Assert
        $this->assertNotSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testGivenAStreamSessionWithStaleAttributesWhenASnapshotIsBoundThenOnlyTheSnapshotAttributesRemain(): void
    {
        // Arrange
        $streamSession = $this->createStreamSession();
        $streamSession->set(static::SESSION_KEY_STREAM_WRITE, true);

        // Act
        (new StorefrontAssistantStreamSessionBinder($this->getSessionClient(), $streamSession))->bindSessionAttributes([
            static::SESSION_KEY_CURRENCY => static::CURRENCY_ISO_CODE,
        ]);

        // Assert
        $this->assertSame(
            [static::SESSION_KEY_CURRENCY => static::CURRENCY_ISO_CODE],
            $this->getSessionClient()->all(),
        );
    }

    protected function createStorefrontAssistantStreamSessionBinder(): StorefrontAssistantStreamSessionBinder
    {
        return new StorefrontAssistantStreamSessionBinder($this->getSessionClient(), $this->createStreamSession());
    }

    protected function createStreamSession(): SessionInterface
    {
        return new Session(new MockArraySessionStorage());
    }

    protected function getSessionClient(): SessionClientInterface
    {
        return $this->tester->getLocator()->session()->client();
    }
}
