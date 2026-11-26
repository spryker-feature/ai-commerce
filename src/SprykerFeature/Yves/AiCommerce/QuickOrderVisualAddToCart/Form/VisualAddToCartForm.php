<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 */
class VisualAddToCartForm extends AbstractType
{
    public const string FIELD_ORDER_FILE = 'orderFile';

    public const string SUBMIT_BUTTON_UPLOAD_ORDER_FILE = 'uploadOrderFile';

    protected const string TEMPLATE_PATH = '@AiCommerce/components/molecules/quick-order-visual-add-to-cart/quick-order-visual-add-to-cart.twig';

    protected const string KEY_SUPPORTED_FILE_EXTENSIONS = 'supportedFileExtensions';

    protected const string KEY_MAX_FILE_SIZE_IN_BYTES = 'maxFileSizeInBytes';

    /**
     * @param array<string, mixed> $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['template_path'] = static::TEMPLATE_PATH;
        $view->vars[static::KEY_SUPPORTED_FILE_EXTENSIONS] = $this->getConfig()->getQuickOrderVisualAddToCartSupportedFileExtensions();
        $view->vars[static::KEY_MAX_FILE_SIZE_IN_BYTES] = $this->getConfig()->getQuickOrderVisualAddToCartMaxFileSizeInBytes();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addOrderFileField($builder);
    }

    protected function addOrderFileField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ORDER_FILE, FileType::class, [
            'label' => false,
            'constraints' => [
                $this->getFactory()->createVisualAddToCartFormConstraint(),
            ],
            'attr' => [
                'accept' => implode(',', $this->getConfig()->getQuickOrderVisualAddToCartSupportedFileMimeTypes()),
                'data-qa' => 'quick-order-visual-add-to-cart-file-input',
            ],
        ]);

        return $this;
    }
}
