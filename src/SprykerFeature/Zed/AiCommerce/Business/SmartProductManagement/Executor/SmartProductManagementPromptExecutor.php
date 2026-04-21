<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor;

use ArrayObject;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;

class SmartProductManagementPromptExecutor implements SmartProductManagementPromptExecutorInterface
{
    use LoggerTrait;

    public function __construct(
        protected readonly AiFoundationFacadeInterface $aiFoundationFacade,
        protected readonly AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function executePrompt(
        PromptRequestTransfer $promptRequestTransfer,
        string $operationName,
    ): PromptResponseTransfer {
        $promptResponseTransfer = $this->aiFoundationFacade->prompt($promptRequestTransfer);

        if ($promptResponseTransfer->getIsSuccessful() === false) {
            return $this->handleUnsuccessfulResponse($promptResponseTransfer, $promptRequestTransfer, $operationName);
        }

        return $promptResponseTransfer;
    }

    protected function handleUnsuccessfulResponse(
        PromptResponseTransfer $promptResponseTransfer,
        PromptRequestTransfer $promptRequestTransfer,
        string $operationName,
    ): PromptResponseTransfer {
        $errorMessages = array_map(
            fn (ErrorTransfer $error) => $error->getMessage(),
            $promptResponseTransfer->getErrors()->getArrayCopy(),
        );

        $this->getLogger()->error('SmartProductManagementPromptExecutor: Error while executing prompt', [
            'messages' => $errorMessages,
            'prompt' => $promptRequestTransfer->modifiedToArray(),
            'response' => $promptResponseTransfer->modifiedToArray(),
        ]);

        $promptResponseTransfer->setErrors(new ArrayObject([
            (new ErrorTransfer())
                ->setParameters(['code' => $this->aiCommerceConfig->getErrorCodeAiProviderRequestError()])
                ->setMessage(sprintf(
                    $this->aiCommerceConfig->getErrorMessageAiProviderRequestErrorTemplate(),
                    $operationName,
                )),
        ]));

        return $promptResponseTransfer;
    }
}
