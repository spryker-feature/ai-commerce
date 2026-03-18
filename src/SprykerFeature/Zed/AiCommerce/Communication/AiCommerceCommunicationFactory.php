<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\User\Business\UserFacadeInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class AiCommerceCommunicationFactory extends AbstractCommunicationFactory
{
    public function getUserFacade(): UserFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_USER);
    }

    /**
     * @return array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface>
     */
    public function getBackofficeAssistantAgentPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_BACKOFFICE_ASSISTANT_AGENT);
    }

    public function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::SERVICE_FORM_CSRF_PROVIDER);
    }
}
