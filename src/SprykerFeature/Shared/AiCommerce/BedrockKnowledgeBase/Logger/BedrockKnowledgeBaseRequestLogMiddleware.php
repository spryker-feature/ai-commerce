<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\BedrockKnowledgeBase\Logger;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Spryker\Shared\Log\LoggerTrait;
use Throwable;

class BedrockKnowledgeBaseRequestLogMiddleware
{
    use LoggerTrait;

    public const string NAME = 'spryker-bedrock-knowledge-base-request-log';

    protected const string LOG_MESSAGE_SUCCESS = 'Amazon Bedrock knowledge base request %s %s %s: HTTP %d';

    protected const string LOG_MESSAGE_FAILURE = 'Amazon Bedrock knowledge base request %s %s %s failed: HTTP %d %s %s';

    protected const string METADATA = '@metadata';

    protected const string STATUS_CODE = 'statusCode';

    protected const string DATA = 'data';

    protected const string CONTENT_SIZE = '[%d bytes]';

    /**
     * @var list<string>
     */
    protected const array CONTENT_KEYS = ['byteContent', 'textContent'];

    /**
     * @var array<string, bool>
     */
    protected const array SDK_PARAMETER_KEYS = ['@http' => true, '@context' => true];

    protected const string CONTEXT_PARAMETERS = 'bedrockParameters';

    protected const string CONTEXT_RESULT = 'bedrockResult';

    public function __invoke(callable $handler): callable
    {
        /**
         * @param \Psr\Http\Message\RequestInterface|null $request
         */
        return function (CommandInterface $command, $request = null) use ($handler) {
            $method = $request?->getMethod() ?? '';
            $url = (string)$request?->getUri();

            return $handler($command, $request)->then(
                function (ResultInterface $result) use ($command, $method, $url): ResultInterface {
                    $resultData = $result->toArray();

                    $this->getLogger()->debug(
                        sprintf(static::LOG_MESSAGE_SUCCESS, $command->getName(), $method, $url, (int)($resultData[static::METADATA][static::STATUS_CODE] ?? 0)),
                        [
                            static::CONTEXT_PARAMETERS => $this->getCommandParameters($command),
                            static::CONTEXT_RESULT => array_diff_key($resultData, [static::METADATA => true]),
                        ],
                    );

                    return $result;
                },
                function (Throwable $reason) use ($command, $method, $url): never {
                    $awsException = $reason instanceof AwsException ? $reason : null;

                    $this->getLogger()->warning(
                        sprintf(
                            static::LOG_MESSAGE_FAILURE,
                            $command->getName(),
                            $method,
                            $url,
                            (int)$awsException?->getStatusCode(),
                            (string)$awsException?->getAwsErrorCode(),
                            $awsException?->getAwsErrorMessage() ?? $reason->getMessage(),
                        ),
                        [static::CONTEXT_PARAMETERS => $this->getCommandParameters($command)],
                    );

                    throw $reason;
                },
            );
        };
    }

    /**
     * @return array<mixed>
     */
    protected function getCommandParameters(CommandInterface $command): array
    {
        return $this->replaceContent(array_diff_key($command->toArray(), static::SDK_PARAMETER_KEYS));
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    protected function replaceContent(array $data): array
    {
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (in_array($key, static::CONTENT_KEYS, true) && isset($value[static::DATA]) && is_string($value[static::DATA])) {
                $value[static::DATA] = sprintf(static::CONTENT_SIZE, strlen($value[static::DATA]));
            }

            $data[$key] = $this->replaceContent($value);
        }

        return $data;
    }
}
