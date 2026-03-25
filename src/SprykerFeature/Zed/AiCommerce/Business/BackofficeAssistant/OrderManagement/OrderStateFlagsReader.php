<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class OrderStateFlagsReader implements OrderStateFlagsReaderInterface
{
    protected const string PROCESS_NAME = 'processName';

    protected const string STATE_NAMES = 'stateNames';

    public function __construct(
        protected AiCommerceRepositoryInterface $repository,
        protected OmsFacadeInterface $omsFacade,
    ) {
    }

    public function getOrderStateFlags(string $orderReference): string
    {
        $orderData = $this->repository->findProcessAndStateNamesByOrderReference($orderReference);
        /** @var ?string $processName */
        $processName = $orderData[static::PROCESS_NAME];
        /** @var array<string> $stateNames */
        $stateNames = $orderData[static::STATE_NAMES];

        if ($processName === null) {
            return '{}';
        }

        $stateFlags = [];

        foreach ($stateNames as $stateName) {
            $stateFlags[$stateName] = $this->omsFacade->getStateFlags($processName, $stateName);
        }

        return (string)json_encode([
            'processName' => $processName,
            'states' => $stateFlags,
        ], JSON_PRETTY_PRINT);
    }
}
