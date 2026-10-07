<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Validator;

use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use Symfony\Component\HttpFoundation\Request;

class StorefrontAssistantRequestPayloadValidator implements StorefrontAssistantRequestPayloadValidatorInterface
{
    protected const string CONTENT_TYPE_JSON = 'json';

    protected const int PAYLOAD_BASE64_NUMERATOR = 4;

    protected const int PAYLOAD_BASE64_DENOMINATOR = 3;

    protected const int PAYLOAD_OVERHEAD_BYTES = 262144;

    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    public function isRequestPayloadValid(Request $request): bool
    {
        if ($request->getContentTypeFormat() !== static::CONTENT_TYPE_JSON) {
            return false;
        }

        $content = (string)$request->getContent();

        return $content !== '' && strlen($content) <= $this->resolveMaxPayloadSizeBytes();
    }

    protected function resolveMaxPayloadSizeBytes(): int
    {
        $maxAttachmentBytes = $this->aiCommerceConfig->getStorefrontAssistantMaxAttachmentSizeBytes()
            * $this->aiCommerceConfig->getStorefrontAssistantMaxAttachmentCount();

        return intdiv(
            $maxAttachmentBytes * static::PAYLOAD_BASE64_NUMERATOR,
            static::PAYLOAD_BASE64_DENOMINATOR,
        ) + static::PAYLOAD_OVERHEAD_BYTES;
    }
}
