<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price;

use Generated\Shared\Transfer\MoneyTransfer;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Money\MoneyClientInterface;

class StorefrontAssistantPriceFormatter implements StorefrontAssistantPriceFormatterInterface
{
    public function __construct(
        protected MoneyClientInterface $moneyClient,
        protected CurrencyClientInterface $currencyClient
    ) {
    }

    public function formatAmount(int $amount): string
    {
        return $this->moneyClient->formatWithSymbol(
            (new MoneyTransfer())
                ->setAmount((string)$amount)
                ->setCurrency($this->currencyClient->getCurrent()),
        );
    }
}
