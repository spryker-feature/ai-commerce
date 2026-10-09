<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice;

class ChoiceOfferRegistry implements ChoiceOfferRegistryInterface
{
    /**
     * Static: each tool plugin creates its own factory, so the turn state is shared per process; a single flag, cleared by StorefrontAssistantChatStreamer at turn start and end.
     */
    protected static bool $hasChoiceOffer = false;

    public function hasChoiceOffer(): bool
    {
        return static::$hasChoiceOffer;
    }

    public function registerChoiceOffer(): void
    {
        static::$hasChoiceOffer = true;
    }

    public function clear(): void
    {
        static::$hasChoiceOffer = false;
    }
}
