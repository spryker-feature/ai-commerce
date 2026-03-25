<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\Business\Process\TransitionInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class OrderOmsTransitionsReader implements OrderOmsTransitionsReaderInterface
{
    protected const string PROCESS_NAME = 'processName';

    protected const string STATE_NAMES = 'stateNames';

    public function __construct(
        protected AiCommerceRepositoryInterface $repository,
        protected OmsFacadeInterface $omsFacade,
    ) {
    }

    public function getOrderOmsTransitions(string $orderReference): string
    {
        $orderData = $this->repository->findProcessAndStateNamesByOrderReference($orderReference);
        /** @var ?string $processName */
        $processName = $orderData[static::PROCESS_NAME];
        /** @var array<string> $stateNames */
        $stateNames = $orderData[static::STATE_NAMES];

        if ($processName === null) {
            return '{}';
        }

        return $this->buildTransitionsResponse($processName, $stateNames);
    }

    /**
     * @param array<string> $stateNames
     */
    protected function buildTransitionsResponse(string $processName, array $stateNames): string
    {
        if ($stateNames === []) {
            return '{}';
        }

        $result = [
            'currentStates' => $stateNames,
            'processName' => $processName,
            'transitions' => [],
        ];

        foreach ($this->omsFacade->getProcesses() as $process) {
            if ($process->getName() !== $processName) {
                continue;
            }

            foreach ($process->getAllTransitions() as $transition) {
                if (!in_array($transition->getSource()->getName(), $stateNames, true)) {
                    continue;
                }

                $result['transitions'][] = $this->buildTransitionData($transition);
            }
        }

        return (string)json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTransitionData(TransitionInterface $transition): array
    {
        $transitionData = [
            'source' => $transition->getSource()->getName(),
            'target' => $transition->getTarget()->getName(),
        ];

        if ($transition->hasEvent()) {
            $event = $transition->getEvent();
            $transitionData['event'] = [
                'name' => $event->getName(),
                'manual' => $event->isManual(),
                'onEnter' => $event->isOnEnter(),
                'timeout' => $event->getTimeout(),
                'command' => $event->getCommand(),
            ];
        }

        if ($transition->hasCondition()) {
            $transitionData['condition'] = $transition->getCondition();
        }

        $transitionData['happy'] = $transition->isHappy();

        return $transitionData;
    }
}
