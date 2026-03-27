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
        return 'Use to get Spryker application navigation details with all available routes and pages. Returns all Backoffice navigation entries with their paths. Use this tool when the user asks where to find something, how to navigate, or which menu item to use.';
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
        $cachePath = $this->getConfig()->getBackofficeNavigationCachePath();

        if (!file_exists($cachePath)) {
            return json_encode(['error' => 'Navigation not found.']);
        }

        $navigation = json_decode((string)file_get_contents($cachePath), true);

        if (!is_array($navigation)) {
            return json_encode(['error' => 'Navigation could not be parsed.']);
        }

        return json_encode($this->collectNavigation($navigation));
    }

    /**
     * @param array<mixed> $navigation
     *
     * @return array<array<string, string>>
     */
    protected function collectNavigation(array $navigation): array
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

            if ($path !== '') {
                $results[] = [
                    'name' => $label ?: $title,
                    'path' => $path,
                ];
            }

            if (!empty($entry['pages']) && is_array($entry['pages'])) {
                $results = array_merge($results, $this->collectNavigation($entry['pages']));
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
}
