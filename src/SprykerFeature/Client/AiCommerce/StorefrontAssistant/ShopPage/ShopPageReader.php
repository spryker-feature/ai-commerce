<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage;

use DateTime;
use Exception;
use Generated\Shared\Transfer\CmsPageStorageTransfer;
use Spryker\Client\CmsStorage\CmsStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class ShopPageReader implements ShopPageReaderInterface
{
    public const string PARAMETER_ID_CMS_PAGE = 'idCmsPage';

    public const string RESULT_KEY_ERROR = 'error';

    public const string RESULT_KEY_FOUND = 'found';

    public const string RESULT_KEY_REASON = 'reason';

    public const string RESULT_KEY_ID_CMS_PAGE = 'idCmsPage';

    public const string RESULT_KEY_NAME = 'name';

    public const string RESULT_KEY_URL = 'url';

    public const string RESULT_KEY_TEXT = 'text';

    protected const string ERROR_MISSING_ID_CMS_PAGE = 'Pass the idCmsPage of a cms_page result from catalog_suggest.';

    protected const string REASON_NOT_AVAILABLE = 'The shop has no available page with idCmsPage %d. Do not answer from it.';

    public function __construct(
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected CmsStorageClientInterface $cmsStorageClient,
        protected ShopPageTextExtractorInterface $shopPageTextExtractor,
        protected StoreClientInterface $storeClient,
        protected LocaleClientInterface $localeClient
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function readShopPage(array $arguments): array
    {
        $idCmsPage = $this->extractIdCmsPage($this->toolArgumentNormalizer->normalizeArguments($arguments));

        if ($idCmsPage === null) {
            return [static::RESULT_KEY_ERROR => static::ERROR_MISSING_ID_CMS_PAGE];
        }

        $cmsPageStorageTransfer = $this->findCmsPageStorage($idCmsPage);

        if ($cmsPageStorageTransfer === null || !$this->isAvailable($cmsPageStorageTransfer, new DateTime())) {
            return $this->createNotAvailableResult($idCmsPage);
        }

        $text = $this->shopPageTextExtractor->extractText($cmsPageStorageTransfer->getPlaceholders());

        if ($text === '') {
            return $this->createNotAvailableResult($idCmsPage);
        }

        return [
            static::RESULT_KEY_FOUND => true,
            static::RESULT_KEY_ID_CMS_PAGE => $idCmsPage,
            static::RESULT_KEY_NAME => (string)$cmsPageStorageTransfer->getName(),
            static::RESULT_KEY_URL => (string)$cmsPageStorageTransfer->getUrl(),
            static::RESULT_KEY_TEXT => $text,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createNotAvailableResult(int $idCmsPage): array
    {
        return [
            static::RESULT_KEY_FOUND => false,
            static::RESULT_KEY_REASON => sprintf(static::REASON_NOT_AVAILABLE, $idCmsPage),
        ];
    }

    protected function findCmsPageStorage(int $idCmsPage): ?CmsPageStorageTransfer
    {
        $cmsPageStorageTransfers = $this->cmsStorageClient->getCmsPageStorageByIds(
            [$idCmsPage],
            $this->localeClient->getCurrentLocale(),
            $this->storeClient->getCurrentStore()->getNameOrFail(),
        );

        foreach ($cmsPageStorageTransfers as $cmsPageStorageTransfer) {
            if ($cmsPageStorageTransfer->getIdCmsPage() === $idCmsPage) {
                return $cmsPageStorageTransfer;
            }
        }

        return null;
    }

    protected function isAvailable(CmsPageStorageTransfer $cmsPageStorageTransfer, DateTime $now): bool
    {
        if ($cmsPageStorageTransfer->getIsActive() !== true) {
            return false;
        }

        $validFrom = $this->createDateTime($cmsPageStorageTransfer->getValidFrom());
        $validTo = $this->createDateTime($cmsPageStorageTransfer->getValidTo());

        if ($validFrom === false || $validTo === false) {
            return false;
        }

        return ($validFrom === null || $now >= $validFrom) && ($validTo === null || $now <= $validTo);
    }

    protected function createDateTime(?string $date): DateTime|false|null
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return new DateTime($date);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractIdCmsPage(array $arguments): ?int
    {
        $idCmsPage = $arguments[static::PARAMETER_ID_CMS_PAGE] ?? null;

        if (is_bool($idCmsPage) || !is_numeric($idCmsPage) || (int)$idCmsPage <= 0) {
            return null;
        }

        return (int)$idCmsPage;
    }
}
