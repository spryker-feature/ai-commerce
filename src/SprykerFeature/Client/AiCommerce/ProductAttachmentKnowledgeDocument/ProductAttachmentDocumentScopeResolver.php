<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\ProductAttachmentKnowledgeDocument;

use ArrayObject;
use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\ProductAttachmentStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentStorageTransfer;
use Spryker\Client\ProductAttachmentStorage\ProductAttachmentStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductAbstractIdResolverInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class ProductAttachmentDocumentScopeResolver implements ProductAttachmentDocumentScopeResolverInterface
{
    protected const string PRODUCT_REFERENCE_ID_PRODUCT_ABSTRACT = 'idProductAbstract %d';

    protected const string PRODUCT_REFERENCE_SKU = 'SKU "%s"';

    protected const string MESSAGE_PRODUCT_NOT_FOUND = 'No product was found for %s.';

    protected const string MESSAGE_PRODUCT_HAS_NO_DOCUMENTS = 'The product with %s has no documents.';

    protected const string MESSAGE_REQUESTED_DOCUMENTS_NOT_FOUND = 'None of the requested documents belong to the requested products.';

    public function __construct(
        protected ProductAbstractIdResolverInterface $productAbstractIdResolver,
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductAttachmentStorageClientInterface $productAttachmentStorageClient
    ) {
    }

    public function isApplicable(KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer): bool
    {
        return $this->getScopeReferences($knowledgeDocumentScopeRequestTransfer, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID) !== []
            || $this->getScopeReferences($knowledgeDocumentScopeRequestTransfer, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU) !== []
            || $this->getScopeReferences($knowledgeDocumentScopeRequestTransfer, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_CONCRETE_SKU) !== [];
    }

    public function resolveDocumentScope(
        KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer
    ): KnowledgeDocumentScopeTransfer {
        $knowledgeDocumentScopeTransfer = new KnowledgeDocumentScopeTransfer();
        $localeName = $knowledgeDocumentScopeRequestTransfer->getLocaleNameOrFail();
        $storeName = $knowledgeDocumentScopeRequestTransfer->getStoreNameOrFail();
        $productReferences = $this->resolveProductReferences($knowledgeDocumentScopeRequestTransfer, $knowledgeDocumentScopeTransfer);
        $productAbstractStorageData = $productReferences === [] ? [] : $this->productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
            array_keys($productReferences),
            $localeName,
            $storeName,
        );

        foreach ($productReferences as $idProductAbstract => $productReference) {
            if (!isset($productAbstractStorageData[$idProductAbstract])) {
                $knowledgeDocumentScopeTransfer->addMessage(sprintf(static::MESSAGE_PRODUCT_NOT_FOUND, $productReference));

                continue;
            }

            $this->addProductDocumentKeys($knowledgeDocumentScopeTransfer, $idProductAbstract, $productReference, $localeName);
        }

        return $this->filterBySourceIdentifiers(
            $knowledgeDocumentScopeTransfer,
            $knowledgeDocumentScopeRequestTransfer->getSourceIdentifiers(),
        );
    }

    /**
     * @return array<int, string>
     */
    protected function resolveProductReferences(
        KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer,
        KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer
    ): array {
        $productReferences = [];

        foreach ($this->getScopeReferences($knowledgeDocumentScopeRequestTransfer, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID) as $idProductAbstract) {
            $productReferences[(int)$idProductAbstract] ??= sprintf(static::PRODUCT_REFERENCE_ID_PRODUCT_ABSTRACT, $idProductAbstract);
        }

        $skus = [
            ...$this->getScopeReferences($knowledgeDocumentScopeRequestTransfer, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU),
            ...$this->getScopeReferences($knowledgeDocumentScopeRequestTransfer, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_CONCRETE_SKU),
        ];

        foreach ($skus as $sku) {
            $productReference = sprintf(static::PRODUCT_REFERENCE_SKU, $sku);
            $idProductAbstract = $this->productAbstractIdResolver->resolveIdProductAbstractBySku(
                (string)$sku,
                $knowledgeDocumentScopeRequestTransfer->getLocaleNameOrFail(),
            );

            if ($idProductAbstract === null) {
                $knowledgeDocumentScopeTransfer->addMessage(sprintf(static::MESSAGE_PRODUCT_NOT_FOUND, $productReference));

                continue;
            }

            $productReferences[$idProductAbstract] ??= $productReference;
        }

        return $productReferences;
    }

    protected function addProductDocumentKeys(
        KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer,
        int $idProductAbstract,
        string $productReference,
        string $localeName
    ): void {
        $knowledgeDocumentSourceTransfers = $this->getProductAttachmentDocumentSources($idProductAbstract, $localeName);

        if ($knowledgeDocumentSourceTransfers === []) {
            $knowledgeDocumentScopeTransfer->addMessage(sprintf(static::MESSAGE_PRODUCT_HAS_NO_DOCUMENTS, $productReference));

            return;
        }

        foreach ($knowledgeDocumentSourceTransfers as $knowledgeDocumentSourceTransfer) {
            $documentKey = $this->createDocumentKey((string)$knowledgeDocumentSourceTransfer->getSourceIdentifier());

            if (in_array($documentKey, $knowledgeDocumentScopeTransfer->getDocumentKeys(), true)) {
                continue;
            }

            $knowledgeDocumentScopeTransfer
                ->addDocumentKey($documentKey)
                ->addKnowledgeDocumentSource($knowledgeDocumentSourceTransfer);
        }
    }

    /**
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer>
     */
    protected function getProductAttachmentDocumentSources(int $idProductAbstract, string $localeName): array
    {
        $productAbstractAttachmentStorageTransfer = $this->productAttachmentStorageClient->findProductAbstractAttachmentStorage(
            (new ProductAttachmentStorageCriteriaTransfer())
                ->setIdProductAbstract($idProductAbstract)
                ->setLocale($localeName),
        );

        if ($productAbstractAttachmentStorageTransfer === null) {
            return [];
        }

        $knowledgeDocumentSourceTransfers = [];

        foreach ($productAbstractAttachmentStorageTransfer->getAttachments() as $productAttachmentStorageTransfer) {
            if ($productAttachmentStorageTransfer->getIdProductAttachment() === null) {
                continue;
            }

            $knowledgeDocumentSourceTransfers[] = $this->createKnowledgeDocumentSource($productAttachmentStorageTransfer);
        }

        return $knowledgeDocumentSourceTransfers;
    }

    protected function createKnowledgeDocumentSource(
        ProductAttachmentStorageTransfer $productAttachmentStorageTransfer
    ): KnowledgeDocumentSourceTransfer {
        return (new KnowledgeDocumentSourceTransfer())
            ->setSourceType(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT)
            ->setSourceIdentifier((string)$productAttachmentStorageTransfer->getIdProductAttachmentOrFail())
            ->setDocumentName($productAttachmentStorageTransfer->getLabel())
            ->setUrl($productAttachmentStorageTransfer->getUrl());
    }

    /**
     * @param array<string> $sourceIdentifiers
     */
    protected function filterBySourceIdentifiers(
        KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer,
        array $sourceIdentifiers
    ): KnowledgeDocumentScopeTransfer {
        if ($sourceIdentifiers === [] || $knowledgeDocumentScopeTransfer->getDocumentKeys() === []) {
            return $knowledgeDocumentScopeTransfer;
        }

        $requestedDocumentKeys = array_map($this->createDocumentKey(...), array_map('strval', $sourceIdentifiers));
        $documentKeys = array_values(array_intersect($knowledgeDocumentScopeTransfer->getDocumentKeys(), $requestedDocumentKeys));

        if ($documentKeys === []) {
            $knowledgeDocumentScopeTransfer->addMessage(static::MESSAGE_REQUESTED_DOCUMENTS_NOT_FOUND);
        }

        return $knowledgeDocumentScopeTransfer
            ->setDocumentKeys($documentKeys)
            ->setKnowledgeDocumentSources($this->filterKnowledgeDocumentSources(
                $knowledgeDocumentScopeTransfer->getKnowledgeDocumentSources(),
                $documentKeys,
            ));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer> $knowledgeDocumentSourceTransfers
     * @param list<string> $documentKeys
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer>
     */
    protected function filterKnowledgeDocumentSources(ArrayObject $knowledgeDocumentSourceTransfers, array $documentKeys): ArrayObject
    {
        $filteredKnowledgeDocumentSourceTransfers = new ArrayObject();

        foreach ($knowledgeDocumentSourceTransfers as $knowledgeDocumentSourceTransfer) {
            if (!in_array($this->createDocumentKey((string)$knowledgeDocumentSourceTransfer->getSourceIdentifier()), $documentKeys, true)) {
                continue;
            }

            $filteredKnowledgeDocumentSourceTransfers->append($knowledgeDocumentSourceTransfer);
        }

        return $filteredKnowledgeDocumentSourceTransfers;
    }

    /**
     * @return list<string>
     */
    protected function getScopeReferences(KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer, string $scopeReferenceType): array
    {
        return array_values(array_map('strval', (array)($knowledgeDocumentScopeRequestTransfer->getScopeReferences()[$scopeReferenceType] ?? [])));
    }

    protected function createDocumentKey(string $sourceIdentifier): string
    {
        return sprintf(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT,
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            $sourceIdentifier,
        );
    }
}
