<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductManagement;

use Generated\Shared\Transfer\ProductAbstractTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractFormTabContentProviderWithPriorityPluginInterface;

class ProductManagementAiProductAbstractFormTabContentProviderWithPriorityPlugin extends AbstractPlugin implements ProductAbstractFormTabContentProviderWithPriorityPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getTabName(): string
    {
        return 'general';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function provideTabContent(?ProductAbstractTransfer $productAbstractTransfer = null): array
    {
        return [
            '@SprykerFeature:AiCommerce/SmartProductManagement/_partials/category-modal-trigger.twig',
        ];
    }
}
