<div align="center">

# 🌊 Torrent Downloader v2

**Self-hosted PHP interface for managing downloads through Transmission RPC**  
**Self-hostowany interfejs PHP do zarządzania pobieraniem przez Transmission RPC**

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![Transmission](https://img.shields.io/badge/Engine-Transmission-D70000)
![Server](https://img.shields.io/badge/Server-Apache%20%7C%20Nginx-success)
![Author](https://img.shields.io/badge/Author-Swir-ff4fa3)

</div>

---

## 🇬🇧 English

Torrent Downloader v2 is a self-hosted PHP project designed to provide a web layer for a Transmission daemon through its RPC interface. The project includes configuration/bootstrap logic and an installer intended to simplify deployment on a compatible server.

### ✨ Project capabilities
- Transmission RPC integration
- `.torrent` and magnet-oriented workflow
- web-based deployment
- installation/bootstrap scripts
- configurable Transmission endpoint
- server-side download environment

### 🛠 Requirements
- PHP 7.4+
- PHP extensions required by the deployment, including cURL/JSON and other features used by the application
- Transmission daemon with RPC enabled
- Apache/Nginx or another PHP-capable web server

### 🚀 Basic setup
```bash
git clone https://github.com/Swir/Torrent_downloaderv2.git
cd Torrent_downloaderv2
php install.php
```

Review `Config.php` and the installer before exposing the service publicly. Keep Transmission RPC authentication and firewall rules appropriately restricted.

---

## 🇵🇱 Polski

Torrent Downloader v2 to self-hostowany projekt PHP zapewniający warstwę WWW dla demona Transmission poprzez interfejs RPC. Repozytorium zawiera konfigurację, bootstrap oraz instalator upraszczający wdrożenie na zgodnym serwerze.

### ✨ Możliwości projektu
- integracja z Transmission RPC
- obsługa workflow dla `.torrent` i linków magnet
- interfejs przeznaczony do działania na serwerze WWW
- instalator i mechanizm bootstrap
- konfigurowalny adres usługi Transmission
- serwerowe środowisko pobierania

### 🛠 Wymagania
- PHP 7.4+
- wymagane rozszerzenia PHP, w tym cURL/JSON oraz pozostałe moduły używane przez aplikację
- Transmission daemon z włączonym RPC
- Apache/Nginx lub inny serwer obsługujący PHP

### 🚀 Podstawowa instalacja
```bash
git clone https://github.com/Swir/Torrent_downloaderv2.git
cd Torrent_downloaderv2
php install.php
```

Przed wystawieniem usługi do Internetu sprawdź `Config.php`, konfigurację instalatora, uwierzytelnianie Transmission RPC oraz reguły firewalla.

---

## 🔐 Responsible use / Odpowiedzialne użycie
Use this project only to transfer content you are legally authorized to download or distribute. / Używaj projektu wyłącznie do pobierania i udostępniania treści, do których masz odpowiednie prawa.

## 👤 Author / Autor
Maintained in this repository by **Swir**.
