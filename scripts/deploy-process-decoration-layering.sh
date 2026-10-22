#!/usr/bin/env bash
# Targeted, reversible SEOHOST patch. Never replace the server's newer CSS.
set -euo pipefail
cd /home/srv51861/domains/foodfoto.pl/laravel

CSS='public/css/foodfoto-process.css'
if [[ ! -f "$CSS" ]] || ! grep -Fq '.ff-process' "$CSS"; then
    echo 'PRZERWANO: nie znaleziono aktualnego arkusza sekcji Jak pracuje.' >&2
    exit 1
fi
if grep -Fq 'FOODFOTO_PROCESS_DECORATION_STACK_V1' "$CSS"; then
    echo 'Poprawka warstw jest juz zainstalowana. Nie zmieniono pliku.'
    exit 0
fi

BACKUP="storage/app/process-layer-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -p "$CSS" "$BACKUP/foodfoto-process.css"

cat >> "$CSS" <<'CSSPATCH'

/* FOODFOTO_PROCESS_DECORATION_STACK_V1
 * Keep headings, descriptions and icons in front of decorative images,
 * even while a decoration is moved, scaled or rotated.
 * The isolated section prevents these high local z-index values from
 * leaking above the CMS toolbar and other page sections.
 */
.ff-process {
    position: relative;
    isolation: isolate;
}
.ff-process > .ff-process-intro,
.ff-process > .ff-process-steps {
    position: relative;
    z-index: 2147483000 !important;
}
.ff-process .ff-process-step,
.ff-process [data-process-part],
.ff-process .ff-process-bubble {
    /* Keep each element's existing absolute/free-positioning coordinates. */
    z-index: 2147483000 !important;
}
CSSPATCH

echo 'GOTOWE: ustawiono teksty i ikony nad dekoracja w sekcji Jak pracuje.'
echo "Kopia oryginalnego CSS: $BACKUP/foodfoto-process.css"
echo 'Nie zmieniono polozenia, wymiarow ani ustawien zdjec.'
echo 'Odswiez edytor (Cmd+Shift+R), powieksz zdjecie i sprawdz warstwy.'
