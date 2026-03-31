<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Executor;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class SearchByImageExecutor implements SearchByImageExecutorInterface
{
    protected const string GLOSSARY_KEY_SEARCH_BY_IMAGE_DISABLED = 'ai_commerce.search_by_image.error.disabled';

    /**
     * @param \Spryker\Shared\Kernel\StrategyResolverInterface<\SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\RedirectResolverInterface> $redirectStrategyResolver
     */
    public function __construct(
        protected AiCommerceClientInterface $aiCommerceClient,
        protected StrategyResolverInterface $redirectStrategyResolver,
        protected AiCommerceConfig $config,
    ) {
    }

    public function execute(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer
    {
        if (!$this->config->isSearchByImageEnabled()) {
            return (new SearchByImageResponseTransfer())
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SEARCH_BY_IMAGE_DISABLED))
                ->setIsSuccessful(false);
        }

        $searchByImageResponseTransfer = $this->aiCommerceClient->getSearchTermFromImage($searchByImageRequestTransfer);

        if (!$searchByImageResponseTransfer->getIsSuccessful()) {
            return $searchByImageResponseTransfer;
        }

        $redirectUrl = $this->redirectStrategyResolver
            ->get($this->config->getRedirectType())
            ->resolve(
                $searchByImageResponseTransfer->getSearchTermOrFail(),
            );

        return $searchByImageResponseTransfer->setRedirectUrl($redirectUrl);
    }
}
