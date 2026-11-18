<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\BedrockKnowledgeBase;

use Aws\BedrockAgent\BedrockAgentClient;
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spryker\Shared\Log\Config\LoggerConfigInterface;
use SprykerFeature\Shared\AiCommerce\BedrockKnowledgeBase\Logger\BedrockKnowledgeBaseRequestLogMiddleware;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group BedrockKnowledgeBase
 * @group BedrockKnowledgeBaseRequestLogMiddlewareTest
 * Add your own group annotations below this line
 */
class BedrockKnowledgeBaseRequestLogMiddlewareTest extends Unit
{
    protected const string AWS_REGION = 'eu-central-1';

    protected const string KNOWLEDGE_BASE_ID = 'KB12345678';

    protected const string AWS_ERROR_CODE_THROTTLING = 'ThrottlingException';

    protected const int HTTP_STATUS_CODE_TOO_MANY_REQUESTS = 429;

    public function testGivenSuccessfulRequestWhenSendingThenRequestIsLoggedAtDebugLevel(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('debug');
        $loggerMock->expects($this->never())->method('info');
        $loggerMock->expects($this->never())->method('warning');
        $bedrockAgentClient = $this->createBedrockAgentClient(new Result(['knowledgeBase' => []]), $loggerMock);

        // Act
        $bedrockAgentClient->getKnowledgeBase(['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID]);
    }

    public function testGivenFailedRequestWhenSendingThenRequestIsLoggedAtWarningLevel(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');
        $loggerMock->expects($this->never())->method('debug');
        $bedrockAgentClient = $this->createBedrockAgentClient(
            new AwsException(static::AWS_ERROR_CODE_THROTTLING, new Command(static::AWS_ERROR_CODE_THROTTLING), [
                'code' => static::AWS_ERROR_CODE_THROTTLING,
                'message' => static::AWS_ERROR_CODE_THROTTLING,
                'response' => new Response(static::HTTP_STATUS_CODE_TOO_MANY_REQUESTS),
            ]),
            $loggerMock,
        );

        // Assert
        $this->expectException(AwsException::class);

        // Act
        $bedrockAgentClient->getKnowledgeBase(['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID]);
    }

    protected function createBedrockAgentClient(Result|AwsException $response, LoggerInterface&MockObject $loggerMock): BedrockAgentClient
    {
        $bedrockAgentClient = new BedrockAgentClient([
            'region' => static::AWS_REGION,
            'version' => 'latest',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'retries' => 0,
            'handler' => new MockHandler([$response]),
        ]);

        $bedrockAgentClient->getHandlerList()->appendSign(
            $this->createRequestLogMiddleware($loggerMock),
            BedrockKnowledgeBaseRequestLogMiddleware::NAME,
        );

        return $bedrockAgentClient;
    }

    protected function createRequestLogMiddleware(LoggerInterface $logger): BedrockKnowledgeBaseRequestLogMiddleware
    {
        return new class ($logger) extends BedrockKnowledgeBaseRequestLogMiddleware {
            public function __construct(protected LoggerInterface $logger)
            {
            }

            protected function getLogger(?LoggerConfigInterface $loggerConfig = null): LoggerInterface
            {
                return $this->logger;
            }
        };
    }
}
