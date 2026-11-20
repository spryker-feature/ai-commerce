<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\DataImport;

use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReportTransfer;
use Spryker\Zed\DataImport\Dependency\Plugin\DataImportPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class ProductAttachmentKnowledgeSelectionDataImportPlugin extends AbstractPlugin implements DataImportPluginInterface
{
    /**
     * {@inheritDoc}
     * - Imports the knowledge selection of product attachments from the `abstract_sku,locale,attachment_url,is_assistant_knowledge` CSV file.
     * - Matches the product attachments of the product abstract by abstract SKU, locale and URL; `default` and an empty locale match the attachments without a locale.
     * - Changes all matched product attachments; reports a row without a match as an error that contains the abstract SKU, the locale and the URL.
     * - Creates the knowledge selection for `1` and deletes it for `0`; does not write an unchanged selection.
     * - Imports each row in its own transaction.
     * - Adds the `AiCommerce.product_attachment_knowledge_selection.publish` event with the product attachment ID for each changed selection.
     *
     * @api
     */
    public function import(?DataImporterConfigurationTransfer $dataImporterConfigurationTransfer = null): DataImporterReportTransfer
    {
        return $this->getBusinessFactory()
            ->getProductAttachmentKnowledgeSelectionDataImporter($dataImporterConfigurationTransfer)
            ->import($dataImporterConfigurationTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getImportType(): string
    {
        return AiCommerceConfig::IMPORT_TYPE_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION;
    }
}
