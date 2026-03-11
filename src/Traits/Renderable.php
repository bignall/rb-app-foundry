<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Traits;

/**
 * Trait for template/view rendering.
 *
 * @package RBCS\AppFoundry\Traits
 */
trait Renderable
{
    /**
     * Render a PHP template file with data.
     *
     * @param string              $template Relative path to template file.
     * @param array<string,mixed> $data     Data to extract into template scope.
     * @param bool                $echo     Whether to echo or return output.
     * @return string|void
     */
    protected function render(string $template, array $data = [], bool $echo = true): string|null
    {
        $file = APPFOUNDRY_PATH . 'templates/' . ltrim($template, '/');

        if (!file_exists($file)) {
            return $echo ? null : '';
        }

        if (!$echo) {
            ob_start();
        }

        // Extract data into local scope for the template.
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        extract($data, EXTR_SKIP);
        include $file;

        if (!$echo) {
            return ob_get_clean() ?: '';
        }

        return null;
    }
}
