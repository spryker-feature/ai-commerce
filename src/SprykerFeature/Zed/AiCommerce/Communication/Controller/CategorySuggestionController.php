<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use ArrayObject;
use Generated\Shared\Transfer\CategorySuggestionRequestTransfer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategorySuggestionController extends AbstractAiCommerceController
{
    protected const string PARAM_PRODUCT_NAME = 'product_name';

    protected const string PARAM_PRODUCT_DESCRIPTION = 'product_description';

    public function indexAction(Request $request): JsonResponse
    {
        $productName = $request->get(static::PARAM_PRODUCT_NAME);
        $description = $request->get(static::PARAM_PRODUCT_DESCRIPTION);

        if (!$productName || !$description) {
            return $this->jsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $categorySuggestionRequestTransfer = (new CategorySuggestionRequestTransfer())
            ->setProductName($productName)
            ->setProductDescription($description);

        $categorySuggestionResponseTransfer = $this->getFacade()
            ->proposeCategorySuggestions($categorySuggestionRequestTransfer);

        if (!$categorySuggestionResponseTransfer->getIsSuccessful()) {
            return $this->jsonResponse(
                ['errors' => $this->formatErrors($categorySuggestionResponseTransfer->getErrors())],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse([
            'categories' => $this->formatSuggestions($categorySuggestionResponseTransfer->getSuggestions()),
        ]);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\CategorySuggestionTransfer> $suggestions
     *
     * @return array<string, int>
     */
    protected function formatSuggestions(ArrayObject $suggestions): array
    {
        $formatted = [];

        foreach ($suggestions as $suggestionTransfer) {
            $formatted[$suggestionTransfer->getCategoryNameOrFail()] = $suggestionTransfer->getIdCategoryOrFail();
        }

        return $formatted;
    }
}
