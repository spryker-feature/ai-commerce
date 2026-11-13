<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Constraint;

use Symfony\Component\Validator\Constraint;

class StorefrontAssistantSuggestedPromptsConstraint extends Constraint
{
    public const string PARAMETER_LOCALE = '{{ locale }}';

    public string $invalidFormatMessage = 'Enter a JSON object with a locale name as each key and a list of the prompts of that locale as each value, for example {"en_US": ["What is new?", "What is on sale?"]}.';

    public string $unknownLocaleMessage = 'The locale "{{ locale }}" is not assigned to any store.';

    public string $invalidPromptsMessage = 'Enter the prompts of the locale "{{ locale }}" as a list of text values.';

    public function validatedBy(): string
    {
        return StorefrontAssistantSuggestedPromptsConstraintValidator::class;
    }
}
