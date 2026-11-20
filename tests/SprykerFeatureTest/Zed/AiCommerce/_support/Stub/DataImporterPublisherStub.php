<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Stub;

use Spryker\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher;

class DataImporterPublisherStub extends DataImporterPublisher
{
    /**
     * @return list<int>
     */
    public static function getImportedEntityIds(string $eventName): array
    {
        return array_keys(static::$importedEntityEvents[$eventName] ?? []);
    }

    public static function reset(): void
    {
        static::$importedEntityEvents = [];
        static::$triggeredEventIds = [];
    }
}
