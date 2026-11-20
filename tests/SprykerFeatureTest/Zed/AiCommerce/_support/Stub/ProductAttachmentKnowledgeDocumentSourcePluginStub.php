<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Stub;

use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentSourcePlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;

class ProductAttachmentKnowledgeDocumentSourcePluginStub implements KnowledgeDocumentSourcePluginInterface
{
    protected const string MIME_TYPE_TEXT_PLAIN = 'text/plain';

    protected ProductAttachmentKnowledgeDocumentSourcePlugin $productAttachmentKnowledgeDocumentSourcePlugin;

    /**
     * @var list<string>
     */
    protected array $readSourceIdentifiers = [];

    public function __construct(protected string $content)
    {
        $this->productAttachmentKnowledgeDocumentSourcePlugin = new ProductAttachmentKnowledgeDocumentSourcePlugin();
    }

    public function getSourceType(): string
    {
        return $this->productAttachmentKnowledgeDocumentSourcePlugin->getSourceType();
    }

    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return $this->productAttachmentKnowledgeDocumentSourcePlugin->getSubscribedEvents();
    }

    /**
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<string>
     */
    public function extractSourceIdentifiers(array $eventEntityTransfers, string $eventName): array
    {
        return $this->productAttachmentKnowledgeDocumentSourcePlugin->extractSourceIdentifiers($eventEntityTransfers, $eventName);
    }

    public function getDocumentSourceCollection(
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer,
    ): KnowledgeDocumentSourceCollectionTransfer {
        return $this->productAttachmentKnowledgeDocumentSourcePlugin->getDocumentSourceCollection($knowledgeDocumentSourceCriteriaTransfer);
    }

    public function readDocumentContent(
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
    ): KnowledgeDocumentContentTransfer {
        $this->readSourceIdentifiers[] = $knowledgeDocumentSourceTransfer->getSourceIdentifierOrFail();

        return (new KnowledgeDocumentContentTransfer())
            ->setKnowledgeDocumentSource($knowledgeDocumentSourceTransfer)
            ->setContent($this->content)
            ->setMimeType(static::MIME_TYPE_TEXT_PLAIN)
            ->setIsSuccessful(true);
    }

    /**
     * @return list<string>
     */
    public function getReadSourceIdentifiers(): array
    {
        return $this->readSourceIdentifiers;
    }
}
