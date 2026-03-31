<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 */
class ImageSearchAiWidget extends AbstractWidget
{
    protected const string PARAMETER_IS_ENABLED = 'isEnabled';

    protected const string PARAMETER_FORM = 'form';

    public function __construct()
    {
        $isEnabled = $this->getConfig()->isSearchByImageEnabled();

        $this->addIsEnabledParameter($isEnabled);
        $this->addFormParameter($isEnabled);
    }

    protected function addIsEnabledParameter(bool $isEnabled): void
    {
        $this->addParameter(static::PARAMETER_IS_ENABLED, $isEnabled);
    }

    protected function addFormParameter(bool $isEnabled): void
    {
        $formView = $isEnabled ? $this->getFactory()->getSearchByImageForm()->createView() : null;
        $this->addParameter(static::PARAMETER_FORM, $formView);
    }

    public static function getName(): string
    {
        return 'ImageSearchAiWidget';
    }

    public static function getTemplate(): string
    {
        return '@AiCommerce/views/search-by-image/search-by-image.twig';
    }
}
