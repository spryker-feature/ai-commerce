<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class ShownProductReader implements ShownProductReaderInterface
{
    protected const string RESULT_KEY_PRODUCTS = 'products';

    public function __construct(
        protected AiFoundationClientInterface $aiFoundationClient,
        protected ConversationMessageExtractorInterface $conversationMessageExtractor,
        protected ConversationReferenceDeriverInterface $conversationReferenceDeriver,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    /**
     * @return list<int>
     */
    public function getShownProductAbstractIds(string $customerReference, string $conversationReference): array
    {
        if (trim($customerReference) === '' || trim($conversationReference) === '') {
            return [];
        }

        $derivedConversationReference = $this->conversationReferenceDeriver->deriveConversationReference(
            $customerReference,
            $conversationReference,
        );

        $conversationHistoryCollectionTransfer = $this->aiFoundationClient->getConversationHistoryCollection(
            (new ConversationHistoryCriteriaTransfer())->setConversationHistoryConditions(
                (new ConversationHistoryConditionsTransfer())->setConversationReferences([$derivedConversationReference]),
            ),
        );

        $productAbstractIds = [];

        foreach ($conversationHistoryCollectionTransfer->getConversationHistories() as $conversationHistoryTransfer) {
            if ($conversationHistoryTransfer->getConversationReference() !== $derivedConversationReference) {
                continue;
            }

            $productAbstractIds = array_merge(
                $productAbstractIds,
                $this->extractShownProductAbstractIds(
                    $this->conversationMessageExtractor->extractConversationMessagesFromConversationHistory($conversationHistoryTransfer),
                ),
            );
        }

        return array_values(array_unique($productAbstractIds));
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer> $storefrontAssistantConversationMessageTransfers
     *
     * @return list<int>
     */
    protected function extractShownProductAbstractIds(array $storefrontAssistantConversationMessageTransfers): array
    {
        $customerFacingToolNames = $this->aiCommerceConfig->getStorefrontAssistantCustomerFacingToolNames();
        $productAbstractIds = [];

        foreach ($storefrontAssistantConversationMessageTransfers as $storefrontAssistantConversationMessageTransfer) {
            foreach ($storefrontAssistantConversationMessageTransfer->getToolInvocations() as $toolInvocationTransfer) {
                if (!in_array($toolInvocationTransfer->getName(), $customerFacingToolNames, true)) {
                    continue;
                }

                $productAbstractIds = array_merge(
                    $productAbstractIds,
                    $this->extractProductAbstractIds((string)$toolInvocationTransfer->getResult()),
                );
            }
        }

        return $productAbstractIds;
    }

    /**
     * @return list<int>
     */
    protected function extractProductAbstractIds(string $toolResult): array
    {
        $decodedToolResult = json_decode($toolResult, true);
        $products = is_array($decodedToolResult) ? ($decodedToolResult[static::RESULT_KEY_PRODUCTS] ?? null) : null;

        if (!is_array($products)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($products as $product) {
            $idProductAbstract = is_array($product) ? ($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null) : null;

            if (is_int($idProductAbstract) && $idProductAbstract > 0) {
                $productAbstractIds[] = $idProductAbstract;
            }
        }

        return $productAbstractIds;
    }
}
