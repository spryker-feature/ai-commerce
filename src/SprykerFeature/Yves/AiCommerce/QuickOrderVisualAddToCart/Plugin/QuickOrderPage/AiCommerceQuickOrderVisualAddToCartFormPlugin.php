<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Plugin\QuickOrderPage;

use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\QuickOrderPageExtension\Dependency\Plugin\QuickOrderFormPluginInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 */
class AiCommerceQuickOrderVisualAddToCartFormPlugin extends AbstractPlugin implements QuickOrderFormPluginInterface
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(): bool
    {
        return $this->getConfig()->isQuickOrderVisualAddToCartEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function createForm(): FormInterface
    {
        return $this->getFactory()->createVisualAddToCartForm();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Generated\Shared\Transfer\QuickOrderItemTransfer>
     */
    public function handleForm(FormInterface $form, Request $request): array
    {
        return $this->getFactory()->createVisualAddToCartFormHandler()->handleForm($form, $request);
    }
}
