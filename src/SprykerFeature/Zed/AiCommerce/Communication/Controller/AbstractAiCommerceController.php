<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use ArrayObject;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacade getFacade()
 */
abstract class AbstractAiCommerceController extends AbstractController
{
    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $errors
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formatErrors(ArrayObject $errors): array
    {
        $formatted = [];

        foreach ($errors as $errorTransfer) {
            $formatted[] = [
                'message' => $errorTransfer->getMessageOrFail(),
                'code' => $errorTransfer->getParameters()['code'] ?? null,
            ];
        }

        return $formatted;
    }
}
