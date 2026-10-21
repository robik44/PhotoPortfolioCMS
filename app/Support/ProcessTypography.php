<?php

namespace App\Support;

/**
 * Adapts editable texts inside FoodFoto's three-step section to the same
 * typography engine as ordinary page-builder text fields.
 *
 * The legacy process_design values remain fallback defaults for old sections.
 * Independently saved Desktop/Tablet/Mobile overrides take precedence.
 */
class ProcessTypography
{
    public const PARTS = [
        'kicker' => 'text',
        'title' => 'heading',
        'intro' => 'text',
        'step1_title' => 'heading',
        'step1_description' => 'text',
        'step2_title' => 'heading',
        'step2_description' => 'text',
        'step3_title' => 'heading',
        'step3_description' => 'text',
    ];

    public static function parts(array $process): array
    {
        $design = is_array($process['process_design'] ?? null) ? $process['process_design'] : [];
        $stored = is_array($process['process_typography'] ?? null) ? $process['process_typography'] : [];
        $parts = [];

        foreach (self::PARTS as $key => $type) {
            $heading = $key === 'title' || str_ends_with($key, '_title');
            $kicker = $key === 'kicker';
            $title = $key === 'title';
            $intro = $key === 'intro';
            $description = !$heading && !$kicker && !$intro;
            $part = is_array($stored[$key] ?? null) ? $stored[$key] : [];

            $size = $kicker ? 11 : ($title ? ($design['title_size'] ?? 58)
                : ($intro ? ($design['lead_size'] ?? 18)
                    : ($heading ? ($design['step_title_size'] ?? 23) : ($design['step_description_size'] ?? 16))));
            $font = $heading ? ($design['heading_font'] ?? 'Georgia') : ($design['body_font'] ?? 'Arial');
            $color = $kicker ? ($design['accent'] ?? '#a7b593')
                : ($heading ? ($design['text'] ?? '#24241f') : ($design['muted'] ?? '#64675e'));

            $parts[$key] = [
                'type' => $type,
                'style' => [
                    'font_family' => $font,
                    'font_size' => $size,
                    'font_weight' => $kicker ? 600 : ($title ? ($design['title_weight'] ?? 400) : 400),
                    'font_style' => $title && ($design['heading_italic'] ?? true) !== false ? 'italic' : 'normal',
                    'text_transform' => $kicker ? 'uppercase' : 'none',
                    'text_align' => 'left',
                    'line_height' => $kicker ? 1.5 : ($title ? 1.08 : ($intro ? 1.62 : ($heading ? 1.18 : 1.55))),
                    'letter_spacing' => $kicker ? .19 : ($title ? -.025 : 0),
                    'letter_spacing_unit' => $kicker || $title ? 'em' : 'px',
                    'color' => $color,
                    'color_alpha' => 100,
                    'opacity' => 100,
                    'margin_top' => 0,
                    'margin_bottom' => $kicker ? 18 : ($heading && !$title ? 9 : 0),
                ],
                'typography' => $part['typography'] ?? [],
            ];
        }

        return $parts;
    }
}
