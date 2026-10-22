<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeHeadingHierarchyInstallerTest extends TestCase
{
    public function test_production_installer_passes_php_lint(): void
    {
        $script = base_path('scripts/fix-home-heading-hierarchy-production.php');
        $lines = [];
        $exitCode = 1;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($script).' 2>&1', $lines, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $lines));
    }
}
