<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Rules\HeaderFontFile;
use App\Support\HeaderFonts;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiteFontLibrary
{
    public const TEXT_BLOCKS = ['heading', 'text', 'button', 'section', 'gallery'];
    public const DEFAULTS = ['site_body_font_family' => 'Arial', 'site_heading_font_family' => 'Arial'];

    public function catalog(): array
    {
        return self::fromSettings(SiteSetting::whereIn('key', [HeaderFonts::SETTING_KEY, ...array_keys(self::DEFAULTS)])
            ->pluck('value', 'key')->all());
    }

    public static function fromSettings(array $settings): array
    {
        $fonts = HeaderFonts::custom($settings);
        $families = HeaderFonts::families($fonts);
        $choices = [];
        foreach ($families as $id => $css) {
            $choices[] = ['value' => $id, 'label' => isset($fonts[$id]) ? $fonts[$id]['label'] . ' (własna)' : $id, 'css' => $css];
        }
        $defaults = self::DEFAULTS;
        foreach ($defaults as $key => $fallback) {
            $id = $settings[$key] ?? null;
            $defaults[$key] = is_string($id) && isset($families[$id]) ? $id : $fallback;
        }

        return compact('fonts', 'families', 'choices', 'defaults');
    }

    public static function css(mixed $id, array $catalog, string $fallback = 'Arial'): string
    {
        return is_string($id) && isset($catalog['families'][$id])
            ? $catalog['families'][$id]
            : ($catalog['families'][$fallback] ?? HeaderFonts::SYSTEM['Arial']);
    }

    public static function uploadRules(bool $required = true): array
    {
        return ['bail', $required ? 'required' : 'nullable', 'file', 'max:5120', new HeaderFontFile];
    }

    /** Save one shared file; optionally update its consumer in the same DB transaction. */
    public function upload(UploadedFile $file, ?Closure $afterSave = null): string
    {
        $id = 'hf_' . str_replace('-', '', (string) Str::uuid());
        $path = $file->storeAs('fonts', $id . '.' . strtolower($file->getClientOriginalExtension()), 'public');
        if (!$path) {
            throw ValidationException::withMessages(['font_file' => 'Nie udało się zapisać czcionki. Spróbuj ponownie.']);
        }
        $label = preg_replace('/[^\p{L}\p{N} ._-]/u', '', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        try {
            DB::transaction(function () use ($id, $path, $label, $afterSave) {
                $raw = SiteSetting::where('key', HeaderFonts::SETTING_KEY)->lockForUpdate()->value('value');
                $fonts = json_decode($raw ?? '{}', true);
                $fonts = is_array($fonts) ? $fonts : [];
                $fonts[$id] = ['path' => $path, 'label' => mb_substr($label ?: 'Własna czcionka', 0, 100)];
                SiteSetting::updateOrCreate(['key' => HeaderFonts::SETTING_KEY], ['value' => json_encode($fonts, JSON_THROW_ON_ERROR)]);
                if ($afterSave) {
                    $afterSave($id);
                }
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
        }

        return $id;
    }

    /** Only check new font identifiers; keep every other JSON property intact. */
    public function validateContent(array $content): void
    {
        $catalog = $this->catalog();
        foreach (($content['sections'] ?? []) as $index => $element) {
            if (!is_array($element) || !in_array($element['type'] ?? '', self::TEXT_BLOCKS, true)) {
                continue;
            }
            $style = $element['style'] ?? [];
            if (is_array($style) && array_key_exists('font_family', $style)) {
                $id = $style['font_family'];
                if (!is_string($id) || !isset($catalog['families'][$id])) {
                    throw ValidationException::withMessages([
                        "content.sections.$index.style.font_family" => 'Wybierz rodzaj czcionki z biblioteki.',
                    ]);
                }
            }
        }
    }
}
