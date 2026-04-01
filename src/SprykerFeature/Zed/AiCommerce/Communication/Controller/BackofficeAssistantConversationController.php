<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Controller;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class BackofficeAssistantConversationController extends AbstractController
{
    protected const string RESPONSE_KEY_HISTORIES = 'histories';

    protected const string RESPONSE_KEY_AVAILABLE_AGENTS = 'available_agents';

    protected const string RESPONSE_KEY_CONVERSATION_REFERENCE = 'conversation_reference';

    protected const string RESPONSE_KEY_NAME = 'name';

    protected const string RESPONSE_KEY_AGENT = 'agent';

    protected const string RESPONSE_KEY_USER_SELECTED_AGENT = 'user_selected_agent';

    protected const string RESPONSE_KEY_DESCRIPTION = 'description';

    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string RESPONSE_KEY_SUCCESS = 'success';

    protected const string REQUEST_KEY_CONVERSATION_REFERENCE = 'conversationReference';

    protected const string CSRF_TOKEN_ID = 'backoffice-assistant';

    protected const string CSRF_TOKEN_PARAM = '_token';

    protected const string ERROR_MISSING_CONVERSATION_REFERENCE = 'backoffice_assistant.error.missing_conversation_reference';

    protected const string ERROR_CONVERSATION_NOT_FOUND = 'backoffice_assistant.error.conversation_not_found';

    protected const string ERROR_BACKOFFICE_ASSISTANT_DISABLED = 'backoffice_assistant.error.disabled';

    protected const string ERROR_INVALID_CSRF_TOKEN = 'backoffice_assistant.error.invalid_csrf_token';

    public function indexAction(): JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED)], 403);
        }

        $userUuid = $this->getFactory()->getUserFacade()->getCurrentUser()->getUuidOrFail();

        $criteria = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())
                    ->addUserUuid($userUuid)
                    ->setLimit($this->getFactory()->getConfig()->getConversationListLimit()),
            );

        $collection = $this->getFacade()->getBackofficeAssistantConversationCollection($criteria);

        $emptyRequest = new BackofficeAssistantPromptRequestTransfer();

        $availableAgents = array_map(
            static fn ($plugin) => [
                static::RESPONSE_KEY_NAME => $plugin->getName(),
                static::RESPONSE_KEY_DESCRIPTION => $plugin->getDescription(),
            ],
            array_filter(
                $this->getFactory()->getBackofficeAssistantAgentPlugins(),
                static fn ($plugin) => $plugin->isApplicable($emptyRequest),
            ),
        );

        return $this->jsonResponse([
            static::RESPONSE_KEY_HISTORIES => array_map(
                static fn ($conversation) => [
                    static::RESPONSE_KEY_CONVERSATION_REFERENCE => $conversation->getConversationReference(),
                    static::RESPONSE_KEY_NAME => $conversation->getName(),
                    static::RESPONSE_KEY_AGENT => $conversation->getAgent() ?? '',
                    static::RESPONSE_KEY_USER_SELECTED_AGENT => $conversation->getUserSelectedAgent() ?? '',
                ],
                $collection->getBackofficeAssistantConversations()->getArrayCopy(),
            ),
            static::RESPONSE_KEY_AVAILABLE_AGENTS => $availableAgents,
        ]);
    }

    public function detailAction(Request $request): JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED)], 403);
        }

        $conversationReference = (string)$request->query->get(static::REQUEST_KEY_CONVERSATION_REFERENCE, '');

        if (!$conversationReference) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_MISSING_CONVERSATION_REFERENCE)], 400);
        }

        $userUuid = $this->getFactory()->getUserFacade()->getCurrentUser()->getUuidOrFail();

        $criteria = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())
                    ->addUserUuid($userUuid)
                    ->addConversationReference($conversationReference)
                    ->setWithMessages(true),
            );

        $collection = $this->getFacade()->getBackofficeAssistantConversationCollection($criteria);

        if ($collection->getBackofficeAssistantConversations()->count() === 0) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_CONVERSATION_NOT_FOUND)], 404);
        }

        /** @var \Generated\Shared\Transfer\BackofficeAssistantConversationTransfer $conversation */
        $conversation = $collection->getBackofficeAssistantConversations()->offsetGet(0);

        return $this->jsonResponse(
            array_merge(
                $conversation->toArray(),
                [
                    static::RESPONSE_KEY_AGENT => $conversation->getAgent() ?? '',
                    static::RESPONSE_KEY_USER_SELECTED_AGENT => $conversation->getUserSelectedAgent() ?? '',
                ],
            ),
        );
    }

    public function deleteAction(Request $request): JsonResponse
    {
        if (!$this->getFactory()->getConfig()->isBackofficeAssistantEnabled()) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_BACKOFFICE_ASSISTANT_DISABLED)], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $token = (string)($data[static::CSRF_TOKEN_PARAM] ?? '');

        if (!$this->isValidCsrfToken($token)) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_INVALID_CSRF_TOKEN)], 403);
        }

        $conversationReference = (string)($data[static::RESPONSE_KEY_CONVERSATION_REFERENCE] ?? '');

        if (!$conversationReference) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_MISSING_CONVERSATION_REFERENCE)], 400);
        }

        $userUuid = $this->getFactory()->getUserFacade()->getCurrentUser()->getUuidOrFail();

        $ownershipCriteria = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())
                    ->addUserUuid($userUuid)
                    ->addConversationReference($conversationReference),
            );

        $collection = $this->getFacade()->getBackofficeAssistantConversationCollection($ownershipCriteria);

        if ($collection->getBackofficeAssistantConversations()->count() === 0) {
            return $this->jsonResponse([static::RESPONSE_KEY_ERROR => $this->getFactory()->getGlossaryFacade()->translate(static::ERROR_CONVERSATION_NOT_FOUND)], 404);
        }

        $deleteCriteria = (new BackofficeAssistantConversationCollectionDeleteCriteriaTransfer())
            ->addConversationReference($conversationReference);

        $this->getFacade()->deleteBackofficeAssistantConversationCollection($deleteCriteria);

        return $this->jsonResponse([static::RESPONSE_KEY_SUCCESS => true]);
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
