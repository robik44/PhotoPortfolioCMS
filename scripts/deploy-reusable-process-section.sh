#!/usr/bin/env bash
# Install the reusable "Jak pracuję" palette element on the current SEOHOST builder
# without pulling the older version of the rest of the application.
set -euo pipefail
cd /home/srv51861/domains/foodfoto.pl/laravel
VIEW='resources/views/admin/pages/builder.blade.php'
test -s "$VIEW" || { echo "Nie znaleziono widoku buildera." >&2; exit 1; }
BACKUP="storage/app/process-reusable-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -p "$VIEW" "$BACKUP/builder.blade.php"
echo "Kopia bezpieczenstwa: $BACKUP/builder.blade.php"

php <<'PHP'
<?php
$view = 'resources/views/admin/pages/builder.blade.php';
$s = file_get_contents($view);
$fail = static function (string $message): never {
    fwrite(STDERR, "PRZERWANO: $message\nWidok buildera nie zostal zmodyfikowany.\n");
    exit(1);
};
if (!is_string($s)) $fail('nie udalo sie odczytac pliku.');
if (str_contains($s, 'FOODFOTO_REUSABLE_PROCESS_V1') && str_contains($s, '+ Jak pracuję — gotowa sekcja')) {
    echo "Funkcja byla juz zainstalowana. Niczego nie zmieniono.\n";
    exit(0);
}
if (str_contains($s, 'FOODFOTO_REUSABLE_PROCESS_V1')) {
    $fail('wykryto niepelna poprzednia instalacje.');
}

$presetRx = '~^[ \t]*<button\b[^>]*data-fve-preset=["\x27]process-collaboration["\x27][^>]*>.*?</button>[ \t]*\R?~ms';
$separatorRx = '~^[ \t]*<button\b[^>]*data-fve-add=["\x27]separator["\x27][^>]*>.*?</button>[ \t]*\R?~ms';
if (preg_match_all($presetRx, $s) !== 1) $fail('nie znaleziono jednego przycisku Proces wspolpracy.');
if (preg_match_all($separatorRx, $s) !== 1) $fail('nie znaleziono przycisku Separator.');
if (substr_count($s, '<x-app-layout>') !== 1) $fail('nieoczekiwany uklad widoku.');
$jsRx = '~\bconst\s+isHomeBuilder\s*=\s*@json\(\$isHomeBuilder\s*\?\?\s*false\);~';
if (preg_match_all($jsRx, $s) !== 1) $fail('nie znaleziono zmiennej buildera.');
$begin = strpos($s, "preset === 'process-collaboration'");
if ($begin === false) $begin = strpos($s, 'preset === "process-collaboration"');
if ($begin === false) $fail('brak mechanizmu dodawania sekcji procesu.');
$end = strpos($s, "} else if (preset ===", $begin);
if ($end === false) $fail('nie odnaleziono konca mechanizmu dodawania procesu.');
$piece = substr($s, $begin, $end - $begin);
$initializer = 'window.FoodfotoProcess.initialize(item);';
if (substr_count($piece, $initializer) !== 1) $fail('mechanizm inicjalizacji procesu zostal zmieniony.');

$s = preg_replace($presetRx, '', $s, 1);
$button = <<<'BLADE'
            <button type="button" class="fve-tool" data-fve-preset="process-collaboration">
                + Jak pracuję — gotowa sekcja
            </button>

BLADE;
$s = preg_replace_callback($separatorRx, static fn ($m) => $m[0] . "\n" . $button, $s, 1);

$bladeCode = <<<'BLADE'
    {{-- FOODFOTO_REUSABLE_PROCESS_V1: copy saved homepage content when a new process element is inserted. --}}
    @php
        $processHomeSections = \App\Models\PageBuilder::query()
            ->whereNull('page_id')
            ->where('type', 'home')
            ->first()?->content['sections'] ?? [];
        $reusableProcessTemplate = collect($processHomeSections)->firstWhere('type', 'process_steps')
            ?? collect($builder?->content['sections'] ?? [])->firstWhere('type', 'process_steps');
    @endphp

BLADE;
$s = str_replace('<x-app-layout>', '<x-app-layout>' . "\n" . $bladeCode, $s);
$s = preg_replace_callback($jsRx, static fn ($m) => $m[0] . "\n    const reusableProcessTemplate = @json(\$reusableProcessTemplate);", $s, 1);

$begin = strpos($s, "preset === 'process-collaboration'");
if ($begin === false) $begin = strpos($s, 'preset === "process-collaboration"');
$end = strpos($s, "} else if (preset ===", $begin);
$piece = substr($s, $begin, $end - $begin);
$extra = <<<'JAVASCRIPT'
window.FoodfotoProcess.initialize(item);
            // Copy only the reusable content/design. The new element retains a new ID
            // and independent page coordinates and editor flow state.
            if (reusableProcessTemplate && reusableProcessTemplate.type === 'process_steps') {
                for (const [key, value] of Object.entries(reusableProcessTemplate)) {
                    if (key.startsWith('process_')) {
                        item[key] = JSON.parse(JSON.stringify(value));
                    }
                }
            }
JAVASCRIPT;
$piece = str_replace($initializer, $extra, $piece);
$s = substr_replace($s, $piece, $begin, $end - $begin);

if (substr_count($s, 'data-fve-preset="process-collaboration"') !== 1 ||
    substr_count($s, 'const reusableProcessTemplate = @json(') !== 1 ||
    !str_contains($s, 'FOODFOTO_REUSABLE_PROCESS_V1')) {
    $fail('kontrola spojnoscI zmian nie powiodla sie.');
}
$tmp = $view . '.new-' . getmypid();
if (file_put_contents($tmp, $s, LOCK_EX) === false || !rename($tmp, $view)) {
    $fail('nie mozna zapisac nowego widoku.');
}
echo "Dodano: Elementy -> + Jak pracuję — gotowa sekcja.\n";
echo "Nowe sekcje moga kopiowac zapisane ustawienia sekcji ze strony glownej.\n";
PHP

# Equivalent to clearing compiled Blade views; no artisan invocation
# because this SEOHOST account's CLI PHP may be older than vendor requirements.
if [[ -d storage/framework/views ]]; then
    find storage/framework/views -maxdepth 1 -type f -name '*.php' -delete
fi

echo '--- Kontrola instalacji ---'
grep -n 'Jak pracuję — gotowa sekcja' "$VIEW"
grep -n 'FOODFOTO_REUSABLE_PROCESS_V1' "$VIEW"
echo 'GOTOWE. Odswiez builder (Cmd+Shift+R) i sprawdz dodanie nowej sekcji.'
