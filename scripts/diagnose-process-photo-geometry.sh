#!/usr/bin/env bash
# Read-only diagnostics for process decorative photo geometry parity.
set -uo pipefail
cd /home/srv51861/domains/foodfoto.pl/laravel || exit 1
echo '========== JS: dekoracja i geometria w builderze =========='
grep -n -A36 -B9 -E 'const decoration =|const decoration=|function movable\(|const movable\s*=' public/js/foodfoto-process.js | head -135
echo
echo '========== PHP: zapis pozycji w stronie publicznej =========='
if [[ -f app/Support/ProcessTypography.php ]]; then
  grep -n -A58 -B9 -E 'function positionStyle|function geometryStyle' app/Support/ProcessTypography.php | head -90
else
  echo 'Brak app/Support/ProcessTypography.php'
fi
echo
echo '========== BLADE: obrazek w widoku publicznym =========='
grep -n -A17 -B7 'if($decorativePhoto)' resources/views/components/builder-process-steps.blade.php | head -35
echo
echo '========== CSS: pozycja i rozmiar dekoracji =========='
grep -n -A16 -B5 -E '^\s*\.ff-process(-decorative-photo| \.ff-process-decorative-photo| img\.ff-process-decorative-photo)|data-process-move="decorative_photo"' public/css/foodfoto-process.css | tail -170
echo
echo '========== PUBLICZNY HTML: rzeczywiste atrybuty obrazka =========='
if command -v curl >/dev/null 2>&1 && command -v php >/dev/null 2>&1; then
  curl -fsSL --connect-timeout 6 --max-time 18 "https://www.foodfoto.pl/?geometry-check=$(date +%s)" | php -r '
    $html=stream_get_contents(STDIN);
    if (preg_match("~<img\b[^>]*ff-process-decorative-photo[^>]*>~is", $html, $m)) {
      $tag = preg_replace("/\s+/u", " ", $m[0]);
      // This is already public image markup; mask its URL to keep output concise.
      $tag = preg_replace("~src=\"[^\"]*\"~", "src=\"[PUBLICZNY_URL_ZDJECIA]\"", $tag);
      echo substr($tag, 0, 1500), PHP_EOL;
    } else {
      echo "Nie znaleziono IMG z klasa ff-process-decorative-photo w publicznym HTML.", PHP_EOL;
    }
  '
else echo 'Brakuje curl lub PHP CLI'; fi
echo
echo 'KONIEC: to polecenie nie zmienilo zadnego pliku.'
