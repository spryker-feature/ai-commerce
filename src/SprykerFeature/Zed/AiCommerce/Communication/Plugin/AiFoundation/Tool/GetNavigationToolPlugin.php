<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class GetNavigationToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public function getName(): string
    {
        return 'get_navigation';
    }

    public function getDescription(): string
    {
        return 'Search Backoffice navigation to find pages and their paths. Use this tool when the user asks where to find something, how to navigate, or which menu item to use. Returns matching navigation entries with their relative URL paths (e.g., /sales/order/index).';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     */
    public function execute(...$arguments): mixed
    {
        $query = strtolower((string)($arguments[0] ?? ''));
        $navigation = json_decode((string)file_get_contents($this->getConfig()->getBackofficeNavigationCachePath()), true);

        if (!is_array($navigation)) {
            return json_encode(['error' => 'Navigation cache could not be parsed.']);
        }

        $matches = $this->searchNavigation($navigation, $query);

        if ($matches === []) {
            return json_encode(['message' => sprintf('No navigation entries found for "%s".', $query)]);
        }

        return json_encode($matches);
    }

    /**
     * @param array<mixed> $navigation
     * @param string $query
     *
     * @return array<array<string, string>>
     */
    protected function searchNavigation(array $navigation, string $query): array
    {
        $results = [];

        foreach ($navigation as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (isset($entry['visible']) && (int)$entry['visible'] === 0) {
                continue;
            }

            $label = (string)($entry['label'] ?? '');
            $title = (string)($entry['title'] ?? '');
            $path = $this->buildPath($entry);

            if ($path !== '' && $this->matchesQuery($label, $title, $query)) {
                $results[] = [
                    'name' => $label ?: $title,
                    'path' => $path,
                ];
            }

            if (!empty($entry['pages']) && is_array($entry['pages'])) {
                $results = array_merge($results, $this->searchNavigation($entry['pages'], $query));
            }
        }

        return $results;
    }

    /**
     * @param array<mixed> $entry
     */
    protected function buildPath(array $entry): string
    {
        if (!empty($entry['uri'])) {
            return (string)$entry['uri'];
        }

        $bundle = $entry['bundle'] ?? '';
        $controller = $entry['controller'] ?? '';
        $action = $entry['action'] ?? '';

        if ($bundle === '' || $controller === '' || $action === '') {
            return '';
        }

        return sprintf('/%s/%s/%s', $bundle, $controller, $action);
    }

    protected function matchesQuery(string $label, string $title, string $query): bool
    {
        return str_contains(strtolower($label), $query) || str_contains(strtolower($title), $query);
    }
}
