<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttachmentExtension\Dependency\Plugin\ProductAttachmentFormExpanderPluginInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class KnowledgeSelectionProductAttachmentFormExpanderPlugin extends AbstractPlugin implements ProductAttachmentFormExpanderPluginInterface
{
    protected const string FIELD_IS_ASSISTANT_KNOWLEDGE = 'is_assistant_knowledge';

    protected const string LABEL_IS_ASSISTANT_KNOWLEDGE = 'Use for assistant knowledge';

    /**
     * {@inheritDoc}
     * - Adds the optional `is_assistant_knowledge` checkbox to the product attachment row form.
     *
     * @api
     *
     * @param array<string, mixed> $options
     */
    public function expand(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        return $builder->add(static::FIELD_IS_ASSISTANT_KNOWLEDGE, CheckboxType::class, [
            'label' => static::LABEL_IS_ASSISTANT_KNOWLEDGE,
            'required' => false,
        ]);
    }
}
