# Torrent Site

Aplikacja webowa do pobierania torrentów z wykorzystaniem Transmission.

## Funkcje

- Pobieranie torrentów z plików .torrent lub linków magnet
- Automatyczne pakowanie pobranych plików do archiwów ZIP
- Automatyczne usuwanie starych archiwów
- Śledzenie postępu pobierania
- Identyfikacja użytkowników przy użyciu UUID

## Wymagania

- PHP 7.4 lub nowszy
- Rozszerzenia PHP: curl, json, zip, openssl, mbstring
- Transmission daemon z dostępem do RPC
- Serwer WWW (Apache/Nginx)
- Dostęp do cron

## Instalacja

### Automatyczna instalacja

1. Pobierz kod aplikacji na serwer:
   ```bash
   git clone https://github.com/username/torrent-site.git
   cd torrent-site
   ```

2. Uruchom skrypt instalacyjny:
   ```bash
   php install.php
   ```
   
   Możesz również uruchomić instalator przez przeglądarkę, wchodząc na adres:
   ```
   http://twoja-domena.pl/install.php
   ```

3. Postępuj zgodnie z instrukcjami wyświetlanymi przez instalator.

### Ręczna instalacja

1. Sklonuj repozytorium lub pobierz kod aplikacji:
   ```bash
   git clone https://github.com/username/torrent-site.git
   cd torrent-site
   ```

2. Utwórz wymagane katalogi:
   ```bash
   mkdir -p data zips logs classes
   ```

3. Utwórz pliki danych:
   ```bash
   echo "{}" > data/torrents.json
   echo "{}" > data/users.json
   ```

4. Skopiuj pliki klas do katalogu `classes/`:
   ```bash
   # Ręcznie skopiuj wszystkie pliki klas
   ```

5. Utwórz plik konfiguracyjny `config.php`:
   ```php
   <?php
   return [
       'transmission' => [
           'rpc_url' => 'http://127.0.0.1:9091/transmission/rpc',
           'username' => '', // Jeśli wymagane, wstaw dane
           'password' => '', // Jeśli wymagane, wstaw dane
       ],
       'paths' => [
           'download_dir' => '/var/lib/transmission-daemon/downloads', // Dostosuj
           'zip_dir' => __DIR__ . '/zips',
           'data_file' => __DIR__ . '/data/torrents.json',
           'users_file' => __DIR__ . '/data/users.json',
           'logs_dir' => __DIR__ . '/logs',
       ],
       'settings' => [
           'delete_after_hours' => 24,
       ],
   ];
   ```

6. Ustaw odpowiednie uprawnienia:
   ```bash
   chown -R www-data:www-data data zips logs
   chmod -R 755 data zips logs
   ```

7. Utwórz zadanie cron do monitorowania torrentów:
   ```bash
   echo "*/5 * * * * php /ścieżka/do/aplikacji/monitor.php" > torrent_cron
   crontab torrent_cron
   ```

## Konfiguracja

### Transmission

Upewnij się, że Transmission jest skonfigurowany z dostępem RPC:

1. Edytuj plik konfiguracyjny Transmission:
   ```bash
   sudo nano /etc/transmission-daemon/settings.json
   ```

2. Ustaw następujące opcje:
   ```json
   {
       "rpc-enabled": true,
       "rpc-bind-address": "0.0.0.0",
       "rpc-port": 9091,
       "rpc-url": "/transmission/rpc",
       "rpc-whitelist-enabled": false
   }
   ```

3. Uruchom ponownie usługę Transmission:
   ```bash
   sudo systemctl restart transmission-daemon
   ```

### Serwer WWW

Skonfiguruj serwer WWW (Apache/Nginx) do obsługi aplikacji. Przykład dla Apache:

```apache
<VirtualHost *:80>
    ServerName torrent-site.local
    DocumentRoot /var/www/torrent-site
    
    <Directory /var/www/torrent-site>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/torrent-site-error.log
    CustomLog ${APACHE_LOG_DIR}/torrent-site-access.log combined
</VirtualHost>
```

## Aktualizacja

Aby zaktualizować aplikację:

1. Pobierz najnowszą wersję kodu:
   ```bash
   git pull origin main
   ```

2. Uruchom instalator, aby zaktualizować pliki:
   ```bash
   php install.php
   ```

## Rozwiązywanie problemów

Logi aplikacji są zapisywane w katalogu `logs/`. W przypadku problemów:

1. Sprawdź logi aplikacji:
   ```bash
   cat logs/app.log
   ```

2. Sprawdź logi serwera WWW:
   ```bash
   sudo tail -f /var/log/apache2/torrent-site-error.log
   ```

3. Sprawdź konfigurację Transmission:
   ```bash
   sudo cat /var/log/transmission/transmission.log
   ```

## Licencja

[MIT License](LICENSE)
