<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

interface ShownProductRegistryInterface
{
    public function startTurn(string $customerReference, string $conversationReference): void;

    /**
     * @param array<int, int> $productAbstractIds
     */
    public function addShownProductAbstractIds(array $productAbstractIds): void;

    /**
     * @return list<int>
     */
    public function getShownProductAbstractIds(): array;

    /**
     * Resets the customer, the conversation and the shown products, so no customer data stays in the process memory after a turn.
     */
    public function clear(): void;
}
