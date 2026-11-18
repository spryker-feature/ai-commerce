<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Helper;

use Aws\BedrockAgent\BedrockAgentClient;
use Aws\Command;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Codeception\Module;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\ContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\LocalizedContentTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCollectionTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemTransfer;
use GuzzleHttp\Psr7\Response;
use Orm\Zed\AiCommerce\Persistence\SpyKnowledgeDocument;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepository;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Shared\User\Helper\UserDataHelper;

class AiCommerceHelper extends Module
{
    use DataCleanupHelperTrait;
    use DependencyHelperTrait;
    use LocatorHelperTrait;

    protected const string BEDROCK_AWS_REGION = 'eu-central-1';

    /**
     * @var list<\Aws\CommandInterface>
     */
    protected array $bedrockAgentCommands = [];

    /**
     * @param list<array<string, mixed>|\Aws\Exception\AwsException|callable> $responses
     */
    public function mockBedrockAgentClient(array $responses): void
    {
        $this->bedrockAgentCommands = [];
        $mockHandler = new MockHandler();

        foreach ($responses as $response) {
            $mockHandler->append(function (CommandInterface $command) use ($response): Result|AwsException {
                $this->bedrockAgentCommands[] = $command;
                $response = is_callable($response) ? $response($command) : $response;

                return is_array($response) ? new Result($response) : $response;
            });
        }

        $this->getDependencyHelper()->setDependency(AiCommerceDependencyProvider::BEDROCK_AGENT_CLIENT, new BedrockAgentClient([
            'region' => static::BEDROCK_AWS_REGION,
            'version' => 'latest',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'retries' => 0,
            'handler' => $mockHandler,
        ]));
    }

    /**
     * @return list<\Aws\CommandInterface>
     */
    public function getBedrockAgentCommands(): array
    {
        return $this->bedrockAgentCommands;
    }

    /**
     * @return list<string>
     */
    public function getBedrockAgentCommandNames(): array
    {
        return array_map(static fn (CommandInterface $command): string => $command->getName(), $this->bedrockAgentCommands);
    }

    public function createBedrockAwsException(string $errorCode, int $statusCode): AwsException
    {
        return new AwsException($errorCode, new Command($errorCode), [
            'code' => $errorCode,
            'message' => $errorCode,
            'response' => new Response($statusCode),
        ]);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveConversation(array $seed = []): BackofficeAssistantConversationTransfer
    {
        $conversationTransfer = (new BackofficeAssistantConversationTransfer())
            ->fromArray(array_merge($this->getDefaultConversationSeed(), $seed), true);

        $collectionRequestTransfer = (new BackofficeAssistantConversationCollectionRequestTransfer())
            ->addBackofficeAssistantConversation($conversationTransfer);

        $responseTransfer = $this->getLocator()
            ->aiCommerce()
            ->facade()
            ->createBackofficeAssistantConversationCollection($collectionRequestTransfer);

        $createdConversation = $responseTransfer->getBackofficeAssistantConversations()->getIterator()->current();

        $this->getDataCleanupHelper()->addCleanup(function () use ($createdConversation): void {
            $this->deleteConversation($createdConversation->getConversationReferenceOrFail());
        });

        return $createdConversation;
    }

    public function haveSmartCmsContentItem(string $name, string $contentTypeKey): ContentTransfer
    {
        $contentTransfer = (new ContentTransfer())
            ->setKey(uniqid('ai-content-item-', true))
            ->setName($name)
            ->setContentTermKey($contentTypeKey)
            ->setContentTypeKey($contentTypeKey)
            ->addLocalizedContent((new LocalizedContentTransfer())->setParameters('{}'));

        return $this->getLocator()->content()->facade()->create($contentTransfer);
    }

    public function findSmartCmsContentItemByKey(
        SmartCmsContentItemCollectionTransfer $smartCmsContentItemCollectionTransfer,
        ?string $key,
    ): ?SmartCmsContentItemTransfer {
        foreach ($smartCmsContentItemCollectionTransfer->getContentItems() as $smartCmsContentItemTransfer) {
            if ($smartCmsContentItemTransfer->getKey() === $key) {
                return $smartCmsContentItemTransfer;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveKnowledgeDocument(array $seed = []): KnowledgeDocumentTransfer
    {
        $knowledgeDocumentTransfer = (new KnowledgeDocumentTransfer())->fromArray(array_merge([
            KnowledgeDocumentTransfer::SOURCE_TYPE => 'test_source',
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => uniqid('document-', true),
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_PENDING,
            KnowledgeDocumentTransfer::ATTEMPT_COUNT => 0,
        ], $seed));

        $knowledgeDocumentEntity = (new SpyKnowledgeDocument())
            ->setSourceType($knowledgeDocumentTransfer->getSourceTypeOrFail())
            ->setSourceIdentifier($knowledgeDocumentTransfer->getSourceIdentifierOrFail())
            ->setStatus($knowledgeDocumentTransfer->getStatusOrFail())
            ->setContentHash($knowledgeDocumentTransfer->getContentHash())
            ->setAttemptCount($knowledgeDocumentTransfer->getAttemptCountOrFail());

        $knowledgeDocumentEntity->save();

        return $knowledgeDocumentTransfer->setIdKnowledgeDocument($knowledgeDocumentEntity->getIdKnowledgeDocument());
    }

    public function findKnowledgeDocument(string $sourceType, string $sourceIdentifier): ?KnowledgeDocumentTransfer
    {
        $knowledgeDocumentCriteriaTransfer = (new KnowledgeDocumentCriteriaTransfer())
            ->setKnowledgeDocumentConditions(
                (new KnowledgeDocumentConditionsTransfer())
                    ->addSourceType($sourceType)
                    ->addSourceIdentifier($sourceIdentifier),
            );

        $knowledgeDocumentTransfers = (new AiCommerceRepository())
            ->getKnowledgeDocumentCollection($knowledgeDocumentCriteriaTransfer)
            ->getKnowledgeDocuments();

        return $knowledgeDocumentTransfers->count() > 0 ? $knowledgeDocumentTransfers->offsetGet(0) : null;
    }

    protected function deleteConversation(string $conversationReference): void
    {
        $deleteCriteriaTransfer = (new BackofficeAssistantConversationCollectionDeleteCriteriaTransfer())
            ->addConversationReference($conversationReference);

        $this->getLocator()
            ->aiCommerce()
            ->facade()
            ->deleteBackofficeAssistantConversationCollection($deleteCriteriaTransfer);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultConversationSeed(): array
    {
        /** @var \SprykerTest\Shared\User\Helper\UserDataHelper $userDataHelper */
        $userDataHelper = $this->getModule('\\' . UserDataHelper::class);

        return [
            BackofficeAssistantConversationTransfer::ID_USER => $userDataHelper->haveUser()->getIdUserOrFail(),
            BackofficeAssistantConversationTransfer::NAME => uniqid('Test Conversation ', true),
        ];
    }
}
