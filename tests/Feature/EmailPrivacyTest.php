<?php

namespace Tests\Feature;

use App\Services\SiteFontLibrary;
use Tests\TestCase;

class EmailPrivacyTest extends TestCase
{
    public function test_homepage_builder_renders_email_button_without_plain_address_in_html(): void
    {
        $address = 'magda@foodfoto.pl';
        $label = $address.'  →';
        $html = view('components.public-builder-canvas', [
            'sections' => [[
                'id' => 'contact-email-test',
                'type' => 'button',
                'content' => $label,
                'button_link' => 'mailto:'.$address,
                'position_x' => 60,
                'position_y' => 75,
                'element_width' => 22,
            ]],
            'settings' => [],
            'fontCatalog' => app(SiteFontLibrary::class)->catalog(),
        ])->render();

        $this->assertStringContainsString('data-ff-email="'.base64_encode($address).'"', $html);
        $this->assertStringContainsString('data-ff-email-label="'.base64_encode($label).'"', $html);
        $this->assertStringContainsString('Napisz e-mail', $html);
        $this->assertStringNotContainsString($address, $html);
        $this->assertStringNotContainsString('href="mailto:', $html);
    }

    public function test_plain_email_text_block_remains_clickable_without_exposing_plaintext_in_source(): void
    {
        $address = 'magda@foodfoto.pl';
        $html = view('components.public-builder-canvas', [
            'sections' => [[
                'id' => 'contact-text-test',
                'type' => 'text',
                'content' => $address,
                'position_x' => 50,
                'position_y' => 80,
                'element_width' => 25,
            ]],
            'settings' => [],
            'fontCatalog' => app(SiteFontLibrary::class)->catalog(),
        ])->render();

        $this->assertStringContainsString('data-ff-email="'.base64_encode($address).'"', $html);
        $this->assertStringNotContainsString($address, $html);
        $this->assertStringNotContainsString('href="mailto:', $html);
    }
}
