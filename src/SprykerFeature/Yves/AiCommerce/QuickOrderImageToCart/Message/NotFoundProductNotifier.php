<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Message;

use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotFoundProductNotifier implements NotFoundProductNotifierInterface
{
    protected const string GLOSSARY_KEY_PRODUCT_NOT_FOUND = 'ai-commerce.quick-order-image-to-cart.image-order.errors.product-not-found';

    public function __construct(
        protected FlashMessengerInterface $flashMessenger,
        protected TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string> $productNames
     */
    public function addErrorNotifications(array $productNames): void
    {
        foreach ($productNames as $productName) {
            $this->addErrorNotification($productName);
        }
    }

    protected function addErrorNotification(string $productName): void
    {
        $translatedMessage = $this->translator->trans(static::GLOSSARY_KEY_PRODUCT_NOT_FOUND, [
            '%product%' => $productName,
        ]);

        $this->flashMessenger->addErrorMessage($translatedMessage);
    }
}
