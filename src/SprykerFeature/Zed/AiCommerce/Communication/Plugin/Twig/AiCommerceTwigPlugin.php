<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\Twig;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class AiCommerceTwigPlugin extends AbstractPlugin implements TwigPluginInterface
{
    protected const string FUNCTION_NAME_IS_BACKOFFICE_ASSISTANT_ENABLED = 'isBackofficeAssistantEnabled';

    protected const string GLOBAL_VARIABLE_IS_BACKOFFICE_ASSISTANT_CONNECTED = 'isBackofficeAssistantConnected';

    protected const string FUNCTION_NAME_GET_FORM_FILL_EXCLUDED_FORM_NAMES = 'getFormFillExcludedFormNames';

    /**
     * {@inheritDoc}
     * - Adds isBackofficeAssistantEnabled Twig function.
     * - Adds isBackofficeAssistantConnected Twig global variable.
     * - Adds getFormFillExcludedFormNames Twig function.
     *
     * @api
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment
    {
        $twig->addFunction($this->createIsBackofficeAssistantEnabledFunction());
        $twig->addGlobal(static::GLOBAL_VARIABLE_IS_BACKOFFICE_ASSISTANT_CONNECTED, true);
        $twig->addFunction($this->createGetFormFillExcludedFormNamesFunction());

        return $twig;
    }

    protected function createIsBackofficeAssistantEnabledFunction(): TwigFunction
    {
        return new TwigFunction(
            static::FUNCTION_NAME_IS_BACKOFFICE_ASSISTANT_ENABLED,
            fn (): bool => $this->getConfig()->isBackofficeAssistantEnabled(),
        );
    }

    protected function createGetFormFillExcludedFormNamesFunction(): TwigFunction
    {
        return new TwigFunction(
            static::FUNCTION_NAME_GET_FORM_FILL_EXCLUDED_FORM_NAMES,
            fn (): array => $this->getConfig()->getFormFillExcludedFormNames(),
        );
    }
}
