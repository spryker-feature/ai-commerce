<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class BackofficeAssistantPromptController extends AbstractController
{
    protected const string REQUEST_KEY_PROMPT = 'prompt';

    protected const string REQUEST_KEY_CONVERSATION_REFERENCE = 'conversation_reference';

    protected const string REQUEST_KEY_SELECTED_AGENT = 'selected_agent';

    protected const string REQUEST_KEY_CONTEXT = 'context';

    protected const string REQUEST_KEY_ATTACHMENTS = 'attachments';

    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    protected const string HEADER_CACHE_CONTROL = 'Cache-Control';

    protected const string HEADER_X_ACCEL_BUFFERING = 'X-Accel-Buffering';

    protected const string HEADER_VALUE_CONTENT_TYPE = 'text/event-stream';

    protected const string HEADER_VALUE_CACHE_CONTROL = 'no-cache';

    protected const string HEADER_VALUE_X_ACCEL_BUFFERING = 'no';

    protected const string CSRF_TOKEN_ID = 'backoffice-assistant';

    protected const string CSRF_TOKEN_PARAM = '_token';

    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string ERROR_BACKOFFICE_ASSISTANT_DISABLED = 'backoffice_assistant.error.disabled';

    protected const string ERROR_INVALID_CSRF_TOKEN = 'backoffice_assistant.error.invalid_csrf_token';

    public function indexAction(Request $request): JsonResponse|StreamedResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED)], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $token = (string)($data[static::CSRF_TOKEN_PARAM] ?? '');

        if (!$this->isValidCsrfToken($token)) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_INVALID_CSRF_TOKEN)], 403);
        }

        return new StreamedResponse(function () use ($data): void {
            set_time_limit(120);

            $userUuid = $this->getFactory()->getUserFacade()->getCurrentUser()->getUuidOrFail();

            $promptRequestTransfer = (new BackofficeAssistantPromptRequestTransfer())
                ->setPrompt($data[static::REQUEST_KEY_PROMPT] ?? '')
                ->setConversationReference($data[static::REQUEST_KEY_CONVERSATION_REFERENCE] ?? '')
                ->setSelectedAgent($data[static::REQUEST_KEY_SELECTED_AGENT] ?? '')
                ->setContext($data[static::REQUEST_KEY_CONTEXT] ?? [])
                ->setUserUuid($userUuid)
                ->setRawAttachments($data[static::REQUEST_KEY_ATTACHMENTS] ?? []);

            $this->getFacade()->handleBackofficeAssistantPrompt($promptRequestTransfer);
        }, 200, [
            static::HEADER_CONTENT_TYPE => static::HEADER_VALUE_CONTENT_TYPE,
            static::HEADER_CACHE_CONTROL => static::HEADER_VALUE_CACHE_CONTROL,
            static::HEADER_X_ACCEL_BUFFERING => static::HEADER_VALUE_X_ACCEL_BUFFERING,
        ]);
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
