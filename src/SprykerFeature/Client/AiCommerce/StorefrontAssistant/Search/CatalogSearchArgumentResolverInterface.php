<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface CatalogSearchArgumentResolverInterface
{
    /**
     * @param array<int|string, mixed> $arguments
     */
    public function resolveQuery(array $arguments): string;

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function resolvePage(array $arguments): int;

    /**
     * @param-out array<int, array<string, string>> $ignoredArguments
     *
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     */
    public function resolveLimit(array $arguments, array &$ignoredArguments): int;

    /**
     * @param-out array<int, array<string, string>> $ignoredArguments
     *
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     *
     * @return array<string, mixed>
     */
    public function resolveRequestParameters(
        array $arguments,
        array &$ignoredArguments,
        int $page,
        int $limit,
        array $facetConfigTransfers
    ): array;

    public function formatFilterArgument(string $parameterName): string;
}
