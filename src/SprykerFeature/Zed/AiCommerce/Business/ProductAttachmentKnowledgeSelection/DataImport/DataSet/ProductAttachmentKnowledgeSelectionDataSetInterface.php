<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\DataImport\DataSet;

interface ProductAttachmentKnowledgeSelectionDataSetInterface
{
    public const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    public const string COLUMN_LOCALE = 'locale';

    public const string COLUMN_ATTACHMENT_URL = 'attachment_url';

    public const string COLUMN_IS_ASSISTANT_KNOWLEDGE = 'is_assistant_knowledge';
}
