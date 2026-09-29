<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The wiring between routes, controllers and pages.
 *
 * Three kinds of break got into this codebase and none of them showed up in a
 * unit test: a route declared against a controller method nobody wrote, a
 * component calling a route name that does not exist, and a page no
 * controller ever renders. Each one only surfaces when somebody opens the
 * screen, which is the worst time to find out.
 */
class RouteIntegrityTest extends TestCase
{
    private function base(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_every_route_points_at_a_method_that_exists(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('uses');

            if (!is_string($action) || !str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);

            if (!class_exists($controller)) {
                $missing[] = $route->getName() . ' → ' . $controller . ' (no such class)';

                continue;
            }

            if (!method_exists($controller, $method)) {
                $missing[] = $route->getName() . ' → ' . class_basename($controller) . '::' . $method . '()';
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Routes pointing at nothing:\n  " . implode("\n  ", $missing),
        );
    }

    public function test_every_route_name_a_component_calls_exists(): void
    {
        $known = [];
        foreach (Route::getRoutes() as $route) {
            if ($route->getName()) {
                $known[] = $route->getName();
            }
        }

        $missing = [];
        $directory = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->base() . '/resources/js')
        );

        foreach ($directory as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['vue', 'js'], true)) {
                continue;
            }

            preg_match_all("/\broute\(\s*'([a-z][a-z0-9._-]+)'/i", file_get_contents($file->getPathname()), $used);

            foreach (array_unique($used[1]) as $name) {
                if (!in_array($name, $known, true)) {
                    $missing[] = $file->getBasename() . ': ' . $name;
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Route names used by a component but never registered:\n  " . implode("\n  ", $missing),
        );
    }

    public function test_every_page_is_rendered_by_something(): void
    {
        $rendered = [];
        $imported = [];

        $php = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->base() . '/app')
        );

        foreach ($php as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                preg_match_all("/Inertia::render\(\s*'([^']+)'/", file_get_contents($file->getPathname()), $hits);
                $rendered = array_merge($rendered, $hits[1]);
            }
        }

        $pagesRoot = $this->base() . '/resources/js/Pages';
        $vue = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->base() . '/resources/js'));
        $pages = [];

        foreach ($vue as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }

            preg_match_all("#from\s+'@/Pages/([^']+)\.vue'#", file_get_contents($file->getPathname()), $hits);
            $imported = array_merge($imported, $hits[1]);

            $path = str_replace('\\', '/', $file->getPathname());
            if (str_starts_with($path, str_replace('\\', '/', $pagesRoot))) {
                $pages[] = trim(substr($path, strlen($pagesRoot), -4), '/');
            }
        }

        $orphans = array_values(array_diff($pages, $rendered, $imported));
        sort($orphans);

        $this->assertSame(
            [],
            $orphans,
            "Pages nothing can open:\n  " . implode("\n  ", $orphans),
        );
    }
}
