<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce;

use ArrayObject;
use Codeception\Actor;
use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReaderConfigurationTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;
use Generated\Shared\Transfer\ProductAttachmentTransfer;
use Orm\Zed\AiCommerce\Persistence\Map\SpyProductAttachmentKnowledgeSelectionTableMap;
use Orm\Zed\AiCommerce\Persistence\SpyProductAttachmentKnowledgeSelection;
use Orm\Zed\AiCommerce\Persistence\SpyProductAttachmentKnowledgeSelectionQuery;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachment;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachmentProductAbstract;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachmentQuery;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Propel;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AiCommerceCommunicationTester extends Actor
{
    use _generated\AiCommerceCommunicationTesterActions;

    /**
     * @var list<string>
     */
    protected const array PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CSV_HEADER = ['abstract_sku', 'locale', 'attachment_url', 'is_assistant_knowledge'];

    public function haveProductAttachment(?int $idProductAbstract = null, ?int $idLocale = null): ProductAttachmentTransfer
    {
        $productAttachmentEntity = (new SpyProductAttachment())
            ->setLabel(uniqid('Attachment ', true))
            ->setUrl(sprintf('https://docs.example.com/%s.pdf', uniqid('', true)))
            ->setFkLocale($idLocale);
        $productAttachmentEntity->save();

        if ($idProductAbstract !== null) {
            $this->haveProductAbstractAttachmentRelation($idProductAbstract, $productAttachmentEntity->getIdProductAttachment());
        }

        return (new ProductAttachmentTransfer())
            ->setIdProductAttachment($productAttachmentEntity->getIdProductAttachment())
            ->setFkLocale($productAttachmentEntity->getFkLocale())
            ->setLabel($productAttachmentEntity->getLabel())
            ->setUrl($productAttachmentEntity->getUrl());
    }

    public function haveProductAbstractAttachmentRelation(int $idProductAbstract, int $idProductAttachment): void
    {
        (new SpyProductAttachmentProductAbstract())
            ->setFkProductAbstract($idProductAbstract)
            ->setFkProductAttachment($idProductAttachment)
            ->save();
    }

    public function haveProductAttachmentKnowledgeSelection(int $idProductAttachment): void
    {
        (new SpyProductAttachmentKnowledgeSelection())
            ->setFkProductAttachment($idProductAttachment)
            ->save();
    }

    public function countProductAttachmentKnowledgeSelections(int $idProductAttachment): int
    {
        return SpyProductAttachmentKnowledgeSelectionQuery::create()
            ->filterByFkProductAttachment($idProductAttachment)
            ->count();
    }

    public function isProductAttachmentKnowledgeSelected(int $idProductAttachment): bool
    {
        return $this->countProductAttachmentKnowledgeSelections($idProductAttachment) > 0;
    }

    public function hasProductAttachment(int $idProductAttachment): bool
    {
        return SpyProductAttachmentQuery::create()
            ->filterByIdProductAttachment($idProductAttachment)
            ->exists();
    }

    public function findProductAttachmentIdByUrl(string $url): ?int
    {
        return SpyProductAttachmentQuery::create()
            ->filterByUrl($url)
            ->findOne()
            ?->getIdProductAttachment();
    }

    public function createProductAttachmentCollection(ProductAttachmentTransfer ...$productAttachmentTransfers): ProductAttachmentCollectionTransfer
    {
        return (new ProductAttachmentCollectionTransfer())
            ->setProductAttachments(new ArrayObject($productAttachmentTransfers));
    }

    public function haveCommittedProductAttachment(int $idProductAbstract, ?int $idLocale, string $url): int
    {
        $productAttachmentEntity = (new SpyProductAttachment())
            ->setLabel(uniqid('Attachment ', true))
            ->setUrl($url)
            ->setFkLocale($idLocale);
        $productAttachmentEntity->save();

        $idProductAttachment = $productAttachmentEntity->getIdProductAttachment();
        $this->haveProductAbstractAttachmentRelation($idProductAbstract, $idProductAttachment);

        $this->addCleanup(function () use ($idProductAttachment): void {
            SpyProductAttachmentQuery::create()->filterByIdProductAttachment($idProductAttachment)->delete();
        });

        return $idProductAttachment;
    }

    /**
     * @param list<list<string>> $rows
     */
    public function createProductAttachmentKnowledgeSelectionImportConfiguration(array $rows): DataImporterConfigurationTransfer
    {
        $fileName = sprintf('%s%s.csv', codecept_output_dir(), uniqid('product_attachment_knowledge_selection_', true));
        $fileHandle = fopen($fileName, 'w');
        fputcsv($fileHandle, static::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CSV_HEADER, ',', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($fileHandle, $row, ',', '"', '\\');
        }

        fclose($fileHandle);

        $this->addCleanup(function () use ($fileName): void {
            unlink($fileName);
        });

        return (new DataImporterConfigurationTransfer())
            ->setReaderConfiguration((new DataImporterReaderConfigurationTransfer())->setFileName($fileName));
    }

    public function getLocaleTransfer(string $localeName): LocaleTransfer
    {
        return $this->getLocator()->locale()->facade()->getLocale($localeName);
    }

    public function isPostgreSql(): bool
    {
        return Propel::getServiceContainer()->getAdapter(SpyProductAttachmentKnowledgeSelectionTableMap::DATABASE_NAME) instanceof PgsqlAdapter;
    }

    /**
     * @param callable(): mixed $callback
     */
    public function countQueries(callable $callback): int
    {
        /** @var \Propel\Runtime\Connection\ConnectionWrapper $connection */
        $connection = Propel::getWriteConnection(SpyProductAttachmentKnowledgeSelectionTableMap::DATABASE_NAME);
        $connection->useDebug(false);
        $connection->useDebug(true);

        try {
            $callback();

            return $connection->getQueryCount();
        } finally {
            $connection->useDebug(false);
        }
    }
}
