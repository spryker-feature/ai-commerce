<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category;

use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class CategoryTreeFormatter implements CategoryTreeFormatterInterface
{
    protected const string LINE_INDENT = '  ';

    protected const string LINE_FORMAT = '%s%d %s';

    protected const string LINE_SEPARATOR = "\n";

    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
        protected StoreClientInterface $storeClient,
        protected LocaleClientInterface $localeClient,
        protected int $maxCategoryCount
    ) {
    }

    public function formatCategoryTree(): string
    {
        $categoryLines = [];
        $this->collectCategoryLines($this->getCategoryNodeStorageTransfers(), 0, $categoryLines);

        return implode(static::LINE_SEPARATOR, array_slice($categoryLines, 0, $this->maxCategoryCount));
    }

    public function findIdCategoryNodeByName(string $categoryName): ?int
    {
        $categoryName = trim($categoryName);

        if ($categoryName === '') {
            return null;
        }

        $categoryNodeIds = [];
        $this->collectIdsCategoryNodeByName($this->getCategoryNodeStorageTransfers(), $categoryName, $categoryNodeIds);

        return count($categoryNodeIds) === 1 ? $categoryNodeIds[0] : null;
    }

    /**
     * @return iterable<\Generated\Shared\Transfer\CategoryNodeStorageTransfer>
     */
    protected function getCategoryNodeStorageTransfers(): iterable
    {
        return $this->categoryStorageClient->getCategories(
            $this->localeClient->getCurrentLocale(),
            $this->storeClient->getCurrentStore()->getNameOrFail(),
        );
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\CategoryNodeStorageTransfer> $categoryNodeStorageTransfers
     * @param array<int, string> $categoryLines
     */
    protected function collectCategoryLines(
        iterable $categoryNodeStorageTransfers,
        int $depth,
        array &$categoryLines
    ): void {
        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            $categoryLine = $this->formatCategoryLine($categoryNodeStorageTransfer, $depth);

            if ($categoryLine !== null) {
                $categoryLines[] = $categoryLine;
            }

            $this->collectCategoryLines(
                $categoryNodeStorageTransfer->getChildren(),
                $categoryLine !== null ? $depth + 1 : $depth,
                $categoryLines,
            );
        }
    }

    protected function formatCategoryLine(
        CategoryNodeStorageTransfer $categoryNodeStorageTransfer,
        int $depth
    ): ?string {
        if ($categoryNodeStorageTransfer->getIsActive() === false) {
            return null;
        }

        $idCategoryNode = $categoryNodeStorageTransfer->getNodeId();
        $name = $this->normalizeCategoryName((string)$categoryNodeStorageTransfer->getName());

        if ($idCategoryNode === null || $name === '') {
            return null;
        }

        return sprintf(static::LINE_FORMAT, str_repeat(static::LINE_INDENT, $depth), $idCategoryNode, $name);
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\CategoryNodeStorageTransfer> $categoryNodeStorageTransfers
     * @param array<int, int> $categoryNodeIds
     */
    protected function collectIdsCategoryNodeByName(
        iterable $categoryNodeStorageTransfers,
        string $categoryName,
        array &$categoryNodeIds
    ): void {
        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            $idCategoryNode = $categoryNodeStorageTransfer->getNodeId();
            $name = $this->normalizeCategoryName((string)$categoryNodeStorageTransfer->getName());

            if ($idCategoryNode !== null && $categoryNodeStorageTransfer->getIsActive() !== false && $name === $categoryName) {
                $categoryNodeIds[] = $idCategoryNode;
            }

            $this->collectIdsCategoryNodeByName(
                $categoryNodeStorageTransfer->getChildren(),
                $categoryName,
                $categoryNodeIds,
            );
        }
    }

    protected function normalizeCategoryName(string $categoryName): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $categoryName));
    }
}
