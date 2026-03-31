<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\Controller;

use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 */
class SearchByImageController extends AbstractController
{
    protected const string GLOSSARY_KEY_ERROR_SEARCH_FAILED = 'ai_commerce.search_by_image.error.search_failed';

    protected const string GLOSSARY_KEY_ERROR_NO_IMAGE_PROVIDED = 'ai_commerce.search_by_image.error.no_image_provided';

    public function searchAction(Request $request): JsonResponse
    {
        $form = $this->getFactory()->getSearchByImageForm();
        $form->handleRequest($request);
        $locale = $this->getLocale();

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->createErrorResponse($this->collectFormErrors($form), $locale);
        }

        /** @var \Generated\Shared\Transfer\SearchByImageRequestTransfer $searchByImageRequestTransfer */
        $searchByImageRequestTransfer = $form->getData();
        $searchByImageRequestTransfer->setLocaleName($locale);

        $searchByImageResponseTransfer = $this->getFactory()->createSearchByImageExecutor()->execute($searchByImageRequestTransfer);

        if (!$searchByImageResponseTransfer->getIsSuccessful()) {
            $errors = array_map(
                fn (ErrorTransfer $error) => (string)$error->getMessage(),
                $searchByImageResponseTransfer->getErrors()->getArrayCopy(),
            ) ?: [static::GLOSSARY_KEY_ERROR_SEARCH_FAILED];

            return $this->createErrorResponse($errors, $locale);
        }

        return $this->jsonResponse(['isSuccessful' => true, 'redirectUrl' => $searchByImageResponseTransfer->getRedirectUrl()]);
    }

    /**
     * @param array<string> $glossaryKeys
     */
    protected function createErrorResponse(array $glossaryKeys, string $locale): JsonResponse
    {
        return $this->jsonResponse([
                'isSuccessful' => false,
                'errors' => array_values($this->getFactory()->getGlossaryStorageClient()->translateBulk($glossaryKeys, $locale)),
            ]);
    }

    /**
     * @return array<string>
     */
    protected function collectFormErrors(FormInterface $form): array
    {
        if (!$form->isSubmitted()) {
            return [static::GLOSSARY_KEY_ERROR_NO_IMAGE_PROVIDED];
        }

        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            if (!$error instanceof FormError) {
                continue;
            }

            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
