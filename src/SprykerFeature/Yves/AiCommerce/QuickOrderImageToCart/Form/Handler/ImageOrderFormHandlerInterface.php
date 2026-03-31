<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface ImageOrderFormHandlerInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\QuickOrderItemTransfer>
     */
    public function handleForm(FormInterface $form, Request $request): array;
}
