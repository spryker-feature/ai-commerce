<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool;

use Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface;
use Spryker\Shared\AiFoundation\Tools\ToolParameterType;

class ToolParameter implements ToolParameterInterface
{
    public function __construct(
        protected string $name,
        protected ToolParameterType $type,
        protected string $description,
        protected bool $isRequired
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ToolParameterType
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }
}
