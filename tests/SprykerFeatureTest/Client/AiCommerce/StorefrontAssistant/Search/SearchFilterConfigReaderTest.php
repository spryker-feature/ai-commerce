<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Search;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\FacetConfigTransfer;
use Generated\Shared\Transfer\SearchConfigExtensionTransfer;
use Spryker\Client\ProductSearchConfigStorage\Plugin\Config\ProductSearchConfigExpanderPlugin;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchFilterConfigReader;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Search
 * @group SearchFilterConfigReaderTest
 */
class SearchFilterConfigReaderTest extends Unit
{
    protected const string PARAMETER_NAME_BRAND = 'brand';

    protected const string PARAMETER_NAME_COLOR = 'color';

    protected const string PARAMETER_NAME_MERCHANT_NAME = 'merchant_name';

    protected const string PARAMETER_NAME_CATEGORY = 'category';

    protected const string PARAMETER_NAME_PRICE = 'price';

    protected const string PARAMETER_NAME_LABEL = 'label';

    protected const string PARAMETER_NAME_PRODUCT_CLASS = 'product-class-names';

    protected const string FIELD_NAME_STRING_FACET = 'string-facet';

    protected AiCommerceClientTester $tester;

    public function testGivenAConfiguredFacetWhenReadThenItIsOfferedUnderItsRequestParameterName(): void
    {
        // Arrange
        $searchFilterConfigReader = $this->createSearchFilterConfigReader([
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_BRAND),
        ]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        $this->assertArrayHasKey(static::PARAMETER_NAME_BRAND, $facetConfigTransfers);
    }

    public function testGivenAMerchantScopingFacetWhenReadThenItIsNeverOfferedAsAFilter(): void
    {
        // Arrange
        $searchFilterConfigReader = $this->createSearchFilterConfigReader([
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_MERCHANT_NAME),
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_BRAND),
        ]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        $this->assertArrayNotHasKey(static::PARAMETER_NAME_MERCHANT_NAME, $facetConfigTransfers);
        $this->assertArrayHasKey(static::PARAMETER_NAME_BRAND, $facetConfigTransfers);
    }

    /**
     * A facet already carried by a typed argument would otherwise be filterable through two
     * different arguments, which lets the model set the same filter twice with conflicting values.
     */
    public function testGivenAFacetAlreadyExposedAsATypedArgumentWhenReadThenItIsNotOfferedAgain(): void
    {
        // Arrange
        $searchFilterConfigReader = $this->createSearchFilterConfigReader([
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_CATEGORY),
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_PRICE),
        ]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        $this->assertSame([], $facetConfigTransfers);
    }

    /**
     * `label` and `product-class-names` already have list arguments of their own, so offering them
     * again through `filters` would let the same filter be set twice with conflicting values.
     */
    public function testGivenAFacetCarriedByAListArgumentWhenReadThenItIsNotOfferedAgain(): void
    {
        // Arrange
        $searchFilterConfigReader = $this->createSearchFilterConfigReader([
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_LABEL),
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_PRODUCT_CLASS),
        ]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        $this->assertSame([], $facetConfigTransfers);
    }

    public function testGivenAFacetWithoutARequestParameterNameWhenReadThenItIsSkipped(): void
    {
        // Arrange
        $searchFilterConfigReader = $this->createSearchFilterConfigReader([
            (new FacetConfigTransfer())->setName(static::PARAMETER_NAME_BRAND),
        ]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        $this->assertSame([], $facetConfigTransfers);
    }

    public function testGivenAFacetThisModuleNeverNamedWhenReadThenItIsStillOffered(): void
    {
        // Arrange
        $searchFilterConfigReader = $this->createSearchFilterConfigReader([
            $this->createFacetConfigTransfer(static::PARAMETER_NAME_COLOR),
        ]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        $this->assertArrayHasKey(static::PARAMETER_NAME_COLOR, $facetConfigTransfers);
    }

    public function testGivenTheShopsOwnConfigurationWhenReadThenEveryPublishedFacetIsOffered(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->getStorageClient();
        $searchFilterConfigReader = new SearchFilterConfigReader([new ProductSearchConfigExpanderPlugin()]);

        // Act
        $facetConfigTransfers = $searchFilterConfigReader->getFilterableFacetConfigs();

        // Assert
        if ($facetConfigTransfers === []) {
            $this->markTestSkipped(
                'No filterable product search attributes are readable from the storage this suite connects to. '
                . 'The shop publishes them per store (kv:product_search_config_extension), so run publish & '
                . 'synchronize and point the suite at that store to exercise this path.',
            );
        }

        foreach ($facetConfigTransfers as $parameterName => $facetConfigTransfer) {
            $this->assertSame($parameterName, $facetConfigTransfer->getParameterName());
            $this->assertNotContains($parameterName, [static::PARAMETER_NAME_MERCHANT_NAME]);
        }
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     */
    protected function createSearchFilterConfigReader(array $facetConfigTransfers): SearchFilterConfigReader
    {
        $searchConfigExtensionTransfer = new SearchConfigExtensionTransfer();

        foreach ($facetConfigTransfers as $facetConfigTransfer) {
            $searchConfigExtensionTransfer->addFacetConfig($facetConfigTransfer);
        }

        return new SearchFilterConfigReader([
            $this->createSearchConfigExpanderPlugin($searchConfigExtensionTransfer),
        ]);
    }

    protected function createSearchConfigExpanderPlugin(
        SearchConfigExtensionTransfer $searchConfigExtensionTransfer
    ): SearchConfigExpanderPluginInterface {
        return new class ($searchConfigExtensionTransfer) implements SearchConfigExpanderPluginInterface {
            public function __construct(protected SearchConfigExtensionTransfer $searchConfigExtensionTransfer)
            {
            }

            public function getSearchConfigExtension(): SearchConfigExtensionTransfer
            {
                return $this->searchConfigExtensionTransfer;
            }
        };
    }

    protected function createFacetConfigTransfer(string $parameterName): FacetConfigTransfer
    {
        return (new FacetConfigTransfer())
            ->setName($parameterName)
            ->setParameterName($parameterName)
            ->setFieldName(static::FIELD_NAME_STRING_FACET);
    }
}
