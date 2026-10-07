<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\AiFoundation;

use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptStreamChunkTransfer;
use Spryker\Client\AiFoundation\Dependency\Plugin\StreamEventPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\Kernel\BundleConfigResolverAwareTrait;
use Spryker\Shared\AiFoundation\Stream\PromptStreamChunkType;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventBlockType;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class StorefrontAssistantSseStreamEventPlugin extends AbstractPlugin implements StreamEventPluginInterface
{
    use BundleConfigResolverAwareTrait;

    /**
     * {@inheritDoc}
     * - Streams the text and reasoning chunks of storefront assistant AI configurations to the customer.
     * - Wraps the deltas of a chunk run in the start and end events of the protocol block.
     *
     * @api
     */
    public function onStreamEvent(
        PromptStreamChunkTransfer $promptStreamChunkTransfer,
        PromptRequestTransfer $promptRequestTransfer
    ): void {
        if (!in_array($promptRequestTransfer->getAiConfigurationName(), $this->getConfig()->getStorefrontAssistantSseAiConfigurationNames(), true)) {
            return;
        }

        $streamEventBlockType = $this->findStreamEventBlockType($promptStreamChunkTransfer);

        if ($streamEventBlockType === null) {
            return;
        }

        $blockId = $this->getFactory()->createStreamBlockTracker()->openBlockId($streamEventBlockType);

        $this->getFactory()->createStreamEventEmitter()->emitEvent($streamEventBlockType->deltaEventType(), [
            StreamEventKey::ID => $blockId,
            StreamEventKey::DELTA => (string)$promptStreamChunkTransfer->getContent(),
        ]);
    }

    protected function findStreamEventBlockType(
        PromptStreamChunkTransfer $promptStreamChunkTransfer
    ): ?StreamEventBlockType {
        $promptStreamChunkType = PromptStreamChunkType::tryFrom((string)$promptStreamChunkTransfer->getType());

        if ($promptStreamChunkType === null) {
            return null;
        }

        return match ($promptStreamChunkType) {
            PromptStreamChunkType::Text => StreamEventBlockType::Text,
            PromptStreamChunkType::Reasoning => StreamEventBlockType::Reasoning,
        };
    }
}
