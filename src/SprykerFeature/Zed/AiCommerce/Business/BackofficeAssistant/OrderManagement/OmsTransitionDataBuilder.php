<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Spryker\Zed\Oms\Business\Process\TransitionInterface;

class OmsTransitionDataBuilder implements OmsTransitionDataBuilderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function buildTransitionData(TransitionInterface $transition): array
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
