<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversationQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Zed\AiCommerce\Persistence\Propel\Mapper\BackofficeAssistantConversationMapper;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface getRepository()
 */
class AiCommercePersistenceFactory extends AbstractPersistenceFactory
{
    public function createBackofficeAssistantConversationQuery(): SpyBackofficeAssistantConversationQuery
    {
        return SpyBackofficeAssistantConversationQuery::create();
    }

    public function createBackofficeAssistantConversationMapper(): BackofficeAssistantConversationMapper
    {
        return new BackofficeAssistantConversationMapper();
    }
}
