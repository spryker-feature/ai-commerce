<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 */
class SearchByImageForm extends AbstractType
{
    public const string FIELD_IMAGE = 'image';

    protected const string GLOSSARY_KEY_LABEL_IMAGE = 'ai_commerce.search_by_image.form.image.label';

    protected const string GLOSSARY_KEY_ERROR_MIME_TYPE = 'ai_commerce.search_by_image.form.image.error.mime_type';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addImageField($builder);
        $builder->addModelTransformer($this->getFactory()->createUploadedImageTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }

    public function getBlockPrefix(): string
    {
        return 'search_by_image';
    }

    protected function addImageField(FormBuilderInterface $builder): static
    {
        $allowedMimeTypes = $this->getConfig()->getAllowedImageMimeTypes();
        $maxImageSizeBytes = $this->getConfig()->getMaxImageSizeBytes();
        $builder->add(static::FIELD_IMAGE, FileType::class, [
            'label' => static::GLOSSARY_KEY_LABEL_IMAGE,
            'required' => true,
            'constraints' => [
                new File([
                    'maxSize' => $maxImageSizeBytes,
                    'mimeTypes' => $allowedMimeTypes,
                    'mimeTypesMessage' => static::GLOSSARY_KEY_ERROR_MIME_TYPE,
                ]),
            ],
            'attr' => [
                'accept' => implode(', ', $allowedMimeTypes),
                'data-max-file-size' => $maxImageSizeBytes,
            ],
        ]);

        return $this;
    }
}
