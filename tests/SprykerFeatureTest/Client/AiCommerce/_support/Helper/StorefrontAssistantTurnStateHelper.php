<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice\ChoiceOfferRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ComparisonRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultRegistry;

class StorefrontAssistantTurnStateHelper extends Module
{
    public function _before(TestInterface $test): void
    {
        $this->resetTurnState();
    }

    public function _after(TestInterface $test): void
    {
        $this->resetTurnState();
    }

    protected function resetTurnState(): void
    {
        (new CatalogSearchResultRegistry())->clear();
        (new ChoiceOfferRegistry())->clear();
        (new ComparisonRegistry())->clear();
    }
}
