<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

class ShownProductRegistry implements ShownProductRegistryInterface
{
    /**
     * Static: each tool plugin creates its own factory, so the turn state is shared per process; holds one conversation, cleared by StorefrontAssistantChatStreamer at turn start and end.
     */
    protected static string $customerReference = '';

    /**
     * Static: the conversation of the current turn, cleared by StorefrontAssistantChatStreamer at turn start and end.
     */
    protected static string $conversationReference = '';

    /**
     * Static: products shown earlier in the conversation, loaded once per turn, cleared by StorefrontAssistantChatStreamer at turn start and end.
     *
     * @var list<int>|null
     */
    protected static ?array $conversationProductAbstractIds = null;

    /**
     * Static: products shown in the current turn, cleared by StorefrontAssistantChatStreamer at turn start and end.
     *
     * @var list<int>
     */
    protected static array $turnProductAbstractIds = [];

    public function __construct(protected ShownProductReaderInterface $shownProductReader)
    {
    }

    public function startTurn(string $customerReference, string $conversationReference): void
    {
        static::$customerReference = $customerReference;
        static::$conversationReference = $conversationReference;
        static::$conversationProductAbstractIds = null;
        static::$turnProductAbstractIds = [];
    }

    /**
     * @param array<int, int> $productAbstractIds
     */
    public function addShownProductAbstractIds(array $productAbstractIds): void
    {
        static::$turnProductAbstractIds = array_values(array_unique(array_merge(
            static::$turnProductAbstractIds,
            array_values($productAbstractIds),
        )));
    }

    /**
     * @return list<int>
     */
    public function getShownProductAbstractIds(): array
    {
        if (static::$conversationProductAbstractIds === null) {
            static::$conversationProductAbstractIds = $this->shownProductReader->getShownProductAbstractIds(
                static::$customerReference,
                static::$conversationReference,
            );
        }

        return array_values(array_unique(array_merge(
            static::$conversationProductAbstractIds,
            static::$turnProductAbstractIds,
        )));
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->startTurn('', '');
    }
}
