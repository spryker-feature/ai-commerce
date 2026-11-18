<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\KnowledgeSearch;

use ArrayObject;
use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;
use Aws\Command;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeSearchConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeSearchCriteriaTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use GuzzleHttp\Psr7\Response;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Client\AiCommerce\AiCommerceFactory;
use SprykerFeature\Client\AiCommerce\Plugin\KnowledgeSearch\BedrockKnowledgeBaseKnowledgeSearchPlugin;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group KnowledgeSearch
 * @group BedrockKnowledgeBaseKnowledgeSearchPluginTest
 * Add your own group annotations below this line
 */
class BedrockKnowledgeBaseKnowledgeSearchPluginTest extends Unit
{
    protected const string KNOWLEDGE_BASE_ID = 'KB12345678';

    protected const string DATA_SOURCE_ID = 'DS12345678';

    protected const string QUERY_TEXT = 'How do I clean the filter?';

    protected const string DOCUMENT_KEY = 'product_attachment:11';

    protected const string SECOND_DOCUMENT_KEY = 'product_attachment:12';

    protected const string LOCALE_NAME = 'en_US';

    protected const string RERANKING_MODEL_ARN = 'arn:aws:bedrock:eu-central-1::foundation-model/cohere.rerank-v3-5:0';

    protected const int LIMIT = 5;

    protected AiCommerceClientTester $tester;

    /**
     * @var list<\Aws\CommandInterface>
     */
    protected array $commands = [];

    public function testGivenTextRetrievalResultsWhenSearchingDocumentsThenPassagesAreMappedAndSortedByScoreDescending(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([$this->createRetrieveResult()], static::KNOWLEDGE_BASE_ID);

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteriaWithLocaleAndDocumentName());

        // Assert
        $this->assertTrue($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertSame(['Rinse the filter with water.', 'Open the cover.'], $this->getContents($knowledgePassageCollectionTransfer->getKnowledgePassages()));
        $this->assertSame(static::DOCUMENT_KEY, $knowledgePassageCollectionTransfer->getKnowledgePassages()->offsetGet(0)->getDocumentKey());
        $this->assertSame('product_attachment', $knowledgePassageCollectionTransfer->getKnowledgePassages()->offsetGet(0)->getSourceType());
        $this->assertSame(['sourceIdentifier' => '11'], $knowledgePassageCollectionTransfer->getKnowledgePassages()->offsetGet(0)->getMetadata());
    }

    public function testGivenLocaleAndDocumentNameWhenSearchingDocumentsThenOneRetrieveRequestWithTheQueryLimitRerankingAndFilterIsSent(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([$this->createRetrieveResult()], static::KNOWLEDGE_BASE_ID);

        // Act
        $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteriaWithLocaleAndDocumentName());

        // Assert
        $this->assertCount(1, $this->commands);
        $retrieveArguments = $this->commands[0]->toArray();
        $this->assertSame(static::KNOWLEDGE_BASE_ID, $retrieveArguments['knowledgeBaseId']);
        $this->assertSame(static::QUERY_TEXT, $retrieveArguments['retrievalQuery']['text']);
        $this->assertSame(static::LIMIT, $retrieveArguments['retrievalConfiguration']['managedSearchConfiguration']['numberOfResults']);
        $this->assertSame('MANAGED', $retrieveArguments['retrievalConfiguration']['managedSearchConfiguration']['rerankingModelType']);
        $this->assertSame([
            ['in' => ['key' => 'document_key', 'value' => [static::DOCUMENT_KEY, static::SECOND_DOCUMENT_KEY]]],
            ['in' => ['key' => 'locale', 'value' => [static::LOCALE_NAME, '*']]],
            ['equals' => ['key' => 'document_name', 'value' => 'Manual']],
        ], $retrieveArguments['retrievalConfiguration']['managedSearchConfiguration']['filter']['andAll']);
    }

    public function testGivenImageAndTextRetrievalResultsWhenSearchingDocumentsThenOnlyTheTextPassageIsReturned(): void
    {
        // Arrange
        $imageRetrievalResult = array_replace($this->createRetrievalResult('', 0.95), ['content' => ['type' => 'IMAGE', 'byteContent' => 'data:image/png;base64,iVBORw0KGgo=']]);
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin(
            [['retrievalResults' => [$imageRetrievalResult, $this->createRetrievalResult('Rinse the filter with water.', 0.87)]]],
            static::KNOWLEDGE_BASE_ID,
        );

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments(
            $this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()),
        );

        // Assert
        $this->assertTrue($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertSame(['Rinse the filter with water.'], $this->getContents($knowledgePassageCollectionTransfer->getKnowledgePassages()));
    }

    public function testGivenOnlyDocumentKeysWhenSearchingDocumentsThenTheFilterIsASingleConditionWithoutAndAll(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([['retrievalResults' => []]], static::KNOWLEDGE_BASE_ID);

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()));

        // Assert
        $this->assertTrue($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertCount(0, $knowledgePassageCollectionTransfer->getKnowledgePassages());
        $this->assertSame(
            ['in' => ['key' => 'document_key', 'value' => [static::DOCUMENT_KEY, static::SECOND_DOCUMENT_KEY]]],
            $this->commands[0]->toArray()['retrievalConfiguration']['managedSearchConfiguration']['filter'],
        );
    }

    public function testGivenNoKnowledgeBaseIdWhenSearchingDocumentsThenAnErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([], '');

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()));

        // Assert
        $this->assertFalse($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertSame([], $this->commands);
    }

    public function testGivenCustomRerankingModelWhenSearchingDocumentsThenTheRerankingModelArnIsSent(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseRerankingModelType', 'CUSTOM');
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseRerankingModelArn', static::RERANKING_MODEL_ARN);
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([['retrievalResults' => []]], static::KNOWLEDGE_BASE_ID);

        // Act
        $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()));

        // Assert
        $managedSearchConfiguration = $this->commands[0]->toArray()['retrievalConfiguration']['managedSearchConfiguration'];
        $this->assertSame(static::KNOWLEDGE_BASE_ID, $this->commands[0]->toArray()['knowledgeBaseId']);
        $this->assertSame('CUSTOM', $managedSearchConfiguration['rerankingModelType']);
        $this->assertSame(static::RERANKING_MODEL_ARN, $managedSearchConfiguration['rerankingConfiguration']['bedrockRerankingConfiguration']['modelConfiguration']['modelArn']);
    }

    public function testGivenCustomRerankingModelWithoutArnWhenSearchingDocumentsThenAnErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseRerankingModelType', 'CUSTOM');
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseRerankingModelArn', '');
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([], static::KNOWLEDGE_BASE_ID);

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()));

        // Assert
        $this->assertFalse($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertSame([], $this->commands);
    }

    public function testGivenFailingRetrieveRequestWhenSearchingDocumentsThenAnErrorIsReturned(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([$this->createAwsException('ValidationException', 400)], static::KNOWLEDGE_BASE_ID);

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()));

        // Assert
        $this->assertFalse($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertCount(1, $knowledgePassageCollectionTransfer->getErrors());
    }

    public function testGivenThrottledRetrieveRequestWhenSearchingDocumentsThenTheRequestIsSentAgainWithTheConfiguredRetries(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin(
            [...array_fill(0, 3, $this->createAwsException('ThrottlingException', 429)), $this->createRetrieveResult()],
            static::KNOWLEDGE_BASE_ID,
            (new SharedAiCommerceConfig())->getBedrockKnowledgeBaseAwsClientConfiguration(),
        );

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments($this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()));

        // Assert
        $this->assertTrue($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertCount(4, $this->commands);
    }

    public function testGivenEmptyQueryTextWhenSearchingDocumentsThenAnErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([], static::KNOWLEDGE_BASE_ID);

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments(
            $this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()->setQueryText('  ')),
        );

        // Assert
        $this->assertFalse($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertSame([], $this->commands);
    }

    public function testGivenNoDocumentKeyOrIdWhenSearchingDocumentsThenAnErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $bedrockKnowledgeBaseKnowledgeSearchPlugin = $this->createPlugin([['retrievalResults' => []]], static::KNOWLEDGE_BASE_ID);

        // Act
        $knowledgePassageCollectionTransfer = $bedrockKnowledgeBaseKnowledgeSearchPlugin->searchDocuments(
            $this->createKnowledgeSearchCriteria($this->createKnowledgeSearchConditions()->setDocumentKeys([''])->setIds([])),
        );

        // Assert
        $this->assertFalse($knowledgePassageCollectionTransfer->getIsSuccessful());
        $this->assertSame([], $this->commands);
    }

    /**
     * @param list<array<string, mixed>|\Aws\Exception\AwsException> $responses
     * @param array<string, mixed> $awsClientConfiguration
     */
    protected function createPlugin(
        array $responses,
        string $configuredKnowledgeBaseId,
        array $awsClientConfiguration = []
    ): BedrockKnowledgeBaseKnowledgeSearchPlugin {
        $this->commands = [];
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseId', $configuredKnowledgeBaseId);

        $awsClientConfiguration = $this->createAwsClientConfiguration($responses, $awsClientConfiguration);
        $this->tester->setDependency(AiCommerceDependencyProvider::BEDROCK_AGENT_RUNTIME_CLIENT, new BedrockAgentRuntimeClient($awsClientConfiguration), AiCommerceFactory::class);

        $bedrockKnowledgeBaseKnowledgeSearchPlugin = new BedrockKnowledgeBaseKnowledgeSearchPlugin();
        $bedrockKnowledgeBaseKnowledgeSearchPlugin->setFactory($this->tester->getFactory());

        return $bedrockKnowledgeBaseKnowledgeSearchPlugin;
    }

    /**
     * @param list<array<string, mixed>|\Aws\Exception\AwsException> $responses
     * @param array<string, mixed> $awsClientConfiguration
     *
     * @return array<string, mixed>
     */
    protected function createAwsClientConfiguration(array $responses, array $awsClientConfiguration): array
    {
        $mockHandler = new MockHandler();

        foreach ($responses as $response) {
            $mockHandler->append(function (CommandInterface $command) use ($response): Result|AwsException {
                $this->commands[] = $command;

                return is_array($response) ? new Result($response) : $response;
            });
        }

        return [
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'handler' => $mockHandler,
        ] + $awsClientConfiguration + [
            'region' => 'eu-central-1',
            'version' => 'latest',
            'retries' => 0,
        ];
    }

    protected function createKnowledgeSearchConditions(): KnowledgeSearchConditionsTransfer
    {
        return (new KnowledgeSearchConditionsTransfer())
            ->setQueryText(static::QUERY_TEXT)
            ->setDocumentKeys([static::DOCUMENT_KEY, static::SECOND_DOCUMENT_KEY]);
    }

    protected function createKnowledgeSearchCriteria(KnowledgeSearchConditionsTransfer $knowledgeSearchConditionsTransfer): KnowledgeSearchCriteriaTransfer
    {
        return (new KnowledgeSearchCriteriaTransfer())
            ->setKnowledgeSearchConditions($knowledgeSearchConditionsTransfer)
            ->setPagination((new PaginationTransfer())->setLimit(static::LIMIT));
    }

    protected function createKnowledgeSearchCriteriaWithLocaleAndDocumentName(): KnowledgeSearchCriteriaTransfer
    {
        return $this->createKnowledgeSearchCriteria(
            $this->createKnowledgeSearchConditions()->addLocale(static::LOCALE_NAME)->setDocumentName('Manual'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRetrieveResult(): array
    {
        return [
        'retrievalResults' => [
            $this->createRetrievalResult('Open the cover.', 0.41),
            $this->createRetrievalResult('Rinse the filter with water.', 0.87),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRetrievalResult(string $text, float $score): array
    {
        return [
            'content' => ['type' => 'TEXT', 'text' => $text],
            'location' => ['type' => 'CUSTOM', 'customDocumentLocation' => ['id' => static::DOCUMENT_KEY]],
            'score' => $score,
            'metadata' => [
                'document_key' => static::DOCUMENT_KEY,
                'source_type' => 'product_attachment',
                'locale' => '*',
                'document_name' => 'Manual',
                'sourceIdentifier' => '11',
                'x-amz-bedrock-kb-chunk-id' => uniqid('chunk-', true),
                'x-amz-bedrock-kb-data-source-id' => static::DATA_SOURCE_ID,
            ],
        ];
    }

    protected function createAwsException(string $errorCode, int $statusCode): AwsException
    {
        return new AwsException($errorCode, new Command($errorCode), ['code' => $errorCode, 'message' => $errorCode, 'response' => new Response($statusCode)]);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\KnowledgePassageTransfer> $knowledgePassageTransfers
     *
     * @return list<string>
     */
    protected function getContents(ArrayObject $knowledgePassageTransfers): array
    {
        $contents = [];

        foreach ($knowledgePassageTransfers as $knowledgePassageTransfer) {
            $contents[] = (string)$knowledgePassageTransfer->getContent();
        }

        return $contents;
    }
}
