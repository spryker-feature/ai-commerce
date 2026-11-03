<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Validator;

use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestPayloadMapperInterface;
use Symfony\Component\HttpFoundation\Request;

class StorefrontAssistantRequestPayloadValidator implements StorefrontAssistantRequestPayloadValidatorInterface
{
    protected const string CONTENT_TYPE_JSON = 'json';

    protected const int PAYLOAD_BASE64_NUMERATOR = 4;

    protected const int PAYLOAD_BASE64_DENOMINATOR = 3;

    protected const int PAYLOAD_OVERHEAD_BYTES = 262144;

    protected const string KEY_ATTACHMENTS = 'attachments';

    protected const string KEY_SELECTED_AGENT = 'selectedAgent';

    protected const string KEY_AGENT_NAME = 'name';

    public function __construct(
        protected AiCommerceConfig $aiCommerceConfig,
        protected AiCommerceClientInterface $aiCommerceClient,
        protected StorefrontAssistantRequestPayloadMapperInterface $storefrontAssistantRequestPayloadMapper
    ) {
    }

    public function isRequestPayloadValid(Request $request): bool
    {
        if ($request->getContentTypeFormat() !== static::CONTENT_TYPE_JSON) {
            return false;
        }

        $content = (string)$request->getContent();

        return $content !== '' && strlen($content) <= $this->resolveMaxPayloadSizeBytes();
    }

    /**
     * @param array<mixed> $payload
     */
    public function isChatPayloadValid(array $payload): bool
    {
        if (array_key_exists(static::KEY_ATTACHMENTS, $payload) && !is_array($payload[static::KEY_ATTACHMENTS])) {
            return false;
        }

        return $this->isSelectedAgentValid($payload[static::KEY_SELECTED_AGENT] ?? null);
    }

    /**
     * @param array<mixed> $payload
     */
    public function isConversationDeletePayloadValid(array $payload): bool
    {
        return $this->storefrontAssistantRequestPayloadMapper->mapPayloadToConversationReferences($payload) !== [];
    }

    protected function isSelectedAgentValid(mixed $selectedAgent): bool
    {
        if ($selectedAgent === null || $selectedAgent === '') {
            return true;
        }

        if (!is_string($selectedAgent)) {
            return false;
        }

        foreach ($this->aiCommerceClient->getStorefrontAssistantEnabledAgents() as $enabledAgent) {
            if (($enabledAgent[static::KEY_AGENT_NAME] ?? null) === $selectedAgent) {
                return true;
            }
        }

        return false;
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
