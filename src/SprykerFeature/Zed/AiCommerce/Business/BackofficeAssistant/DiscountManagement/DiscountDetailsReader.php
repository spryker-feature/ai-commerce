<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement;

use Generated\Shared\Transfer\DiscountConfiguratorTransfer;
use Spryker\Zed\Discount\Business\DiscountFacadeInterface;

class DiscountDetailsReader implements DiscountDetailsReaderInterface
{
    public function __construct(protected DiscountFacadeInterface $discountFacade)
    {
    }

    public function getDiscountDetails(int $idDiscount): string
    {
        $configurator = $this->discountFacade->findHydratedDiscountConfiguratorByIdDiscount($idDiscount);

        if ($configurator === null) {
            return (string)json_encode([
                'found' => false,
                'error' => sprintf('Discount with ID %d does not exist.', $idDiscount),
            ], JSON_PRETTY_PRINT);
        }

        return (string)json_encode(
            array_merge(['found' => true], $this->buildDiscountData($configurator)),
            JSON_PRETTY_PRINT,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDiscountData(DiscountConfiguratorTransfer $configurator): array
    {
        $general = $configurator->getDiscountGeneral();
        $calculator = $configurator->getDiscountCalculator();
        $condition = $configurator->getDiscountCondition();
        $voucher = $configurator->getDiscountVoucher();

        $data = [
            'general' => [
                'idDiscount' => $general?->getIdDiscount(),
                'displayName' => $general?->getDisplayName(),
                'description' => $general?->getDescription(),
                'discountType' => $general?->getDiscountType(),
                'isActive' => $general?->getIsActive(),
                'isExclusive' => $general?->getIsExclusive(),
                'validFrom' => $general?->getValidFrom(),
                'validTo' => $general?->getValidTo(),
                'priority' => $general?->getPriority(),
            ],
            'calculator' => [
                'calculatorPlugin' => $calculator?->getCalculatorPlugin(),
                'amount' => $calculator?->getAmount(),
                'collectorQueryString' => $calculator?->getCollectorQueryString(),
                'collectorStrategyType' => $calculator?->getCollectorStrategyType(),
            ],
            'condition' => [
                'decisionRuleQueryString' => $condition?->getDecisionRuleQueryString(),
                'minimumItemAmount' => $condition?->getMinimumItemAmount(),
            ],
        ];

        if ($voucher !== null) {
            $data['voucher'] = [
                'fkDiscountVoucherPool' => $voucher->getFkDiscountVoucherPool(),
                'maxNumberOfUses' => $voucher->getMaxNumberOfUses(),
            ];
        }

        return $data;
    }
}
