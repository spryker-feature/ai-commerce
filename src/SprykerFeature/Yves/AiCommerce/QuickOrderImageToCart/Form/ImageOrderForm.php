<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 */
class ImageOrderForm extends AbstractType
{
    public const string FIELD_UPLOAD_IMAGE_ORDER = 'uploadImageOrder';

    public const string SUBMIT_BUTTON_UPLOAD_IMAGE = 'uploadImage';

    protected const string TEMPLATE_PATH = '@AiCommerce/components/molecules/quick-order-image-to-cart/quick-order-image-to-cart.twig';

    protected const string KEY_SUPPORTED_IMAGE_EXTENSIONS = 'supportedImageExtensions';

    protected const string KEY_MAX_FILE_SIZE_IN_BYTES = 'maxFileSizeInBytes';

    /**
     * @param array<string, mixed> $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['template_path'] = static::TEMPLATE_PATH;
        $view->vars[static::KEY_SUPPORTED_IMAGE_EXTENSIONS] = $this->getConfig()->getQuickOrderImageToCartSupportedImageExtensions();
        $view->vars[static::KEY_MAX_FILE_SIZE_IN_BYTES] = $this->getConfig()->getQuickOrderImageToCartMaxFileSizeInBytes();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUploadImageOrderField($builder);
    }

    protected function addUploadImageOrderField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_UPLOAD_IMAGE_ORDER, FileType::class, [
            'label' => false,
            'constraints' => [
                $this->getFactory()->createImageOrderFormConstraint(),
            ],
            'attr' => [
                'accept' => implode(',', $this->getConfig()->getQuickOrderImageToCartSupportedMimeTypes()),
            ],
        ]);

        return $this;
    }
}
