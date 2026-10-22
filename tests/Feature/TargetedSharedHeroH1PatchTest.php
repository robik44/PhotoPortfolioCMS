<?php

namespace Tests\Feature;

use Tests\TestCase;

class TargetedSharedHeroH1PatchTest extends TestCase
{
    public function test_installer_is_parseable_php(): void
    {
        $script = base_path('scripts/fix-draft-shared-hero-h1-production.php');
        $output = [];
        $status = 1;

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($script).' 2>&1', $output, $status);

        $this->assertSame(0, $status, implode("\n", $output));
    }

    public function test_installer_targets_single_production_file_and_preserves_editor_preview(): void
    {
        $source = file_get_contents(base_path('scripts/fix-draft-shared-hero-h1-production.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('resources/views/admin/components/draft-shared-header.blade.php', $source);
        $this->assertStringContainsString("request()->getPathInfo() !== '/'", $source);
        $this->assertStringContainsString('ff-seo-hero-title', $source);
        $this->assertStringContainsString('if($cssCount!==3)', $source);
        $this->assertStringContainsString("copy(\$backup,\$target)", $source);
        $this->assertStringContainsString('SUKCES: 15/15', $source);
        $this->assertStringNotContainsString('php artisan migrate', $source);
    }
}
