<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizer;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Tool
 * @group ToolArgumentNormalizerTest
 */
class ToolArgumentNormalizerTest extends Unit
{
    protected const string ARGUMENT_QUERY = 'query';

    protected const string QUERY = 'camera';

    public function testGivenArgumentsWrappedInAPositionalArrayWhenNormalizedThenTheWrappedArgumentsAreReturned(): void
    {
        // Act
        $arguments = (new ToolArgumentNormalizer())->normalizeArguments([[static::ARGUMENT_QUERY => static::QUERY]]);

        // Assert
        $this->assertSame([static::ARGUMENT_QUERY => static::QUERY], $arguments);
    }

    public function testGivenKeyedArgumentsWhenNormalizedThenTheyAreReturnedUnchanged(): void
    {
        // Act
        $arguments = (new ToolArgumentNormalizer())->normalizeArguments([static::ARGUMENT_QUERY => static::QUERY]);

        // Assert
        $this->assertSame([static::ARGUMENT_QUERY => static::QUERY], $arguments);
    }

    public function testGivenAPositionalScalarArgumentWhenNormalizedThenItIsReturnedUnchanged(): void
    {
        // Act
        $arguments = (new ToolArgumentNormalizer())->normalizeArguments([static::QUERY]);

        // Assert
        $this->assertSame([static::QUERY], $arguments);
    }
}
