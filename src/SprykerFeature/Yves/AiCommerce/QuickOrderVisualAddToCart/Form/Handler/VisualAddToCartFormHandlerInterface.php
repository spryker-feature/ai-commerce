<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface VisualAddToCartFormHandlerInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\QuickOrderItemTransfer>
     */
    public function handleForm(FormInterface $form, Request $request): array;
}
