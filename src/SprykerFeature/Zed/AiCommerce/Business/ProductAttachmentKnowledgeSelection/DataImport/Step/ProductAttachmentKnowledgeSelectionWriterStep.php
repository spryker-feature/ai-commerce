<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\DataImport\Step;

use Generated\Shared\Transfer\LocaleConditionsTransfer;
use Generated\Shared\Transfer\LocaleCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentConditionTransfer;
use Generated\Shared\Transfer\ProductAttachmentCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionConditionsTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionTransfer;
use Spryker\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\PublishAwareStep;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductAttachment\Business\ProductAttachmentFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\DataImport\DataSet\ProductAttachmentKnowledgeSelectionDataSetInterface;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Reader\ProductAttachmentKnowledgeSelectionReaderInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\AiCommerceEvents;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class ProductAttachmentKnowledgeSelectionWriterStep extends PublishAwareStep implements DataImportStepInterface
{
    protected const string VALUE_SELECTED = '1';

    protected const string VALUE_NOT_SELECTED = '0';

    protected const string LOCALE_NAME_DEFAULT = 'default';

    protected const string ERROR_MESSAGE_PRODUCT_ATTACHMENT_NOT_FOUND = 'Product attachment with abstract SKU "%s", locale "%s" and URL "%s" not found.';

    protected const string ERROR_MESSAGE_INVALID_VALUE = 'Value "%s" of "%s" for abstract SKU "%s", locale "%s" and URL "%s" is invalid. Use "%s" or "%s".';

    protected ?string $cachedAbstractSku = null;

    /**
     * @var list<\Generated\Shared\Transfer\ProductAttachmentTransfer>
     */
    protected array $cachedProductAttachmentTransfers = [];

    /**
     * @var array<string, int|null>
     */
    protected array $idLocaleCache = [];

    public function __construct(
        protected ProductFacadeInterface $productFacade,
        protected LocaleFacadeInterface $localeFacade,
        protected ProductAttachmentFacadeInterface $productAttachmentFacade,
        protected ProductAttachmentKnowledgeSelectionReaderInterface $productAttachmentKnowledgeSelectionReader,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
    ) {
    }

    public function execute(DataSetInterface $dataSet): void
    {
        $abstractSku = (string)$dataSet[ProductAttachmentKnowledgeSelectionDataSetInterface::COLUMN_ABSTRACT_SKU];
        $localeName = (string)$dataSet[ProductAttachmentKnowledgeSelectionDataSetInterface::COLUMN_LOCALE];
        $url = (string)$dataSet[ProductAttachmentKnowledgeSelectionDataSetInterface::COLUMN_ATTACHMENT_URL];
        $value = (string)$dataSet[ProductAttachmentKnowledgeSelectionDataSetInterface::COLUMN_IS_ASSISTANT_KNOWLEDGE];

        if (!in_array($value, [static::VALUE_SELECTED, static::VALUE_NOT_SELECTED], true)) {
            throw new InvalidDataException(sprintf(
                static::ERROR_MESSAGE_INVALID_VALUE,
                $value,
                ProductAttachmentKnowledgeSelectionDataSetInterface::COLUMN_IS_ASSISTANT_KNOWLEDGE,
                $abstractSku,
                $localeName,
                $url,
                static::VALUE_SELECTED,
                static::VALUE_NOT_SELECTED,
            ));
        }

        $productAttachmentIds = $this->getMatchedProductAttachmentIds($abstractSku, $localeName, $url);

        if ($productAttachmentIds === []) {
            throw new EntityNotFoundException(sprintf(static::ERROR_MESSAGE_PRODUCT_ATTACHMENT_NOT_FOUND, $abstractSku, $localeName, $url));
        }

        $changedProductAttachmentIds = $this->saveProductAttachmentKnowledgeSelections($productAttachmentIds, $value === static::VALUE_SELECTED);

        foreach ($changedProductAttachmentIds as $idProductAttachment) {
            $this->addPublishEvents(AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH, $idProductAttachment);
        }
    }

    /**
     * @return list<int>
     */
    protected function getMatchedProductAttachmentIds(string $abstractSku, string $localeName, string $url): array
    {
        $isDefaultLocale = $localeName === '' || $localeName === static::LOCALE_NAME_DEFAULT;
        $idLocale = $isDefaultLocale ? null : $this->findIdLocale($localeName);

        if (!$isDefaultLocale && $idLocale === null) {
            return [];
        }

        $productAttachmentIds = [];

        foreach ($this->getProductAttachmentTransfersByAbstractSku($abstractSku) as $productAttachmentTransfer) {
            if ($productAttachmentTransfer->getFkLocale() === $idLocale && $productAttachmentTransfer->getUrl() === $url) {
                $productAttachmentIds[] = $productAttachmentTransfer->getIdProductAttachmentOrFail();
            }
        }

        return $productAttachmentIds;
    }

    protected function findIdLocale(string $localeName): ?int
    {
        if (array_key_exists($localeName, $this->idLocaleCache)) {
            return $this->idLocaleCache[$localeName];
        }

        $localeTransfers = $this->localeFacade->getLocaleCollection(
            (new LocaleCriteriaTransfer())->setLocaleConditions((new LocaleConditionsTransfer())->addLocaleName($localeName)),
        );

        $this->idLocaleCache[$localeName] = isset($localeTransfers[$localeName]) ? $localeTransfers[$localeName]->getIdLocaleOrFail() : null;

        return $this->idLocaleCache[$localeName];
    }

    /**
     * @return list<\Generated\Shared\Transfer\ProductAttachmentTransfer>
     */
    protected function getProductAttachmentTransfersByAbstractSku(string $abstractSku): array
    {
        if ($this->cachedAbstractSku === $abstractSku) {
            return $this->cachedProductAttachmentTransfers;
        }

        $this->cachedAbstractSku = $abstractSku;
        $this->cachedProductAttachmentTransfers = [];
        $idProductAbstract = $this->productFacade->findProductAbstractIdBySku($abstractSku);

        if ($idProductAbstract === null) {
            return $this->cachedProductAttachmentTransfers;
        }

        $productAttachmentCollectionTransfer = $this->productAttachmentFacade->getProductAttachmentCollection(
            (new ProductAttachmentCriteriaTransfer())->setProductAttachmentCondition(
                (new ProductAttachmentConditionTransfer())->addIdProductAbstract($idProductAbstract),
            ),
        );
        $this->cachedProductAttachmentTransfers = array_values($productAttachmentCollectionTransfer->getProductAttachments()->getArrayCopy());

        return $this->cachedProductAttachmentTransfers;
    }

    /**
     * @param list<int> $productAttachmentIds
     *
     * @return list<int>
     */
    protected function saveProductAttachmentKnowledgeSelections(array $productAttachmentIds, bool $isAssistantKnowledge): array
    {
        $storedProductAttachmentIds = $this->productAttachmentKnowledgeSelectionReader->getSelectedProductAttachmentIds($productAttachmentIds);

        if (!$isAssistantKnowledge) {
            $productAttachmentIdsToDelete = array_values(array_intersect($productAttachmentIds, $storedProductAttachmentIds));
            $this->aiCommerceEntityManager->deleteProductAttachmentKnowledgeSelections(
                (new ProductAttachmentKnowledgeSelectionConditionsTransfer())->setProductAttachmentIds($productAttachmentIdsToDelete),
            );

            return $productAttachmentIdsToDelete;
        }

        $productAttachmentIdsToCreate = array_values(array_diff($productAttachmentIds, $storedProductAttachmentIds));

        foreach ($productAttachmentIdsToCreate as $idProductAttachment) {
            $this->aiCommerceEntityManager->createProductAttachmentKnowledgeSelection(
                (new ProductAttachmentKnowledgeSelectionTransfer())->setIdProductAttachment($idProductAttachment),
            );
        }

        return $productAttachmentIdsToCreate;
    }
}
