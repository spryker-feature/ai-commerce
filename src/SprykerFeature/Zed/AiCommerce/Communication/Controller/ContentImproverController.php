<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use Generated\Shared\Transfer\ContentImprovementRequestTransfer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentImproverController extends AbstractAiCommerceController
{
    protected const string PARAM_TEXT = 'text';

    protected const string KEY_ERROR = 'error';

    protected const string KEY_IMPROVED_TEXT = 'improvedText';

    public function indexAction(Request $request): JsonResponse
    {
        $text = $request->get(static::PARAM_TEXT);

        if (!$text) {
            return $this->jsonResponse(
                [
                    'error' => 'Bad request',
                    'message' => 'Text is missing from request.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $contentImprovementRequestTransfer = (new ContentImprovementRequestTransfer())
            ->setText($text);

        $contentImprovementResponseTransfer = $this->getFacade()->improveContent($contentImprovementRequestTransfer);

        if (!$contentImprovementResponseTransfer->getIsSuccessful()) {
            return $this->jsonResponse(
                [static::KEY_ERROR => $this->formatErrors($contentImprovementResponseTransfer->getErrors())],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse([
            static::KEY_IMPROVED_TEXT => $contentImprovementResponseTransfer->getImprovedTextOrFail(),
        ]);
    }
}
