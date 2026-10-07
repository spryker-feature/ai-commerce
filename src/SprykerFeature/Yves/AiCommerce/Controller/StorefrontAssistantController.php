<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceClientInterface getClient()
 */
class StorefrontAssistantController extends AbstractController
{
    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string GLOSSARY_KEY_ERROR_NOT_AUTHENTICATED = 'ai_commerce.storefront_assistant.error.not_authenticated';

    protected const string RESPONSE_KEY_DELETED_COUNT = 'deletedCount';

    protected const string GLOSSARY_KEY_ERROR_CONVERSATION_NOT_FOUND = 'ai_commerce.storefront_assistant.error.conversation_not_found';

    protected const string GLOSSARY_KEY_ERROR_CONVERSATION_DELETE_FAILED = 'ai_commerce.storefront_assistant.error.conversation_delete_failed';

    protected const int STREAM_TIME_LIMIT_IN_SECONDS = 120;

    protected const string GLOSSARY_KEY_ERROR_INVALID_CSRF_TOKEN = 'ai_commerce.storefront_assistant.error.invalid_csrf_token';

    protected const string GLOSSARY_KEY_ERROR_INVALID_PAYLOAD = 'ai_commerce.storefront_assistant.error.invalid_payload';

    public const string CSRF_TOKEN_ID = 'storefront_assistant';

    protected const string REQUEST_HEADER_CSRF_TOKEN = 'X-CSRF-Token';

    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    protected const string HEADER_CACHE_CONTROL = 'Cache-Control';

    protected const string HEADER_X_ACCEL_BUFFERING = 'X-Accel-Buffering';

    protected const string HEADER_VALUE_CONTENT_TYPE = 'text/event-stream';

    protected const string HEADER_VALUE_CACHE_CONTROL = 'no-cache';

    protected const string HEADER_VALUE_X_ACCEL_BUFFERING = 'no';

    public function promptAction(Request $request): JsonResponse|StreamedResponse
    {
        $storefrontAssistantAccessChecker = $this->getFactory()->createStorefrontAssistantAccessChecker();

        if (!$storefrontAssistantAccessChecker->isStorefrontAssistantEnabled()) {
            return $this->jsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $customerReference = $storefrontAssistantAccessChecker->findCustomerReference();

        if ($customerReference === null) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_NOT_AUTHENTICATED, Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenHeaderValid($request)) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_INVALID_CSRF_TOKEN, Response::HTTP_FORBIDDEN);
        }

        $payload = $this->getFactory()->createStorefrontAssistantRequestPayloadReader()->findRequestPayload($request);

        if ($payload === null) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_INVALID_PAYLOAD, Response::HTTP_BAD_REQUEST);
        }

        $storefrontAssistantChatRequestTransfer = $this->getFactory()
            ->createStorefrontAssistantRequestPayloadMapper()
            ->mapPayloadToStorefrontAssistantChatRequestTransfer($payload, $customerReference, (string)$this->getLocale());

        $validationResponseTransfer = $this->getClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        if ($validationResponseTransfer->getIsSuccessful() !== true) {
            $errorTransfer = $this->getFactory()
                ->createStorefrontAssistantValidationErrorResolver()
                ->resolveValidationError($validationResponseTransfer);

            return $this->createErrorResponse(
                (string)$errorTransfer->getMessage(),
                Response::HTTP_BAD_REQUEST,
                $errorTransfer->getParameters(),
            );
        }

        $sessionAttributes = $request->getSession()->all();
        $this->getFactory()
            ->createStorefrontAssistantStreamSessionBinder()
            ->bindSessionAttributes($sessionAttributes);

        // Keeps the turn running after a browser disconnect, so the answer is still persisted to the conversation history.
        ignore_user_abort(true);
        set_time_limit(static::STREAM_TIME_LIMIT_IN_SECONDS);

        return new StreamedResponse(
            function () use ($storefrontAssistantChatRequestTransfer): void {
                $this->getClient()
                    ->executeStorefrontAssistantChat($storefrontAssistantChatRequestTransfer);
            },
            Response::HTTP_OK,
            [
                static::HEADER_CONTENT_TYPE => static::HEADER_VALUE_CONTENT_TYPE,
                static::HEADER_CACHE_CONTROL => static::HEADER_VALUE_CACHE_CONTROL,
                static::HEADER_X_ACCEL_BUFFERING => static::HEADER_VALUE_X_ACCEL_BUFFERING,
            ],
        );
    }

    protected function isCsrfTokenHeaderValid(Request $request): bool
    {
        $csrfToken = new CsrfToken(static::CSRF_TOKEN_ID, (string)$request->headers->get(static::REQUEST_HEADER_CSRF_TOKEN));

        return $this->getFactory()->getCsrfTokenManager()->isTokenValid($csrfToken);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createErrorResponse(string $glossaryKey, int $statusCode, array $parameters = []): JsonResponse
    {
        return $this->jsonResponse(
            [static::RESPONSE_KEY_ERROR => $this->getFactory()->getTranslatorService()->trans($glossaryKey, $parameters)],
            $statusCode,
        );
    }

    public function conversationsAction(Request $request): JsonResponse
    {
        $storefrontAssistantAccessChecker = $this->getFactory()->createStorefrontAssistantAccessChecker();

        if (!$storefrontAssistantAccessChecker->isStorefrontAssistantEnabled()) {
            return $this->jsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $customerReference = $storefrontAssistantAccessChecker->findCustomerReference();

        if ($customerReference === null) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_NOT_AUTHENTICATED, Response::HTTP_FORBIDDEN);
        }

        $storefrontAssistantConversationCollectionCriteriaTransfer = $this->getFactory()
            ->createStorefrontAssistantRequestPayloadMapper()
            ->mapPayloadToConversationCollectionCriteriaTransfer($request->query->all(), $customerReference);

        $storefrontAssistantConversationCollectionTransfer = $this->getClient()
            ->getStorefrontAssistantConversationCollection($storefrontAssistantConversationCollectionCriteriaTransfer);

        return $this->jsonResponse(
            $this->getFactory()
                ->createStorefrontAssistantConversationMapper()
                ->mapStorefrontAssistantConversationCollectionToArray($storefrontAssistantConversationCollectionTransfer),
            Response::HTTP_OK,
        );
    }

    public function deleteConversationAction(Request $request): JsonResponse
    {
        $storefrontAssistantAccessChecker = $this->getFactory()->createStorefrontAssistantAccessChecker();

        if (!$storefrontAssistantAccessChecker->isStorefrontAssistantEnabled()) {
            return $this->jsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $customerReference = $storefrontAssistantAccessChecker->findCustomerReference();

        if ($customerReference === null) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_NOT_AUTHENTICATED, Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenHeaderValid($request)) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_INVALID_CSRF_TOKEN, Response::HTTP_FORBIDDEN);
        }

        $payload = $this->getFactory()->createStorefrontAssistantRequestPayloadReader()->findRequestPayload($request);

        if ($payload === null) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_INVALID_PAYLOAD, Response::HTTP_BAD_REQUEST);
        }

        $storefrontAssistantConversationCollectionResponseTransfer = $this->getClient()
            ->deleteStorefrontAssistantConversationCollection(
                $this->getFactory()
                    ->createStorefrontAssistantRequestPayloadMapper()
                    ->mapPayloadToConversationCollectionDeleteCriteriaTransfer($payload, $customerReference),
            );

        if ($storefrontAssistantConversationCollectionResponseTransfer->getIsSuccessful() !== true) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_CONVERSATION_DELETE_FAILED, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->jsonResponse(
            [static::RESPONSE_KEY_DELETED_COUNT => (int)$storefrontAssistantConversationCollectionResponseTransfer->getDeletedCount()],
            Response::HTTP_OK,
        );
    }

    public function conversationMessagesAction(Request $request): JsonResponse
    {
        $storefrontAssistantAccessChecker = $this->getFactory()->createStorefrontAssistantAccessChecker();

        if (!$storefrontAssistantAccessChecker->isStorefrontAssistantEnabled()) {
            return $this->jsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $customerReference = $storefrontAssistantAccessChecker->findCustomerReference();

        if ($customerReference === null) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_NOT_AUTHENTICATED, Response::HTTP_FORBIDDEN);
        }

        $storefrontAssistantConversationMessageCollectionTransfer = $this->getClient()
            ->getStorefrontAssistantConversationMessageCollection(
                $this->getFactory()
                    ->createStorefrontAssistantRequestPayloadMapper()
                    ->mapPayloadToConversationMessageCollectionCriteriaTransfer($request->query->all(), $customerReference),
            );

        if ($storefrontAssistantConversationMessageCollectionTransfer->getIsSuccessful() !== true) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_ERROR_CONVERSATION_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        return $this->jsonResponse(
            $this->getFactory()
                ->createStorefrontAssistantConversationMapper()
                ->mapStorefrontAssistantConversationMessageCollectionToArray($storefrontAssistantConversationMessageCollectionTransfer),
            Response::HTTP_OK,
        );
    }
}
