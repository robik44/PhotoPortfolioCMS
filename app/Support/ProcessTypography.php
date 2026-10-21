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
        'step1_number' => 'text',
        'step1_title' => 'heading',
        'step1_description' => 'text',
        'step2_number' => 'text',
        'step2_title' => 'heading',
        'step2_description' => 'text',
        'step3_number' => 'text',
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
            $number = str_ends_with($key, '_number');
            $title = $key === 'title';
            $intro = $key === 'intro';
            $description = !$heading && !$kicker && !$intro;
            $part = is_array($stored[$key] ?? null) ? $stored[$key] : [];

            $size = $number ? ($design['number_size'] ?? 38) : ($kicker ? 11 : ($title ? ($design['title_size'] ?? 58)
                : ($intro ? ($design['lead_size'] ?? 18)
                    : ($heading ? ($design['step_title_size'] ?? 23) : ($design['step_description_size'] ?? 16)))));
            $font = $number ? 'Georgia' : ($heading ? ($design['heading_font'] ?? 'Georgia') : ($design['body_font'] ?? 'Arial'));
            $color = ($kicker || $number) ? ($design['accent'] ?? '#a7b593')
                : ($heading ? ($design['text'] ?? '#24241f') : ($design['muted'] ?? '#64675e'));

            $parts[$key] = [
                'type' => $type,
                'style' => [
                    'font_family' => $font,
                    'font_size' => $size,
                    'font_weight' => $kicker ? 600 : ($title ? ($design['title_weight'] ?? 400) : 400),
                    'font_style' => $number || ($title && ($design['heading_italic'] ?? true) !== false) ? 'italic' : 'normal',
                    'text_transform' => $kicker ? 'uppercase' : 'none',
                    'text_align' => 'left',
                    'line_height' => $number ? 1 : ($kicker ? 1.5 : ($title ? 1.08 : ($intro ? 1.62 : ($heading ? 1.18 : 1.55)))),
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

    /**
     * Each process item has an independent saved offset. Only numbers
     * accepted, same units and clamping as the JS visual editor.
     */
    public static function positionStyle(array $process, string $key): string
    {
        $position = $process['process_positions'][$key] ?? [];
        if (!is_array($position)) $position = [];
        $x = is_numeric($position['x'] ?? null) ? max(-1800, min(1800, (float)$position['x'])) : 0;
        $y = is_numeric($position['y'] ?? null) ? max(-1000, min(1000, (float)$position['y'])) : 0;
        return '--ff-part-x:'.$x.'px;--ff-part-y:'.$y.'px;';
    }
}
