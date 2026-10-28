<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;

interface BackofficeAssistantChatMapperInterface
{
    /**
     * @param array<string, mixed> $payload
     *
     * @throws \JsonException
     */
    public function mapPayloadToBackofficeAssistantPromptRequestTransfer(
        array $payload,
        string $currentPage,
        int $idUser,
    ): BackofficeAssistantPromptRequestTransfer;

    /**
     * @param array<string, mixed> $payload
     */
    public function mapPayloadToBackofficeAssistantConversationCollectionDeleteCriteriaTransfer(
        array $payload,
        int $idUser,
    ): BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;

    /**
     * @param list<array<string, string>> $availableAgents
     *
     * @return array<string, mixed>
     */
    public function mapBackofficeAssistantConversationCollectionToArray(
        BackofficeAssistantConversationCollectionTransfer $backofficeAssistantConversationCollectionTransfer,
        array $availableAgents,
    ): array;

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function mapBackofficeAssistantConversationToMessagesArray(
        BackofficeAssistantConversationTransfer $backofficeAssistantConversationTransfer,
    ): array;
}
