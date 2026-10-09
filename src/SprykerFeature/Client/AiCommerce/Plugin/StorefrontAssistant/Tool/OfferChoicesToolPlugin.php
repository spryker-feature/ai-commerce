<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Shared\AiFoundation\Tools\ToolParameterType;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice\ChoiceOfferReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class OfferChoicesToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'offer_choices';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Shows one question with 2 to 6 tappable answers; a tap sends the answer as the next message. Call it once, as the last step of your reply, instead of listing options in text; it shows the question too.
        - Gift: ask in up to three replies, skipping any already answered: who it is for, a hobby a shop category serves, then budget ranges from that category's `priceRange` (browse it, never `query` the hobby). Then search within the budget (a stated one is `priceMin`/`priceMax` of every search), check your top pick with `product_details`, display 3+ products and say whether it offers gift wrapping.
        DESCRIPTION;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return static::TOOL_DESCRIPTION;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                ChoiceOfferReader::PARAMETER_QUESTION,
                ToolParameterType::String,
                sprintf('The question, at most %d characters.', ChoiceOfferReader::MAX_QUESTION_LENGTH),
                true,
            ),
            new ToolParameter(
                ChoiceOfferReader::PARAMETER_CHOICES,
                ToolParameterType::Array,
                sprintf(
                    '%d to %d short answers, at most %d characters each.',
                    ChoiceOfferReader::MIN_CHOICES,
                    ChoiceOfferReader::MAX_CHOICES,
                    ChoiceOfferReader::MAX_CHOICE_LENGTH,
                ),
                true,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     *
     * @return array<string, mixed>
     */
    public function execute(...$arguments): mixed
    {
        return $this->getFactory()->createChoiceOfferReader()->offerChoices($arguments);
    }
}
