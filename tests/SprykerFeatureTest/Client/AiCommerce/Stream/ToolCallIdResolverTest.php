<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Stream;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallIdResolver;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Stream
 * @group ToolCallIdResolverTest
 */
class ToolCallIdResolverTest extends Unit
{
    protected const string TOOL_NAME = 'display_products';

    protected const string PROVIDER_TOOL_CALL_ID = 'call_provider_generated_1';

    protected const string SYNTHETIC_TOOL_CALL_ID_PREFIX = 'call_';

    public function testGivenProviderToolCallIdWhenResolvedThenItIsReturnedUnchanged(): void
    {
        // Arrange
        $toolCallIdResolver = new ToolCallIdResolver();

        // Act
        $toolCallId = $toolCallIdResolver->resolveToolCallId(static::TOOL_NAME, static::PROVIDER_TOOL_CALL_ID);

        // Assert
        $this->assertSame(static::PROVIDER_TOOL_CALL_ID, $toolCallId);
    }

    public function testGivenNoProviderToolCallIdWhenResolvedThenASyntheticIdentifierIsReturned(): void
    {
        // Arrange
        $toolCallIdResolver = new ToolCallIdResolver();

        // Act
        $toolCallId = $toolCallIdResolver->resolveToolCallId(static::TOOL_NAME);

        // Assert
        $this->assertStringStartsWith(static::SYNTHETIC_TOOL_CALL_ID_PREFIX, $toolCallId);
    }

    public function testGivenEmptyProviderToolCallIdWhenResolvedThenASyntheticIdentifierIsReturned(): void
    {
        // Arrange
        $toolCallIdResolver = new ToolCallIdResolver();

        // Act
        $toolCallId = $toolCallIdResolver->resolveToolCallId(static::TOOL_NAME, '');

        // Assert
        $this->assertStringStartsWith(static::SYNTHETIC_TOOL_CALL_ID_PREFIX, $toolCallId);
    }

    public function testGivenProviderToolCallIdEqualToToolNameWhenResolvedThenASyntheticIdentifierIsReturned(): void
    {
        // Arrange
        $toolCallIdResolver = new ToolCallIdResolver();

        // Act
        $toolCallId = $toolCallIdResolver->resolveToolCallId(static::TOOL_NAME, static::TOOL_NAME);

        // Assert
        $this->assertNotSame(static::TOOL_NAME, $toolCallId);
        $this->assertStringStartsWith(static::SYNTHETIC_TOOL_CALL_ID_PREFIX, $toolCallId);
    }

    public function testGivenTwoResolverInstancesWhenResolvingTheSameToolThenSyntheticIdentifiersAreDistinct(): void
    {
        // Act
        $firstToolCallId = (new ToolCallIdResolver())->resolveToolCallId(static::TOOL_NAME);
        $secondToolCallId = (new ToolCallIdResolver())->resolveToolCallId(static::TOOL_NAME);

        // Assert
        $this->assertNotSame($firstToolCallId, $secondToolCallId);
    }
}
