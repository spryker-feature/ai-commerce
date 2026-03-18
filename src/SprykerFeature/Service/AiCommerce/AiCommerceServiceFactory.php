<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AiCommerce;

use Spryker\Service\Kernel\AbstractServiceFactory;
use SprykerFeature\Service\AiCommerce\BackofficeAssistant\Generator\ConversationReferenceGenerator;
use SprykerFeature\Service\AiCommerce\BackofficeAssistant\Generator\ConversationReferenceGeneratorInterface;

class AiCommerceServiceFactory extends AbstractServiceFactory
{
    public function createConversationReferenceGenerator(): ConversationReferenceGeneratorInterface
    {
        return new ConversationReferenceGenerator();
    }
}
