<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use ArrayObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class SmartCmsContentController extends AbstractAiCommerceController
{
    protected const string CSRF_TOKEN_ID = 'smart-cms-content';

    protected const string CSRF_TOKEN_PARAM = '_token';

    protected const string PAYLOAD_KEY_ATTACHMENTS = 'attachments';

    protected const string KEY_MEDIA_TYPE = 'mediaType';

    protected const string KEY_CONTENT = 'content';

    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string RESPONSE_KEY_EXPLANATION = 'explanation';

    protected const string RESPONSE_KEY_PLACEHOLDERS = 'placeholders';

    protected const string ERROR_SMART_CMS_DISABLED = 'smart_cms_content.error.disabled';

    protected const string ERROR_INVALID_CSRF_TOKEN = 'smart_cms_content.error.invalid_csrf_token';

    public function generateAction(Request $request): JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isSmartCmsEnabled()) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->translateError(static::ERROR_SMART_CMS_DISABLED)], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (!is_array($data)) {
            $data = [];
        }

        if (!$this->isValidCsrfToken((string)($data[static::CSRF_TOKEN_PARAM] ?? ''))) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->translateError(static::ERROR_INVALID_CSRF_TOKEN)], Response::HTTP_FORBIDDEN);
        }

        $rawAttachments = $this->extractRawAttachments($data);
        $attachmentErrors = $this->getFactory()->createAttachmentValidator()->validateRawAttachments($rawAttachments);

        if ($attachmentErrors !== []) {
            return $this->jsonResponse(
                [static::RESPONSE_KEY_ERROR => $this->formatErrors(new ArrayObject($attachmentErrors))],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $smartCmsContentRequestTransfer = $this->getFactory()
            ->createSmartCmsContentRequestMapper()
            ->mapPayloadToSmartCmsContentRequestTransfer($data);

        foreach ($this->getFactory()->createAttachmentBuilder()->buildAttachmentTransfers($rawAttachments) as $attachmentTransfer) {
            $smartCmsContentRequestTransfer->addAttachment($attachmentTransfer);
        }

        $smartCmsContentResponseTransfer = $this->getFacade()->generateCmsContent($smartCmsContentRequestTransfer);

        if (!$smartCmsContentResponseTransfer->getIsSuccessful()) {
            return $this->jsonResponse(
                [static::RESPONSE_KEY_ERROR => $this->formatErrors($smartCmsContentResponseTransfer->getErrors())],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse([
            static::RESPONSE_KEY_EXPLANATION => $smartCmsContentResponseTransfer->getExplanation(),
            static::RESPONSE_KEY_PLACEHOLDERS => $this->getFactory()
                ->createSmartCmsContentResponseMapper()
                ->mapSmartCmsContentResponseTransferToPlaceholderArray($smartCmsContentResponseTransfer),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<string, string>>
     */
    protected function extractRawAttachments(array $data): array
    {
        $rawAttachments = $data[static::PAYLOAD_KEY_ATTACHMENTS] ?? [];

        if (!is_array($rawAttachments)) {
            return [];
        }

        $normalizedAttachments = [];

        foreach ($rawAttachments as $rawAttachment) {
            if (!is_array($rawAttachment)) {
                continue;
            }

            $normalizedAttachments[] = [
                static::KEY_MEDIA_TYPE => (string)($rawAttachment[static::KEY_MEDIA_TYPE] ?? ''),
                static::KEY_CONTENT => (string)($rawAttachment[static::KEY_CONTENT] ?? ''),
            ];
        }

        return $normalizedAttachments;
    }

    protected function translateError(string $key): string
    {
        $glossaryFacade = $this->getFactory()->getGlossaryFacade();

        // The error keys ship as Zed translation CSVs for the Symfony translator and may not exist in the
        // runtime glossary store; fall back to the key itself so the response stays a clean 403/422.
        if (!$glossaryFacade->hasTranslation($key)) {
            return $key;
        }

        return $glossaryFacade->translate($key);
    }

    protected function isValidCsrfToken(string $token): bool
    {
        if (!$token) {
            return false;
        }

        return $this->getFactory()
            ->getCsrfTokenManager()
            ->isTokenValid(new CsrfToken(static::CSRF_TOKEN_ID, $token));
    }
}
