<?php

namespace Tests\Feature;

use Tests\TestCase;

class SharedHeroH1InstallerTest extends TestCase
{
    public function test_production_h1_installer_has_valid_php_syntax(): void
    {
        $script = base_path('scripts/fix-shared-hero-h1-v2.php');

        $output = [];
        $code = 1;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($script).' 2>&1', $output, $code);

        $this->assertSame(0, $code, implode("\n", $output));
    }

    public function test_installer_preserves_homepage_h1_and_has_backups_and_rollback(): void
    {
        $source = file_get_contents(base_path('scripts/fix-shared-hero-h1-v2.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("request()->getPathInfo() === '/'", $source);
        $this->assertStringContainsString('ff-seo-shared-headline', $source);
        $this->assertStringContainsString('PRZERWANO BEZ ZMIAN', $source);
        $this->assertStringContainsString('$backupRoot', $source);
        $this->assertStringContainsString('przywrocono', mb_strtolower($source));
        $this->assertStringContainsString('WYNIK: $passed/', $source);
    }
}
