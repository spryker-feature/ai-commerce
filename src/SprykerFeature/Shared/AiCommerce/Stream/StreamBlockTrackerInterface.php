<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

interface StreamBlockTrackerInterface
{
    public function openBlockId(StreamEventBlockType $streamEventBlockType): string;

    public function closeOpenBlock(): void;

    public function resetBlocks(): void;
}
