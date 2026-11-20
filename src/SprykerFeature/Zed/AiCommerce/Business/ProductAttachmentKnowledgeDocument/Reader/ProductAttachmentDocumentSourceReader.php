<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeDocument\Reader;

use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionConditionsTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentTransfer;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Dependency\AiCommerceEvents;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class ProductAttachmentDocumentSourceReader implements ProductAttachmentDocumentSourceReaderInterface
{
    public const string METADATA_KEY_URL = 'url';

    /**
     * @uses \Orm\Zed\AiCommerce\Persistence\Map\SpyProductAttachmentKnowledgeSelectionTableMap::COL_FK_PRODUCT_ATTACHMENT
     */
    protected const string COL_FK_PRODUCT_ATTACHMENT = 'spy_product_attachment_knowledge_selection.fk_product_attachment';

    /**
     * @var list<string>
     */
    protected const array SELECTION_ENTITY_EVENT_NAMES = [
        AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CREATE,
        AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_DELETE,
    ];

    public function __construct(
        protected AiCommerceRepositoryInterface $aiCommerceRepository,
        protected LocaleFacadeInterface $localeFacade,
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<string>
     */
    public function extractSourceIdentifiers(array $eventEntityTransfers, string $eventName): array
    {
        $isSelectionEntityEvent = in_array($eventName, static::SELECTION_ENTITY_EVENT_NAMES, true);
        $sourceIdentifiers = [];

        foreach ($eventEntityTransfers as $eventEntityTransfer) {
            $idProductAttachment = $isSelectionEntityEvent
                ? $this->findForeignKeyProductAttachmentId($eventEntityTransfer)
                : $eventEntityTransfer->getId();

            if ($idProductAttachment === null) {
                continue;
            }

            $sourceIdentifiers[] = (string)$idProductAttachment;
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

        $productAttachmentKnowledgeSelectionCollectionTransfer = $this->aiCommerceRepository->getProductAttachmentKnowledgeSelectionCollection(
            $this->createProductAttachmentKnowledgeSelectionCriteriaTransfer($productAttachmentIds, $knowledgeDocumentSourceCriteriaTransfer),
        );

        $localeNamesById = [];

        foreach ($productAttachmentKnowledgeSelectionCollectionTransfer->getProductAttachmentKnowledgeSelections() as $productAttachmentKnowledgeSelectionTransfer) {
            $productAttachmentTransfer = $productAttachmentKnowledgeSelectionTransfer->getProductAttachmentOrFail();
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

    protected function findForeignKeyProductAttachmentId(EventEntityTransfer $eventEntityTransfer): int|string|null
    {
        return $eventEntityTransfer->getForeignKeys()[static::COL_FK_PRODUCT_ATTACHMENT] ?? null;
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
    protected function createProductAttachmentKnowledgeSelectionCriteriaTransfer(
        array $productAttachmentIds,
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer,
    ): ProductAttachmentKnowledgeSelectionCriteriaTransfer {
        $productAttachmentKnowledgeSelectionCriteriaTransfer = (new ProductAttachmentKnowledgeSelectionCriteriaTransfer())
            ->setProductAttachmentKnowledgeSelectionConditions(
                (new ProductAttachmentKnowledgeSelectionConditionsTransfer())->setProductAttachmentIds($productAttachmentIds),
            );

        if ($productAttachmentIds !== []) {
            return $productAttachmentKnowledgeSelectionCriteriaTransfer;
        }

        return $productAttachmentKnowledgeSelectionCriteriaTransfer->setPagination(
            $knowledgeDocumentSourceCriteriaTransfer->getPagination(),
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
