<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

use Generated\Shared\Transfer\AiToolCallTransfer;

class ToolCallEventStreamer implements ToolCallEventStreamerInterface
{
    /**
     * @param array<string> $aiConfigurationNames
     * @param array<string>|null $visibleToolNames
     * @param array<\SprykerFeature\Shared\AiCommerce\Stream\ToolResultExpanderInterface> $toolResultExpanders
     */
    public function __construct(
        protected StreamEventEmitterInterface $streamEventEmitter,
        protected StreamBlockTrackerInterface $streamBlockTracker,
        protected ToolCallIdResolverInterface $toolCallIdResolver,
        protected array $aiConfigurationNames,
        protected ?array $visibleToolNames = null,
        protected array $toolResultExpanders = []
    ) {
    }

    public function streamToolInput(AiToolCallTransfer $aiToolCallTransfer): AiToolCallTransfer
    {
        if (!$this->isStreamedAiConfiguration($aiToolCallTransfer)) {
            return $aiToolCallTransfer;
        }

        $toolName = (string)$aiToolCallTransfer->getToolName();
        $toolCallId = $this->toolCallIdResolver->resolveToolCallId($toolName, $aiToolCallTransfer->getToolCallId());

        $aiToolCallTransfer->setToolCallId($toolCallId);

        $this->streamBlockTracker->closeOpenBlock();

        $this->streamEventEmitter->emitEvent(StreamEventType::ToolInputStart, [
            StreamEventKey::TOOL_CALL_ID => $toolCallId,
            StreamEventKey::TOOL_NAME => $toolName,
        ]);

        $this->streamEventEmitter->emitEvent(StreamEventType::ToolInputAvailable, [
            StreamEventKey::TOOL_CALL_ID => $toolCallId,
            StreamEventKey::TOOL_NAME => $toolName,
            StreamEventKey::INPUT => $aiToolCallTransfer->getToolArguments(),
        ]);

        return $aiToolCallTransfer;
    }

    public function streamToolOutput(AiToolCallTransfer $aiToolCallTransfer): void
    {
        if (!$this->isStreamedAiConfiguration($aiToolCallTransfer)) {
            return;
        }

        $toolName = (string)$aiToolCallTransfer->getToolName();

        if (!$this->isVisibleTool($toolName)) {
            return;
        }

        $toolCallId = $this->toolCallIdResolver->resolveToolCallId($toolName, $aiToolCallTransfer->getToolCallId());

        if ($aiToolCallTransfer->getIsExecutionAllowed() === false) {
            $this->streamEventEmitter->emitEvent(StreamEventType::ToolOutputDenied, [
                StreamEventKey::TOOL_CALL_ID => $toolCallId,
            ]);

            return;
        }

        $this->streamEventEmitter->emitEvent(StreamEventType::ToolOutputAvailable, [
            StreamEventKey::TOOL_CALL_ID => $toolCallId,
            StreamEventKey::OUTPUT => [
                StreamEventKey::RESULT => $this->expandToolResult($toolName, (string)$aiToolCallTransfer->getToolResult()),
            ],
        ]);
    }

    protected function isStreamedAiConfiguration(AiToolCallTransfer $aiToolCallTransfer): bool
    {
        return in_array(
            $aiToolCallTransfer->getPromptRequest()?->getAiConfigurationName(),
            $this->aiConfigurationNames,
            true,
        );
    }

    protected function isVisibleTool(string $toolName): bool
    {
        return $this->visibleToolNames === null || in_array($toolName, $this->visibleToolNames, true);
    }

    protected function expandToolResult(string $toolName, string $toolResult): string
    {
        foreach ($this->toolResultExpanders as $toolResultExpander) {
            $toolResult = $toolResultExpander->expandToolResult($toolName, $toolResult);
        }

        return $toolResult;
    }
}
