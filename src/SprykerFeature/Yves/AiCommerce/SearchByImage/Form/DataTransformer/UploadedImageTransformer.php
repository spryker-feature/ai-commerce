<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Form\DataTransformer;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @implements \Symfony\Component\Form\DataTransformerInterface<\Generated\Shared\Transfer\SearchByImageRequestTransfer|null, array<string, mixed>>
 */
class UploadedImageTransformer implements DataTransformerInterface
{
    public function __construct(protected string $imageFieldName)
    {
    }

    /**
     * {@inheritDoc}
     *
     * File inputs cannot be pre-populated, so there is nothing to transform back to view format.
     */
    public function transform(mixed $value): mixed
    {
        return [$this->imageFieldName => null];
    }

    /**
     * {@inheritDoc}
     *
     * Converts the uploaded file into a SearchByImageRequestTransfer with base64-encoded content and MIME type.
     */
    public function reverseTransform(mixed $value): ?SearchByImageRequestTransfer
    {
        $uploadedFile = $value[$this->imageFieldName] ?? null;

        if (!$uploadedFile instanceof UploadedFile) {
            return null;
        }

        $imageContent = file_get_contents($uploadedFile->getPathname());

        if ($imageContent === false) {
            return null;
        }

        return (new SearchByImageRequestTransfer())
            ->setImageContent(base64_encode($imageContent))
            ->setImageMediaType((string)$uploadedFile->getMimeType());
    }
}
