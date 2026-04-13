<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class FillFormToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    protected const string KEY_FIELDS = 'fields';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'fill_form';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Applies a set of field values to the form on the current Backoffice page. '
            . 'Call this tool exactly once after you have resolved all field values from the user\'s instruction and the form structure provided in context. '
            . 'The "fields" argument must be a key/value object where each key is the exact "name" attribute of a form field '
            . 'and each value is the desired field value. '
            . 'Do not include fields you are not changing. Do not invent field names.'
            . 'Example of tool parameter: {"fields": {"form[section][first_name]": "Jane", "form[section][last_name]": "Smith", "form[section][is_active]": true}}';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                static::KEY_FIELDS,
                'object',
                'Key/value pairs where each key is the exact "name" attribute of a form field and each value is the desired value to set. '
                . 'Rules by field type: '
                . 'text/email/number/textarea — pass the value as a string; '
                . 'date/datetime — use the format shown in the placeholder (e.g. "2024-12-31"); '
                . 'select — use the option "value" attribute, not the visible label; '
                . 'checkbox/radio — use true or false. '
                . 'Only include fields you intend to fill. Never invent field names outside the provided form structure. '
                . 'Field names often use bracket notation exactly as provided in the form structure. ',
                true,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     * - Emits SSE event with type `FormFill`
     *
     * @api
     *
     * @param mixed ...$arguments
     */
    public function execute(...$arguments): mixed
    {
        $fields = $arguments[static::KEY_FIELDS] ?? [];

        $this->getFactory()->createSseEventEmitter()->emit(BackofficeAssistantEventType::FormFill, [
            static::KEY_FIELDS => $fields,
        ]);

        return json_encode(['success' => true, 'filled_fields' => array_keys((array)$fields)], JSON_THROW_ON_ERROR);
    }
}
