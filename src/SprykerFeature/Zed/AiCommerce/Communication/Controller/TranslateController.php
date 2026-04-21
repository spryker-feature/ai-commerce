<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use Generated\Shared\Transfer\AiTranslationCollectionRequestTransfer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslateController extends AbstractAiCommerceController
{
    protected const string PARAM_TEXT = 'text';

    protected const string PARAM_LOCALES = 'locales';

    protected const string PARAM_SOURCE_LOCALE = 'sourceLocale';

    protected const string KEY_ERROR = 'error';

    protected const string KEY_MESSAGE = 'message';

    protected const string KEY_TRANSLATIONS = 'translations';

    protected const string KEY_ERRORS = 'errors';

    public function indexAction(Request $request): JsonResponse
    {
        $text = $request->get(static::PARAM_TEXT);
        $locales = $request->get(static::PARAM_LOCALES, []);
        $sourceLocale = $request->get(static::PARAM_SOURCE_LOCALE);

        if (!$text || !$locales) {
            return $this->jsonResponse(
                [
                    static::KEY_ERROR => 'Bad request',
                    static::KEY_MESSAGE => 'Text and/or target locales are missing from request.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $requestCollectionTransfer = (new AiTranslationCollectionRequestTransfer())
            ->setText($text)
            ->setSourceLocale($sourceLocale)
            ->setTargetLocales($locales);

        $responseCollectionTransfer = $this->getFacade()->translateCollection($requestCollectionTransfer);

        if (!$responseCollectionTransfer->getIsSuccessful()) {
            return $this->jsonResponse(
                [static::KEY_ERRORS => $this->formatErrors($responseCollectionTransfer->getErrors())],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse([
            static::KEY_TRANSLATIONS => $responseCollectionTransfer->getTranslations(),
        ]);
    }
}
