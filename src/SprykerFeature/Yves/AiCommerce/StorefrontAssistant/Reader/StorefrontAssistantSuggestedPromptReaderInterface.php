<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader;

interface StorefrontAssistantSuggestedPromptReaderInterface
{
    /**
     * @return array<string, list<string>>
     */
    public function getSuggestedPrompts(): array;

    /**
     * @return array<string, list<array{title: string, prompts: list<string>}>>
     */
    public function getSuggestedPromptSections(): array;
}
