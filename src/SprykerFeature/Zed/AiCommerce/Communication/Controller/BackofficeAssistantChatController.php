<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use JsonException;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * The chat contract the storefront assistant speaks (camel-case JSON, the CSRF token in a header, a delete of several
 * conversations in one request), so the shared chat uses one API in both applications.
 *
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class BackofficeAssistantChatController extends AbstractController
{
    protected const string CSRF_TOKEN_ID = 'backoffice-assistant';

    protected const string REQUEST_HEADER_CSRF_TOKEN = 'X-CSRF-Token';

    protected const string REQUEST_HEADER_REFERER = 'referer';

    protected const string REQUEST_KEY_CONVERSATION_REFERENCE = 'conversationReference';

    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string RESPONSE_KEY_DELETED_COUNT = 'deletedCount';

    protected const string ERROR_BACKOFFICE_ASSISTANT_DISABLED = 'backoffice_assistant.error.disabled';

    protected const string ERROR_INVALID_CSRF_TOKEN = 'backoffice_assistant.error.invalid_csrf_token';

    protected const string ERROR_INVALID_PAYLOAD = 'backoffice_assistant.error.invalid_payload';

    protected const string ERROR_MISSING_CONVERSATION_REFERENCE = 'backoffice_assistant.error.missing_conversation_reference';

    protected const string ERROR_CONVERSATION_NOT_FOUND = 'backoffice_assistant.error.conversation_not_found';

    protected const string ERROR_METHOD_NOT_ALLOWED = 'backoffice_assistant.error.method_not_allowed';

    protected const string HEADER_ALLOW = 'Allow';

    protected const int STREAM_TIME_LIMIT_IN_SECONDS = 120;

    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    protected const string HEADER_CACHE_CONTROL = 'Cache-Control';

    protected const string HEADER_X_ACCEL_BUFFERING = 'X-Accel-Buffering';

    protected const string HEADER_VALUE_CONTENT_TYPE = 'text/event-stream';

    protected const string HEADER_VALUE_CACHE_CONTROL = 'no-cache';

    protected const string HEADER_VALUE_X_ACCEL_BUFFERING = 'no';

    public function promptAction(Request $request): JsonResponse|StreamedResponse
    {
        $refusal = $this->findRequestRefusal($request, Request::METHOD_POST);

        if ($refusal !== null) {
            return $refusal;
        }

        $payload = $this->findPayload($request);

        if ($payload === null || !$this->getFactory()->createBackofficeAssistantChatPayloadValidator()->isPromptPayloadValid($payload)) {
            return $this->createErrorResponse(static::ERROR_INVALID_PAYLOAD, Response::HTTP_BAD_REQUEST);
        }

        try {
            $backofficeAssistantPromptRequestTransfer = $this->getFactory()
                ->createBackofficeAssistantChatMapper()
                ->mapPayloadToBackofficeAssistantPromptRequestTransfer($payload, $this->readCurrentPage($request), $this->getIdUser());
        } catch (JsonException) {
            return $this->createErrorResponse(static::ERROR_INVALID_PAYLOAD, Response::HTTP_BAD_REQUEST);
        }

        return $this->createPromptStreamResponse($backofficeAssistantPromptRequestTransfer);
    }

    public function conversationsAction(): JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->createErrorResponse(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED, Response::HTTP_FORBIDDEN);
        }

        $backofficeAssistantConversationCollectionTransfer = $this->getConversationCollection(
            $this->createConversationConditions()->setLimit($this->getFactory()->getConfig()->getConversationListLimit()),
        );

        return $this->jsonResponse(
            $this->getFactory()
                ->createBackofficeAssistantChatMapper()
                ->mapBackofficeAssistantConversationCollectionToArray(
                    $backofficeAssistantConversationCollectionTransfer,
                    $this->getFactory()->createBackofficeAssistantAgentReader()->getAvailableAgents(),
                ),
        );
    }

    public function conversationMessagesAction(Request $request): JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->createErrorResponse(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED, Response::HTTP_FORBIDDEN);
        }

        $conversationReference = (string)$request->query->get(static::REQUEST_KEY_CONVERSATION_REFERENCE, '');

        if ($conversationReference === '') {
            return $this->createErrorResponse(static::ERROR_MISSING_CONVERSATION_REFERENCE, Response::HTTP_BAD_REQUEST);
        }

        $backofficeAssistantConversationCollectionTransfer = $this->getConversationCollection(
            $this->createConversationConditions()->addConversationReference($conversationReference)->setWithMessages(true),
        );

        if ($backofficeAssistantConversationCollectionTransfer->getBackofficeAssistantConversations()->count() === 0) {
            return $this->createErrorResponse(static::ERROR_CONVERSATION_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        return $this->jsonResponse(
            $this->getFactory()
                ->createBackofficeAssistantChatMapper()
                ->mapBackofficeAssistantConversationToMessagesArray(
                    $backofficeAssistantConversationCollectionTransfer->getBackofficeAssistantConversations()->offsetGet(0),
                ),
        );
    }

    public function deleteConversationAction(Request $request): JsonResponse
    {
        $refusal = $this->findRequestRefusal($request, Request::METHOD_DELETE);

        if ($refusal !== null) {
            return $refusal;
        }

        $payload = $this->findPayload($request);

        if ($payload === null || !$this->getFactory()->createBackofficeAssistantChatPayloadValidator()->isConversationDeletePayloadValid($payload)) {
            return $this->createErrorResponse(static::ERROR_INVALID_PAYLOAD, Response::HTTP_BAD_REQUEST);
        }

        $backofficeAssistantConversationCollectionDeleteCriteriaTransfer = $this->getFactory()
            ->createBackofficeAssistantChatMapper()
            ->mapPayloadToBackofficeAssistantConversationCollectionDeleteCriteriaTransfer($payload, $this->getIdUser());

        $backofficeAssistantConversationCollectionResponseTransfer = $this->getFacade()
            ->deleteBackofficeAssistantConversationCollection($backofficeAssistantConversationCollectionDeleteCriteriaTransfer);

        if ($backofficeAssistantConversationCollectionResponseTransfer->getErrors()->count() > 0) {
            return $this->createErrorResponse(static::ERROR_CONVERSATION_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        return $this->jsonResponse([
            static::RESPONSE_KEY_DELETED_COUNT => count($backofficeAssistantConversationCollectionDeleteCriteriaTransfer->getConversationReferences()),
        ]);
    }

    protected function findRequestRefusal(Request $request, string $allowedMethod): ?JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->createErrorResponse(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED, Response::HTTP_FORBIDDEN);
        }

        if (!$request->isMethod($allowedMethod)) {
            $errorResponse = $this->createErrorResponse(static::ERROR_METHOD_NOT_ALLOWED, Response::HTTP_METHOD_NOT_ALLOWED);
            $errorResponse->headers->set(static::HEADER_ALLOW, $allowedMethod);

            return $errorResponse;
        }

        $csrfToken = new CsrfToken(static::CSRF_TOKEN_ID, (string)$request->headers->get(static::REQUEST_HEADER_CSRF_TOKEN));

        if (!$this->getFactory()->getCsrfTokenManager()->isTokenValid($csrfToken)) {
            return $this->createErrorResponse(static::ERROR_INVALID_CSRF_TOKEN, Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findPayload(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : null;
    }

    protected function createConversationConditions(): BackofficeAssistantConversationConditionsTransfer
    {
        return (new BackofficeAssistantConversationConditionsTransfer())->addIdUser($this->getIdUser());
    }

    protected function getConversationCollection(
        BackofficeAssistantConversationConditionsTransfer $backofficeAssistantConversationConditionsTransfer,
    ): BackofficeAssistantConversationCollectionTransfer {
        return $this->getFacade()->getBackofficeAssistantConversationCollection(
            (new BackofficeAssistantConversationCriteriaTransfer())
                ->setBackofficeAssistantConversationConditions($backofficeAssistantConversationConditionsTransfer),
        );
    }

    protected function getIdUser(): int
    {
        return $this->getFactory()->getUserFacade()->getCurrentUser()->getIdUserOrFail();
    }

    protected function readCurrentPage(Request $request): string
    {
        // The page the chat runs on: the chat posts from it, so the Referer header names it.
        $referer = (string)$request->headers->get(static::REQUEST_HEADER_REFERER, '');
        $path = (string)parse_url($referer, PHP_URL_PATH);
        $query = (string)parse_url($referer, PHP_URL_QUERY);

        return $query === '' ? $path : sprintf('%s?%s', $path, $query);
    }

    protected function createPromptStreamResponse(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequestTransfer,
    ): StreamedResponse {
        set_time_limit(static::STREAM_TIME_LIMIT_IN_SECONDS);

        return new StreamedResponse(
            function () use ($backofficeAssistantPromptRequestTransfer): void {
                $this->getFactory()->createPromptHandler()->process($backofficeAssistantPromptRequestTransfer);
            },
            Response::HTTP_OK,
            [
                static::HEADER_CONTENT_TYPE => static::HEADER_VALUE_CONTENT_TYPE,
                static::HEADER_CACHE_CONTROL => static::HEADER_VALUE_CACHE_CONTROL,
                static::HEADER_X_ACCEL_BUFFERING => static::HEADER_VALUE_X_ACCEL_BUFFERING,
            ],
        );
    }

    protected function createErrorResponse(string $translationKey, int $statusCode): JsonResponse
    {
        return $this->jsonResponse(
            [static::RESPONSE_KEY_ERROR => $this->getFactory()->getTranslatorFacade()->trans($translationKey)],
            $statusCode,
        );
    }
}
