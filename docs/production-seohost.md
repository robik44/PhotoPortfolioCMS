# Wdrożenie produkcyjne — SeoHost

## Środowisko
Na pierwsze wdrożenie użyj PHP 8.4. Projekt wymaga PHP 8.3 lub nowszego, a CI testuje PHP 8.4.

Wymagane rozszerzenia:
- mbstring
- fileinfo
- PDO z wybranym sterownikiem bazy
- GD z obsługą WebP

Katalog publiczny domeny musi wskazywać na katalog `public/` aplikacji Laravel.

## Ustawienia produkcyjne
W pliku `.env` na serwerze ustaw:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://foodfoto.pl`
- `LOG_CHANNEL=daily`
- `LOG_LEVEL=warning`
- `SESSION_DRIVER=file`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_SAME_SITE=lax`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`

Wygeneruj osobny produkcyjny `APP_KEY`. Nie umieszczaj `.env` w Git.

## Poczta
Adres odbiorcy wiadomości resetującej hasło jest adresem konta administratora i można go zmienić w CMS.

SMTP jest konfigurowany na serwerze w `.env`. Dla skrzynki utrzymywanej w SeoHost należy użyć danych właściwych dla konkretnego konta hostingowego i szyfrowanego SMTP.

## Pliki potrzebne na produkcji
Kod aplikacji pochodzi z GitHub. Produkcja potrzebuje także zależności Composer oraz zbudowanych plików Vite w `public/build/`.

Dane użytkownika nie powinny zależeć od repozytorium. Trzeba osobno zachować:
- bazę danych
- `storage/app/public/` ze zdjęciami i fontami
- produkcyjny `.env`

## Bezpieczna wymiana starej strony
Nie kasuj starej witryny przed wykonaniem kopii.

1. Pobierz pełną kopię obecnego katalogu WWW.
2. Wyeksportuj wszystkie stare bazy danych.
3. Zachowaj wszystkie dane newslettera, formularzy i list adresowych, nawet jeśli nie będą migrowane.
4. Nie usuwaj katalogów pocztowych ani całego katalogu domowego konta hostingowego.
5. Nową aplikację wgraj do osobnego katalogu.
6. Przetestuj ją na adresie technicznym lub subdomenie.
7. Pozostaw tryb Under construction do zakończenia testów.
8. Dopiero po odbiorze przełącz katalog domeny na nowe `public/`.
9. Starą stronę pozostaw w archiwum przez co najmniej 30 dni.

## Po przełączeniu
- sprawdź HTTPS
- sprawdź /up
- sprawdź /robots.txt
- sprawdź /sitemap.xml
- sprawdź logowanie i reset hasła
- sprawdź upload zdjęć
- sprawdź wszystkie galerie i wersję mobilną
- skonfiguruj przekierowania 301 ze starych adresów
- zgłoś sitemapę w Google Search Console

SeoHost ma własne kopie zapasowe, ale dodatkowo należy utrzymywać niezależną kopię bazy i `storage/app/public/` poza serwerem produkcyjnym.
