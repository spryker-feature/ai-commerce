<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class OmsProcessDefinitionReader implements OmsProcessDefinitionReaderInterface
{
    public function __construct(
        protected AiCommerceRepositoryInterface $repository,
        protected OmsFacadeInterface $omsFacade,
        protected OmsTransitionDataBuilderInterface $omsTransitionDataBuilder,
    ) {
    }

    public function getOmsProcessDefinition(string $orderReference): string
    {
        $orderData = $this->repository->findProcessAndStateNamesByOrderReference($orderReference);
        $processName = $orderData['processName'];

        if ($processName === null) {
            return '{}';
        }

        foreach ($this->omsFacade->getProcesses() as $process) {
            if ($process->getName() !== $processName) {
                continue;
            }

            return (string)json_encode($this->buildProcessDefinitionData($process), JSON_PRETTY_PRINT);
        }

        return '{}';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildProcessDefinitionData(ProcessInterface $process): array
    {
        return [
            'processName' => $process->getName(),
            'file' => $process->hasFile() ? $process->getFile() : null,
            'states' => $this->buildStatesData($process),
            'transitions' => $this->buildTransitionsData($process),
            'subProcesses' => $this->buildSubProcessesData($process),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildStatesData(ProcessInterface $process): array
    {
        $states = [];

        foreach ($process->getAllStates() as $state) {
            $flags = [];

            foreach ($state->getFlags() as $flag) {
                $flags[] = $flag->getName();
            }

            $states[] = [
                'name' => $state->getName(),
                'reserved' => $state->isReserved(),
                'flags' => $flags,
                'displayName' => $state->getDisplay() ?: $state->getName(),
            ];
        }

        return $states;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildTransitionsData(ProcessInterface $process): array
    {
        $transitions = [];

        foreach ($process->getAllTransitions() as $transition) {
            $transitions[] = $this->omsTransitionDataBuilder->buildTransitionData($transition);
        }

        return $transitions;
    }

    /**
     * @return array<int, string>
     */
    protected function buildSubProcessesData(ProcessInterface $process): array
    {
        $subProcessNames = [];

        foreach ($process->getSubProcesses() as $subProcess) {
            $subProcessNames[] = $subProcess->getName();
        }

        return $subProcessNames;
    }
}
