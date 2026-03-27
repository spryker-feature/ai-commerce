<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversationQuery;
use Orm\Zed\Discount\Persistence\SpyDiscountQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
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

    public function getDiscountPropelQuery(): SpyDiscountQuery
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PROPEL_QUERY_DISCOUNT);
    }

    public function getSalesOrderPropelQuery(): SpySalesOrderQuery
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PROPEL_QUERY_SALES_ORDER);
    }

    public function createBackofficeAssistantConversationMapper(): BackofficeAssistantConversationMapper
    {
        return new BackofficeAssistantConversationMapper();
    }
}
