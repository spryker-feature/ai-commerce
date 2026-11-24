<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Document;

use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;

interface DocumentToolArgumentMapperInterface
{
    /**
     * @param array<int|string, mixed> $arguments
     */
    public function mapArgumentsToKnowledgeDocumentScopeRequest(array $arguments): KnowledgeDocumentScopeRequestTransfer;

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return list<string>
     */
    public function mapArgumentToStrings(array $arguments, string $parameterName): array;
}
