<?php
/**
 * FoodFoto — click-to-call for standalone Polish phone numbers in
 * PageBuilder text and button elements (O mnie, Kontakt, homepage, etc.).
 * Changes only one Blade component. No database, styles, canvas, menu,
 * JavaScript, or 1400px layout edits.
 */
declare(strict_types=1);

$root = '/home/srv51861/domains/foodfoto.pl/laravel';
$file = $root.'/resources/views/components/public-builder-canvas.blade.php';

function ffStop(string $why): never {
    fwrite(STDERR, "STOP BEZ ZMIAN: ".$why."\n");
    exit(2);
}

$original = @file_get_contents($file);
if (!is_string($original) || strlen($original) < 17000 || !is_writable($file)) {
    ffStop('Nie można odczytać szablonu strony lub brak prawa zapisu.');
}
$marker = 'A standalone Polish telephone number is clickable';
if (str_contains($original, $marker)) {
    echo "JUZ ZAINSTALOWANE: numery telefonu sa obslugiwane. Nic nie zmieniono.\n";
    exit(0);
}

$edits = [
    [
        <<<'OLD'
            $content = $element['content'] ?? '';
OLD,
        <<<'NEW'
            $content = $element['content'] ?? '';
            // A standalone Polish telephone number is clickable even if no link
            // has been manually configured in the CMS. Other content is unchanged.
            $phoneLabel = trim((string) $content);
            $phoneDigits = preg_replace('/\D+/', '', $phoneLabel);
            $autoPhoneHref = preg_match('/^(?:\+48[\s().-]*)?(?:\d[\s().-]*){9}$/u', $phoneLabel) === 1
                ? 'tel:+48'.substr($phoneDigits, -9)
                : null;
NEW,
    ],
    [
        <<<'OLD'
                    $textHref = $textLink ?: ($exactEmail ? 'mailto:'.$exactEmail : null);
OLD,
        <<<'NEW'
                    $textHref = $autoPhoneHref ?: ($textLink ?: ($exactEmail ? 'mailto:'.$exactEmail : null));
NEW,
    ],
    [
        <<<'OLD'
@elseif($textHref)<a href="{{ $textHref }}" style="color:inherit;text-decoration:underline;text-underline-offset:3px;">
OLD,
        <<<'NEW'
@elseif($textHref)<a href="{{ $textHref }}" style="color:inherit;text-decoration:{{ $autoPhoneHref ? 'none' : 'underline' }};text-underline-offset:3px;">
NEW,
    ],
    [
        <<<'OLD'
                    $buttonHref = \App\Support\BuilderButton::href($element['button_link'] ?? null);
OLD,
        <<<'NEW'
                    $buttonHref = $autoPhoneHref ?: \App\Support\BuilderButton::href($element['button_link'] ?? null);
NEW,
    ],
    [
        <<<'OLD'
                    @if($buttonHref && !$protectedButtonEmail && ($element['button_new_tab'] ?? false)) target="_blank" rel="noopener noreferrer" @endif
OLD,
        <<<'NEW'
                    @if($buttonHref && !$protectedButtonEmail && !str_starts_with($buttonHref, 'tel:') && ($element['button_new_tab'] ?? false)) target="_blank" rel="noopener noreferrer" @endif
NEW,
    ],
];

$changed = $original;
foreach ($edits as $i => [$before, $after]) {
    if (substr_count($changed, $before) !== 1) {
        ffStop('Nieoczekiwany kod szablonu, kontrola '.($i + 1).'/'.count($edits).'.');
    }
    $changed = str_replace($before, $after, $changed);
}
if (!str_contains($changed, $marker)) {
    ffStop('Weryfikacja zmian nie przeszła.');
}

$backupDir = $root.'/storage/app/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
    ffStop('Nie można utworzyć katalogu kopii.');
}
$backup = $backupDir.'/public-builder-before-phone-links-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if (file_put_contents($backup, $original, LOCK_EX) === false) {
    ffStop('Nie udało się utworzyć kopii bezpieczeństwa.');
}
$temp = $file.'.phone-'.getmypid();
if (file_put_contents($temp, $changed, LOCK_EX) === false) {
    ffStop('Nie udało się zapisać pliku tymczasowego.');
}
@chmod($temp, fileperms($file) & 0777);
if (!rename($temp, $file)) {
    @unlink($temp);
    ffStop('Nie udało się podmienić szablonu.');
}

$output = [];
$status = 1;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1', $output, $status);
if ($status !== 0) {
    @copy($backup, $file);
    exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');
    ffStop('Błąd kompilacji Blade — przywrócono poprzedni plik. '.implode(' | ', $output));
}

echo "SUKCES: sam numer telefonu w tekstach i przyciskach PageBuildera jest linkiem tel:+48...\n";
echo "BEZ ZMIAN: treści CMS, wygląd przycisków, nagłówek, menu, fotografie, układ 1400 px.\n";
echo "KOMPILACJA LARAVEL: OK.\n";
echo "KOPIA: ".$backup."\n";

// Read-only post-install visibility check of source elements for the target pages.
try {
    chdir($root);
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    foreach (['o-mnie', 'kontakt'] as $slug) {
        $page = \App\Models\Page::query()->where('slug', $slug)->first();
        $content = $page?->builder?->content ?? [];
        $sections = is_array($content) ? ($content['sections'] ?? []) : [];
        $found = [];
        foreach ($sections as $element) {
            if (!is_array($element) || !in_array($element['type'] ?? '', ['text','button'], true)) continue;
            $text = trim((string) ($element['content'] ?? ''));
            if (preg_match('/^(?:\+48[\s().-]*)?(?:\d[\s().-]*){9}$/u', $text) === 1) {
                $digits = preg_replace('/\D+/', '', $text);
                $found[] = 'tel:+48'.substr($digits, -9);
            }
        }
        echo "STRONA $slug: rozpoznano ".count($found)." numer(ów).".($found ? " Link: ".implode(', ',array_unique($found)) : '')."\n";
    }
} catch (\Throwable $e) {
    echo "INFO: Nie udało się sprawdzić zawartości stron w bazie; sama poprawka szablonu została zapisana.\n";
}

echo "TEST: odśwież /o-mnie i /kontakt i dotknij numeru na telefonie.\n";
