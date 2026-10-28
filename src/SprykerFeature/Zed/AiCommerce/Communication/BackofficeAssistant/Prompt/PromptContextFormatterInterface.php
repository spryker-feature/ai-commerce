<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

interface PromptContextFormatterInterface
{
    /**
     * @throws \JsonException
     */
    public function formatPrompt(string $message, mixed $pageContext, mixed $formContext, bool $isRegeneration = false): string;

    public function stripContext(string $prompt): string;
}
