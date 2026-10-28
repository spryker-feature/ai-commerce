<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Conversation;

/**
 * A regenerated prompt is stored like any other turn, so the stored history keeps the exchange it replaces. The
 * marker on the stored prompt lets the conversation readers drop that exchange, as the chat already did on screen.
 */
class RegeneratedPromptMarker
{
    public const string PAYLOAD_KEY_REGENERATE = 'regenerate';

    protected const string MARKER = "[Regenerate: true]\n\n";

    public function addMarker(string $prompt): string
    {
        return static::MARKER . $prompt;
    }

    public function hasMarker(string $prompt): bool
    {
        return str_starts_with($prompt, static::MARKER);
    }

    public function removeMarker(string $prompt): string
    {
        return $this->hasMarker($prompt) ? substr($prompt, strlen(static::MARKER)) : $prompt;
    }

    /**
     * @template TMessage
     *
     * @param list<TMessage> $messages
     * @param callable(TMessage): ?string $readUserPrompt The prompt of a user message, null for any other message.
     *
     * @return list<TMessage>
     */
    public function removeReplacedExchanges(array $messages, callable $readUserPrompt): array
    {
        $keptMessages = [];

        foreach ($messages as $message) {
            $userPrompt = $readUserPrompt($message);

            if ($userPrompt !== null && $this->hasMarker($userPrompt)) {
                $keptMessages = array_slice(
                    $keptMessages,
                    0,
                    $this->findReplacedExchangeStart($keptMessages, $readUserPrompt, $this->removeMarker($userPrompt)),
                );
            }

            $keptMessages[] = $message;
        }

        return $keptMessages;
    }

    /**
     * The chat regenerates any earlier question too and drops everything after it, so the replaced exchange starts at
     * the latest earlier occurrence of the same prompt, or at the latest user message when the prompt is not found.
     *
     * @template TMessage
     *
     * @param list<TMessage> $keptMessages
     * @param callable(TMessage): ?string $readUserPrompt
     */
    protected function findReplacedExchangeStart(array $keptMessages, callable $readUserPrompt, string $prompt): int
    {
        $latestUserMessageIndex = null;

        for ($i = count($keptMessages) - 1; $i >= 0; $i--) {
            $userPrompt = $readUserPrompt($keptMessages[$i]);

            if ($userPrompt === null) {
                continue;
            }

            if ($this->removeMarker($userPrompt) === $prompt) {
                return $i;
            }

            $latestUserMessageIndex ??= $i;
        }

        return $latestUserMessageIndex ?? count($keptMessages);
    }
}
