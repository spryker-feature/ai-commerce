<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Dependency;

interface AiCommerceEvents
{
    /**
     * Specification:
     * - This event is used for the `spy_product_attachment_knowledge_selection` entity creation.
     *
     * @api
     */
    public const string ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CREATE = 'Entity.spy_product_attachment_knowledge_selection.create';

    /**
     * Specification:
     * - This event is used for the `spy_product_attachment_knowledge_selection` entity deletion.
     *
     * @api
     */
    public const string ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_DELETE = 'Entity.spy_product_attachment_knowledge_selection.delete';

    /**
     * Specification:
     * - This event is used to publish a changed knowledge selection of a product attachment, for example after a data import.
     * - The event entity ID is the product attachment ID.
     *
     * @api
     */
    public const string PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH = 'AiCommerce.product_attachment_knowledge_selection.publish';
}
