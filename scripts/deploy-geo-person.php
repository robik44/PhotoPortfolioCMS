<?php
/**
 * FoodFoto GEO — describe Magda Gugała as the subject of the "O mnie"
 * page and link the Person record to the existing Organization schema.
 * Scope: resources/views/components/seo-meta.blade.php only.
 * No CSS, visual builder, routes, redirects, user content or DB changes.
 */
declare(strict_types=1);

$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/components/seo-meta.blade.php';
function geoStop(string $why): never {
    fwrite(STDERR, "STOP BEZ ZMIAN: $why\n");
    exit(2);
}
$before=@file_get_contents($file);
if (!is_string($before) || strlen($before)<1800 || !is_writable($file)) {
    geoStop('Nie mozna bezpiecznie odczytac seo-meta.blade.php.');
}
$marker='FF_GEO_PERSON_MAGDA_20261118';
if (str_contains($before, $marker)) {
    echo "JUZ ZAINSTALOWANE — nie wprowadzono kolejnych zmian.\n";
    exit(0);
}
$needleA=<<<'BLADE'
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => $seo['site_name'],
        'url' => url('/'),
    ],
BLADE;
$replaceA=<<<'BLADE'
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => $seo['site_name'],
        'url' => url('/'),
    ],
    // Link the AboutPage to the Person described below.
    'mainEntity' => ($seo['schema_type'] ?? '') === 'AboutPage'
        ? ['@id' => url('/o-mnie').'#magda-gugala'] : null,
BLADE;
$needleB=<<<'BLADE'
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
BLADE;
$replaceB=<<<'BLADE'
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

{{-- FF_GEO_PERSON_MAGDA_20261118: factual Person schema only on the About page. --}}
@if(($seo['schema_type'] ?? '') === 'AboutPage')
@php
    $person = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        '@id' => url('/o-mnie').'#magda-gugala',
        'name' => 'Magda Gugała',
        'url' => url('/o-mnie'),
        'mainEntityOfPage' => url('/o-mnie'),
        'jobTitle' => 'Stylistka żywności i fotografka kulinarna',
        'description' => 'Zajmuje się stylizacją żywności i przygotowaniem potraw do profesjonalnych sesji zdjęciowych. Współpracuje z fotografami i zespołami realizującymi projekty reklamowe.',
        'knowsAbout' => [
            'stylizacja żywności',
            'food styling',
            'stylizacja potraw',
            'fotografia kulinarna',
            'zdjęcia na opakowania produktów spożywczych',
        ],
        'affiliation' => ['@id' => url('/').'#organization'],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
BLADE;

foreach ([$needleA,$needleB] as $i=>$needle) {
    if (substr_count($before,$needle)!==1) {
        geoStop('Nierozpoznana wersja szablonu (punkt '.($i+1).').');
    }
}
$after=str_replace($needleA,$replaceA,$before);
$after=str_replace($needleB,$replaceB,$after);
if ($after===$before || !str_contains($after,$marker)) geoStop('Nie zbudowano poprawnej zmiany.');

$backupDir=$root.'/storage/app/backups';
if (!is_dir($backupDir) && !mkdir($backupDir,0700,true)) geoStop('Nie udalo sie utworzyc katalogu kopii.');
$backup=$backupDir.'/seo-meta-before-geo-person-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if (file_put_contents($backup,$before,LOCK_EX)===false) geoStop('Nie zapisano kopii.');
$temp=$file.'.geo-'.getmypid();
if (file_put_contents($temp,$after,LOCK_EX)===false) geoStop('Nie zapisano pliku tymczasowego.');
@chmod($temp,fileperms($file)&0777);
if (!rename($temp,$file)) { @unlink($temp); geoStop('Nie podmieniono pliku szablonu.'); }

$output=[];$status=1;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$output,$status);
if ($status!==0) {
    @copy($backup,$file);
    exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');
    geoStop('Blad kompilacji Blade; przywrocono kopie: '.implode(' | ',$output));
}
echo "SUKCES: dodano Person JSON-LD tylko na /o-mnie i powiazano z organizacja FoodFoto.\n";
echo "SUKCES: AboutPage wskazuje Person jako mainEntity.\n";
echo "KOMPILACJA LARAVEL: OK.\n";
echo "BEZ ZMIAN: wyglad, builder, naglowek, biala kapsula, skalowanie 1400 px, tresci CMS, baza danych.\n";
echo "KOPIA: ".$backup."\n";
echo "Nastepnie sprawdz publiczne HTML /o-mnie (Person) i /kontakt (bez Person).\n";
