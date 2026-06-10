<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentValidator;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group SmartCmsContentAttachmentValidatorTest
 */
class SmartCmsContentAttachmentValidatorTest extends Unit
{
    protected const string ALLOWED_MEDIA_TYPE = 'application/pdf';

    protected const string DISALLOWED_MEDIA_TYPE = 'application/x-msdownload';

    protected const string MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE = 'backoffice_assistant.validation.attachment_unsupported_media_type';

    protected const string MESSAGE_ATTACHMENT_FILE_TOO_LARGE = 'backoffice_assistant.validation.attachment_file_too_large';

    protected AiCommerceCommunicationTester $tester;

    public function testValidRawAttachmentPassesValidation(): void
    {
        // Arrange
        $rawAttachments = [['mediaType' => static::ALLOWED_MEDIA_TYPE, 'content' => base64_encode('small pdf bytes')]];

        // Act
        $errors = (new AttachmentValidator(new AiCommerceConfig()))->validateRawAttachments($rawAttachments);

        // Assert
        $this->assertSame([], $errors);
    }

    public function testDisallowedMediaTypeIsRejected(): void
    {
        // Arrange
        $rawAttachments = [['mediaType' => static::DISALLOWED_MEDIA_TYPE, 'content' => base64_encode('payload')]];

        // Act
        $errors = (new AttachmentValidator(new AiCommerceConfig()))->validateRawAttachments($rawAttachments);

        // Assert
        $this->assertContains(static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE, $this->getErrorMessages($errors));
    }

    public function testOversizedAttachmentIsRejected(): void
    {
        // Arrange
        $config = new AiCommerceConfig();
        $oversizedContent = base64_encode(str_repeat('a', $config->getBackofficeAssistantAttachmentMaxFileSizeBytes() + 1));
        $rawAttachments = [['mediaType' => static::ALLOWED_MEDIA_TYPE, 'content' => $oversizedContent]];

        // Act
        $errors = (new AttachmentValidator($config))->validateRawAttachments($rawAttachments);

        // Assert
        $this->assertContains(static::MESSAGE_ATTACHMENT_FILE_TOO_LARGE, $this->getErrorMessages($errors));
    }

    /**
     * @param array<\Generated\Shared\Transfer\ErrorTransfer> $errors
     *
     * @return array<string>
     */
    protected function getErrorMessages(array $errors): array
    {
        return array_map(static fn (ErrorTransfer $errorTransfer): ?string => $errorTransfer->getMessage(), $errors);
    }
}
