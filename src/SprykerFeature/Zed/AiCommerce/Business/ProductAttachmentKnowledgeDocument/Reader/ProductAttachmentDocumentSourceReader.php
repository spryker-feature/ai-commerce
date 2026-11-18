<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeDocument\Reader;

use Generated\Shared\Transfer\FilterTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\ProductAttachmentConditionTransfer;
use Generated\Shared\Transfer\ProductAttachmentCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentTransfer;
use Generated\Shared\Transfer\SortTransfer;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\ProductAttachment\Business\ProductAttachmentFacadeInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class ProductAttachmentDocumentSourceReader implements ProductAttachmentDocumentSourceReaderInterface
{
    public const string METADATA_KEY_URL = 'url';

    /**
     * @uses \Orm\Zed\ProductAttachment\Persistence\Map\SpyProductAttachmentTableMap::COL_ID_PRODUCT_ATTACHMENT
     */
    protected const string COL_ID_PRODUCT_ATTACHMENT = 'spy_product_attachment.id_product_attachment';

    public function __construct(
        protected ProductAttachmentFacadeInterface $productAttachmentFacade,
        protected LocaleFacadeInterface $localeFacade,
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<string>
     */
    public function extractSourceIdentifiers(array $eventEntityTransfers): array
    {
        $sourceIdentifiers = [];

        foreach ($eventEntityTransfers as $eventEntityTransfer) {
            if ($eventEntityTransfer->getId() === null) {
                continue;
            }

            $sourceIdentifiers[] = (string)$eventEntityTransfer->getId();
        }

        return array_values(array_unique($sourceIdentifiers));
    }

    public function getDocumentSourceCollection(
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer,
    ): KnowledgeDocumentSourceCollectionTransfer {
        $knowledgeDocumentSourceCollectionTransfer = new KnowledgeDocumentSourceCollectionTransfer();
        $sourceIdentifiers = $knowledgeDocumentSourceCriteriaTransfer->getKnowledgeDocumentSourceConditions()?->getSourceIdentifiers() ?? [];
        $productAttachmentIds = $this->getProductAttachmentIds($sourceIdentifiers);

        if ($sourceIdentifiers !== [] && $productAttachmentIds === []) {
            return $knowledgeDocumentSourceCollectionTransfer;
        }

        $productAttachmentCollectionTransfer = $this->productAttachmentFacade->getProductAttachmentCollection(
            $this->createProductAttachmentCriteriaTransfer($productAttachmentIds, $knowledgeDocumentSourceCriteriaTransfer),
        );

        $localeNamesById = [];

        foreach ($productAttachmentCollectionTransfer->getProductAttachments() as $productAttachmentTransfer) {
            $localeNamesById = $this->addLocaleName($localeNamesById, $productAttachmentTransfer->getFkLocale());

            $knowledgeDocumentSourceCollectionTransfer->addKnowledgeDocumentSource(
                $this->mapProductAttachmentTransferToKnowledgeDocumentSourceTransfer(
                    $productAttachmentTransfer,
                    new KnowledgeDocumentSourceTransfer(),
                    $localeNamesById,
                ),
            );
        }

        return $knowledgeDocumentSourceCollectionTransfer;
    }

    /**
     * @param array<string> $sourceIdentifiers
     *
     * @return list<int>
     */
    protected function getProductAttachmentIds(array $sourceIdentifiers): array
    {
        $productAttachmentIds = [];

        foreach ($sourceIdentifiers as $sourceIdentifier) {
            if (!ctype_digit($sourceIdentifier)) {
                continue;
            }

            $productAttachmentIds[] = (int)$sourceIdentifier;
        }

        return array_values(array_unique($productAttachmentIds));
    }

    /**
     * @param list<int> $productAttachmentIds
     */
    protected function createProductAttachmentCriteriaTransfer(
        array $productAttachmentIds,
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer,
    ): ProductAttachmentCriteriaTransfer {
        $productAttachmentConditionTransfer = new ProductAttachmentConditionTransfer();
        $productAttachmentCriteriaTransfer = (new ProductAttachmentCriteriaTransfer())
            ->setProductAttachmentCondition($productAttachmentConditionTransfer)
            ->addSort((new SortTransfer())->setField(static::COL_ID_PRODUCT_ATTACHMENT)->setIsAscending(true));

        if ($productAttachmentIds !== []) {
            $productAttachmentConditionTransfer->setProductAttachmentIds($productAttachmentIds);

            return $productAttachmentCriteriaTransfer;
        }

        $paginationTransfer = $knowledgeDocumentSourceCriteriaTransfer->getPagination();

        if ($paginationTransfer === null) {
            return $productAttachmentCriteriaTransfer;
        }

        return $productAttachmentCriteriaTransfer->setFilter(
            (new FilterTransfer())
                ->setLimit($paginationTransfer->getLimit())
                ->setOffset($paginationTransfer->getOffset()),
        );
    }

    /**
     * @param array<int, string> $localeNamesById
     *
     * @return array<int, string>
     */
    protected function addLocaleName(array $localeNamesById, ?int $idLocale): array
    {
        if ($idLocale === null || isset($localeNamesById[$idLocale])) {
            return $localeNamesById;
        }

        $localeNamesById[$idLocale] = $this->localeFacade->getLocaleById($idLocale)->getLocaleNameOrFail();

        return $localeNamesById;
    }

    /**
     * @param array<int, string> $localeNamesById
     */
    protected function mapProductAttachmentTransferToKnowledgeDocumentSourceTransfer(
        ProductAttachmentTransfer $productAttachmentTransfer,
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
        array $localeNamesById,
    ): KnowledgeDocumentSourceTransfer {
        $idLocale = $productAttachmentTransfer->getFkLocale();

        return $knowledgeDocumentSourceTransfer
            ->setSourceType(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT)
            ->setSourceIdentifier((string)$productAttachmentTransfer->getIdProductAttachmentOrFail())
            ->setDocumentName($productAttachmentTransfer->getLabel())
            ->setLocaleName($idLocale === null ? null : $localeNamesById[$idLocale])
            ->setUrl($productAttachmentTransfer->getUrl())
            ->setMetadata([static::METADATA_KEY_URL => $productAttachmentTransfer->getUrl()]);
    }
}
