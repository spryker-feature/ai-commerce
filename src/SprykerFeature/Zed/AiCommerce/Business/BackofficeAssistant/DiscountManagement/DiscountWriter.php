<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement;

use Generated\Shared\Transfer\DiscountCalculatorTransfer;
use Generated\Shared\Transfer\DiscountConditionTransfer;
use Generated\Shared\Transfer\DiscountConfiguratorResponseTransfer;
use Generated\Shared\Transfer\DiscountConfiguratorTransfer;
use Generated\Shared\Transfer\DiscountGeneralTransfer;
use Spryker\Zed\Discount\Business\DiscountFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class DiscountWriter implements DiscountWriterInterface
{
    public function __construct(
        protected DiscountFacadeInterface $discountFacade,
        protected AiCommerceRepositoryInterface $repository,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDiscount(array $data): string
    {
        $displayName = (string)($data['displayName'] ?? '');

        if ($this->repository->existsDiscountByDisplayName($displayName)) {
            return (string)json_encode([
                'success' => false,
                'errors' => [sprintf('A discount with the name "%s" already exists. Please use a unique name.', $displayName)],
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        $configurator = $this->buildDiscountConfiguratorTransfer($data);
        $response = $this->discountFacade->createDiscount($configurator);

        if (!$response->getIsSuccessful()) {
            return (string)json_encode([
                'success' => false,
                'errors' => $this->extractErrorMessages($response),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        return (string)json_encode([
            'success' => true,
            'idDiscount' => $response->getDiscountConfiguratorOrFail()
                ->getDiscountGeneralOrFail()
                ->getIdDiscount(),
            'errors' => [],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDiscount(int $idDiscount, array $data): string
    {
        $existingDiscount = $this->discountFacade->findHydratedDiscountConfiguratorByIdDiscount($idDiscount);

        if ($existingDiscount === null) {
            return (string)json_encode([
                'success' => false,
                'errors' => [sprintf('Discount with ID %d not found.', $idDiscount)],
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        $discountConfigurator = $this->mergeDiscountData($existingDiscount, $data);
        $response = $this->discountFacade->updateDiscountWithValidation($discountConfigurator);

        if (!$response->getIsSuccessful()) {
            return (string)json_encode([
                'success' => false,
                'errors' => $this->extractErrorMessages($response),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        return (string)json_encode([
            'success' => true,
            'errors' => [],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function buildDiscountConfiguratorTransfer(array $data): DiscountConfiguratorTransfer
    {
        $general = (new DiscountGeneralTransfer())
            ->setDisplayName($data['displayName'] ?? '')
            ->setDiscountType($data['discountType'] ?? '')
            ->setDescription($data['description'] ?? null)
            ->setValidFrom($data['validFrom'] ?? '')
            ->setValidTo($data['validTo'] ?? '')
            ->setIsExclusive((bool)($data['isExclusive'] ?? false))
            ->setPriority(isset($data['priority']) ? (int)$data['priority'] : null)
            ->setIsActive(false);

        $calculator = (new DiscountCalculatorTransfer())
            ->setCalculatorPlugin($data['calculatorPlugin'] ?? '')
            ->setAmount(isset($data['amount']) ? (string)(int)$data['amount'] : '0')
            ->setCollectorQueryString($data['collectorQueryString'] ?? null)
            ->setCollectorStrategyType($data['collectorStrategyType'] ?? 'query-string');

        $condition = (new DiscountConditionTransfer())
            ->setMinimumItemAmount((int)($data['minimumItemAmount'] ?? 1));

        return (new DiscountConfiguratorTransfer())
            ->setDiscountGeneral($general)
            ->setDiscountCalculator($calculator)
            ->setDiscountCondition($condition);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mergeDiscountData(DiscountConfiguratorTransfer $discountTransfer, array $data): DiscountConfiguratorTransfer
    {
        $general = $discountTransfer->getDiscountGeneralOrFail();
        $calculator = $discountTransfer->getDiscountCalculatorOrFail();
        $condition = $discountTransfer->getDiscountConditionOrFail();

        if (isset($data['displayName'])) {
            $general->setDisplayName($data['displayName']);
        }

        if (isset($data['discountType'])) {
            $general->setDiscountType($data['discountType']);
        }

        if (array_key_exists('description', $data)) {
            $general->setDescription($data['description']);
        }

        if (isset($data['validFrom'])) {
            $general->setValidFrom($data['validFrom']);
        }

        if (isset($data['validTo'])) {
            $general->setValidTo($data['validTo']);
        }

        if (isset($data['isExclusive'])) {
            $general->setIsExclusive((bool)$data['isExclusive']);
        }

        if (isset($data['priority'])) {
            $general->setPriority((int)$data['priority']);
        }

        if (isset($data['calculatorPlugin'])) {
            $calculator->setCalculatorPlugin($data['calculatorPlugin']);
        }

        if (isset($data['amount'])) {
            $calculator->setAmount((string)(int)$data['amount']);
        }

        if (isset($data['minimumItemAmount'])) {
            $condition->setMinimumItemAmount((int)$data['minimumItemAmount']);
        }

        return $discountTransfer;
    }

    /**
     * @return array<string>
     */
    protected function extractErrorMessages(DiscountConfiguratorResponseTransfer $response): array
    {
        $errors = [];

        foreach ($response->getMessages() as $message) {
            $text = $message->getMessage();

            if ($text !== null && $text !== '') {
                $errors[] = $text;
            }
        }

        if ($errors === []) {
            $errors[] = 'Operation failed due to a validation error. Please check the provided data and try again.';
        }

        return $errors;
    }
}
