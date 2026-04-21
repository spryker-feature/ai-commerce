<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use Generated\Shared\Transfer\ImageAltTextRequestTransfer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImageAltTextController extends AbstractAiCommerceController
{
    protected const string PARAM_IMAGE_URL = 'imageUrl';

    protected const string PARAM_LOCALE = 'locale';

    protected const string KEY_ERROR = 'error';

    protected const string KEY_ALT_TEXT = 'altText';

    public function indexAction(Request $request): JsonResponse
    {
        $imageUrl = $request->get(static::PARAM_IMAGE_URL);
        $targetLocale = $request->get(static::PARAM_LOCALE);

        if (!$imageUrl || !$targetLocale) {
            return $this->jsonResponse(
                [
                    'error' => 'Bad request',
                    'message' => 'ImageUrl and/or target locale are missing from request.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $imageAltTextRequestTransfer = (new ImageAltTextRequestTransfer())
            ->setImageUrl($imageUrl)
            ->setTargetLocale($targetLocale);

        $imageAltTextResponseTransfer = $this->getFacade()
            ->generateImageAltText($imageAltTextRequestTransfer);

        if (!$imageAltTextResponseTransfer->getIsSuccessful()) {
            return $this->jsonResponse(
                [static::KEY_ERROR => $this->formatErrors($imageAltTextResponseTransfer->getErrors())],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse([
            static::KEY_ALT_TEXT => $imageAltTextResponseTransfer->getAltTextOrFail(),
        ]);
    }
}
