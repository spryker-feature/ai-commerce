<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\CmsPageStorageTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage\ShopPageTextExtractor;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group ReadShopPageToolPluginTest
 */
class ReadShopPageToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_CMS_PAGE = 'idCmsPage';

    protected const string KEY_FOUND = 'found';

    protected const string KEY_ID_CMS_PAGE = 'idCmsPage';

    protected const string KEY_NAME = 'name';

    protected const string KEY_URL = 'url';

    protected const string KEY_TEXT = 'text';

    protected const string KEY_ERROR = 'error';

    protected const int ID_CMS_PAGE = 7;

    protected const string PAGE_NAME = 'Return policy';

    protected const string PAGE_URL = '/en/return-policy';

    protected const string DATE_FORMAT = 'Y-m-d H:i:s';

    protected AiCommerceClientTester $tester;

    public function testGivenActivePolicyPageWhenReadThenPlainTextAndUrlAreReturned(): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([$this->createCmsPageStorage(['content' => '<p>Return within <b>30 days</b> of receipt.</p>'])]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => static::ID_CMS_PAGE]);

        // Assert
        $this->assertSame([
            static::KEY_FOUND => true,
            static::KEY_ID_CMS_PAGE => static::ID_CMS_PAGE,
            static::KEY_NAME => static::PAGE_NAME,
            static::KEY_URL => static::PAGE_URL,
            static::KEY_TEXT => 'Return within 30 days of receipt.',
        ], $result);
    }

    public function testGivenInactivePageWhenReadThenNoTextIsReturned(): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([$this->createCmsPageStorage(['content' => 'Return within 30 days.'])->setIsActive(false)]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => static::ID_CMS_PAGE]);

        // Assert
        $this->assertFalse($result[static::KEY_FOUND]);
        $this->assertArrayNotHasKey(static::KEY_TEXT, $result);
    }

    /**
     * @dataProvider provideDatesOutsideValidity
     */
    public function testGivenPageOutsideValidityDatesWhenReadThenNoTextIsReturned(?string $validFrom, ?string $validTo): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([
            $this->createCmsPageStorage(['content' => 'Return within 30 days.'])->setValidFrom($validFrom)->setValidTo($validTo),
        ]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => static::ID_CMS_PAGE]);

        // Assert
        $this->assertFalse($result[static::KEY_FOUND]);
        $this->assertArrayNotHasKey(static::KEY_TEXT, $result);
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function provideDatesOutsideValidity(): array
    {
        return [
            'expired' => [static::formatDate('-10 days'), static::formatDate('-1 day')],
            'not yet valid' => [static::formatDate('+1 day'), static::formatDate('+10 days')],
            'only valid from in the future' => [static::formatDate('+1 day'), null],
            'only valid to in the past' => [null, static::formatDate('-1 day')],
        ];
    }

    public function testGivenPlaceholderWithTwigWidgetAndHtmlWhenReadThenOnlyPlainTextRemains(): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([$this->createCmsPageStorage([
            'title' => '<p><b><span style="font-size: 24px;">Return&nbsp;policy</span></b></p>',
            'content' => "{{ content_banner('br-1') }}<p>Free returns &amp; exchanges<br>within   30 days.</p>{% if true %}<script>alert(1)</script>",
        ])]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => static::ID_CMS_PAGE]);

        // Assert
        $this->assertSame("Return policy\nFree returns & exchanges\nwithin 30 days.", $result[static::KEY_TEXT]);
    }

    public function testGivenLongPageWhenReadThenTextIsCappedAt3000Characters(): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([$this->createCmsPageStorage(['content' => str_repeat('Returns are free. ', 400)])]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => static::ID_CMS_PAGE]);

        // Assert
        $this->assertLessThanOrEqual(ShopPageTextExtractor::MAX_TEXT_LENGTH, mb_strlen($result[static::KEY_TEXT]));
        $this->assertStringStartsWith('Returns are free.', $result[static::KEY_TEXT]);
    }

    public function testGivenUnknownPageIdWhenReadThenFoundIsFalse(): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([$this->createCmsPageStorage(['content' => 'Return within 30 days.'])]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => static::ID_CMS_PAGE + 1]);

        // Assert
        $this->assertFalse($result[static::KEY_FOUND]);
        $this->assertArrayNotHasKey(static::KEY_TEXT, $result);
    }

    public function testGivenMissingPageIdWhenReadThenAnErrorAsksForIt(): void
    {
        // Arrange
        $this->tester->haveCmsStorageClientReturning([]);

        // Act
        $result = $this->tester->executeReadShopPageTool([static::PARAMETER_ID_CMS_PAGE => 'returns']);

        // Assert
        $this->assertArrayHasKey(static::KEY_ERROR, $result);
    }

    /**
     * @param array<string, string> $placeholders
     */
    protected function createCmsPageStorage(array $placeholders): CmsPageStorageTransfer
    {
        return (new CmsPageStorageTransfer())
            ->setIdCmsPage(static::ID_CMS_PAGE)
            ->setName(static::PAGE_NAME)
            ->setUrl(static::PAGE_URL)
            ->setIsActive(true)
            ->setPlaceholders($placeholders);
    }

    protected static function formatDate(string $modifier): string
    {
        return (new DateTime($modifier))->format(static::DATE_FORMAT);
    }
}
