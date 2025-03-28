<?php
// install.php - Skrypt instalacyjny dla Torrent Site

// Wyłączenie limitu czasu dla długich operacji
set_time_limit(0);

// Funkcja do wyświetlania sformatowanych komunikatów
function output($message, $type = 'info') {
    $colors = [
        'success' => '0;32', // Zielony
        'info' => '0;34',    // Niebieski
        'warning' => '0;33', // Żółty
        'error' => '0;31'    // Czerwony
    ];
    
    $colorCode = isset($colors[$type]) ? $colors[$type] : $colors['info'];
    echo "\033[" . $colorCode . "m" . $message . "\033[0m\n";
}

// Sprawdź, czy uruchomiono w CLI i przełącz na wyjście HTML, jeśli web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Jeśli działa w przeglądarce, wyświetl nagłówek HTML
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Instalacja Torrent Site</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                padding: 20px;
                background-color: #f8f9fa;
            }
            .installer-container {
                max-width: 800px;
                margin: 0 auto;
                background-color: #fff;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .log-container {
                background-color: #212529;
                color: #fff;
                padding: 15px;
                border-radius: 5px;
                max-height: 400px;
                overflow-y: auto;
                font-family: monospace;
            }
            .success { color: #28a745; }
            .info { color: #0d6efd; }
            .warning { color: #ffc107; }
            .error { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class="container installer-container">
            <h1 class="mb-4">Instalacja Torrent Site</h1>
            <div class="log-container" id="logContainer">';
    
    // Nadpisanie funkcji output dla HTML
    function output($message, $type = 'info') {
        echo '<div class="' . $type . '">[' . date('H:i:s') . '] ' . htmlspecialchars($message) . '</div>' . PHP_EOL;
        flush();
    }
}

output("Rozpoczęcie instalacji Torrent Site...", "info");

// Krok 1: Sprawdź wersję PHP
$requiredPhpVersion = '7.4.0';
if (version_compare(PHP_VERSION, $requiredPhpVersion, '<')) {
    output("Błąd: Wymagana jest wersja PHP >= $requiredPhpVersion. Aktualna wersja: " . PHP_VERSION, "error");
    exit(1);
} else {
    output("PHP w wersji " . PHP_VERSION . " ✓", "success");
}

// Krok 2: Sprawdź wymagane rozszerzenia
$requiredExtensions = ['curl', 'json', 'zip', 'openssl', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (count($missingExtensions) > 0) {
    output("Błąd: Brakujące rozszerzenia PHP: " . implode(', ', $missingExtensions), "error");
    exit(1);
} else {
    output("Wszystkie wymagane rozszerzenia PHP są zainstalowane ✓", "success");
}

// Krok 3: Utwórz strukturę katalogów
$baseDir = __DIR__;
$directories = [
    $baseDir . '/data',
    $baseDir . '/zips',
    $baseDir . '/logs',
    $baseDir . '/classes'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            output("Utworzono katalog: $dir", "success");
        } else {
            output("Błąd: Nie można utworzyć katalogu: $dir", "error");
            exit(1);
        }
    } else {
        output("Katalog $dir już istnieje ✓", "info");
    }
}

// Krok 4: Utwórz lub zaktualizuj plik konfiguracyjny
$configFile = $baseDir . '/config.php';
$configTemplate = '<?php
// config.php - Wygenerowano automatycznie przez instalator

return [
    \'transmission\' => [
        \'rpc_url\' => \'http://127.0.0.1:9091/transmission/rpc\',
        \'username\' => \'\', // Jeśli wymagane, wstaw dane
        \'password\' => \'\', // Jeśli wymagane, wstaw dane
    ],
    \'paths\' => [
        \'download_dir\' => \'/var/lib/transmission-daemon/downloads\', // Dostosuj
        \'zip_dir\' => __DIR__ . \'/zips\',
        \'data_file\' => __DIR__ . \'/data/torrents.json\',
        \'users_file\' => __DIR__ . \'/data/users.json\',
        \'logs_dir\' => __DIR__ . \'/logs\',
    ],
    \'settings\' => [
        \'delete_after_hours\' => 24,
    ],
];
';

if (!file_exists($configFile)) {
    if (file_put_contents($configFile, $configTemplate)) {
        output("Utworzono plik konfiguracyjny: $configFile", "success");
    } else {
        output("Błąd: Nie można utworzyć pliku konfiguracyjnego: $configFile", "error");
        exit(1);
    }
} else {
    output("Plik konfiguracyjny już istnieje ✓", "info");
    output("Uwaga: Jeśli chcesz zresetować konfigurację, usuń plik config.php i uruchom instalator ponownie.", "warning");
}

// Krok 5: Utwórz puste pliki danych
$dataFiles = [
    $baseDir . '/data/torrents.json',
    $baseDir . '/data/users.json'
];

foreach ($dataFiles as $file) {
    if (!file_exists($file)) {
        if (file_put_contents($file, '{}')) {
            output("Utworzono plik danych: $file", "success");
        } else {
            output("Błąd: Nie można utworzyć pliku danych: $file", "error");
            exit(1);
        }
    } else {
        output("Plik danych $file już istnieje ✓", "info");
    }
}

// Krok 6: Kopiuj pliki klas
$classFiles = [
    'Config.php',
    'Logger.php',
    'UserManager.php',
    'TransmissionClient.php',
    'TorrentManager.php'
];

// Ręczne utworzenie klas
$classesCode = [
    'Config.php' => '<?php
class Config {
    private static $instance = null;
    private $config = [];
    
    private function __construct() {
        $this->config = include __DIR__ . \'/../config.php\';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        $keys = explode(\'.\', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function getAll() {
        return $this->config;
    }
}',
    'Logger.php' => '<?php
class Logger {
    private static $instance = null;
    private $logDir;
    
    private function __construct() {
        $config = Config::getInstance();
        $this->logDir = $config->get(\'paths.logs_dir\');
        
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($message, $level = \'INFO\') {
        $logFile = $this->logDir . \'/app.log\';
        $date = date(\'Y-m-d H:i:s\');
        file_put_contents($logFile, "[$date][$level] $message\n", FILE_APPEND);
    }
    
    public function error($message) {
        $this->log($message, \'ERROR\');
    }
    
    public function info($message) {
        $this->log($message, \'INFO\');
    }
    
    public function debug($message) {
        $this->log($message, \'DEBUG\');
    }
}',
    'UserManager.php' => '<?php
class UserManager {
    private static $instance = null;
    
    private function __construct() {
        // Inicjalizacja, jeśli potrzebna
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function generateUUID() {
        if (function_exists(\'com_create_guid\') === true) {
            return trim(com_create_guid(), \'{}\');
        }
    
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);
    
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
        return vsprintf(\'%s%s-%s-%s-%s-%s%s%s\', str_split(bin2hex($data), 4));
    }
    
    public function getUserUUID() {
        if (isset($_COOKIE[\'user_uuid\'])) {
            return $_COOKIE[\'user_uuid\'];
        } elseif (isset($_SESSION[\'user_uuid\'])) {
            return $_SESSION[\'user_uuid\'];
        } else {
            $uuid = $this->generateUUID();
            setcookie(\'user_uuid\', $uuid, [
                \'expires\' => time() + (365 * 24 * 60 * 60),
                \'path\' => \'/\',
                \'domain\' => \'\',
                \'secure\' => false,
                \'httponly\' => true,
                \'samesite\' => \'Lax\'
            ]);
            $_SESSION[\'user_uuid\'] = $uuid;
            return $uuid;
        }
    }
    
    public function requireUUID() {
        if (!isset($_COOKIE[\'user_uuid\']) && !isset($_SESSION[\'user_uuid\'])) {
            header(\'Location: index.php\');
            exit;
        }
    }
    
    public function removeUUID() {
        setcookie(\'user_uuid\', \'\', time() - 3600, "/");
        session_unset();
        session_destroy();
    }
}',
    'TransmissionClient.php' => '<?php
class TransmissionClient {
    private static $instance = null;
    private $rpcUrl;
    private $username;
    private $password;
    private $sessionId = null;
    private $logger;
    
    private function __construct() {
        $config = Config::getInstance();
        $this->rpcUrl = $config->get(\'transmission.rpc_url\');
        $this->username = $config->get(\'transmission.username\', \'\');
        $this->password = $config->get(\'transmission.password\', \'\');
        $this->logger = Logger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function rpc($data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->rpcUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
        $headers = [\'Content-Type: application/json\'];
        if ($this->sessionId) {
            $headers[] = \'X-Transmission-Session-Id: \' . $this->sessionId;
        }
    
        if (!empty($this->username) && !empty($this->password)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . \':\' . $this->password);
        }
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $response = curl_exec($ch);
    
        if ($response === false) {
            $error = \'Błąd połączenia z Transmission: \' . curl_error($ch);
            $this->logger->error($error);
            curl_close($ch);
            return null;
        }
    
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
    
        if ($http_code == 409) {
            if (preg_match(\'/X-Transmission-Session-Id: (\\S+)/\', $header, $matches)) {
                $this->sessionId = trim($matches[1]);
                curl_close($ch);
                return $this->rpc($data); // Powtórz z ID sesji
            } else {
                $error = \'Nie można uzyskać Session ID od Transmission.\';
                $this->logger->error($error);
                curl_close($ch);
                return null;
            }
        }
    
        curl_close($ch);
    
        $response_data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = \'Niepoprawny format JSON od Transmission: \' . json_last_error_msg();
            $this->logger->error($error . " - Odpowiedź: " . $body);
            return null;
        }
    
        if (isset($response_data[\'result\']) && $response_data[\'result\'] !== \'success\') {
            $error = \'Błąd Transmission RPC: \' . $response_data[\'result\'];
            $this->logger->error($error . " - Odpowiedź: " . $body);
            return null;
        }
    
        return $response_data;
    }
    
    public function addTorrent($torrentData, $isMagnet = false) {
        $data = [
            \'method\' => \'torrent-add\',
            \'arguments\' => []
        ];
    
        if ($isMagnet) {
            $data[\'arguments\'][\'filename\'] = $torrentData;
        } else {
            $data[\'arguments\'][\'metainfo\'] = $torrentData;
        }
    
        return $this->rpc($data);
    }
    
    public function getTorrents($fields = []) {
        if (empty($fields)) {
            $fields = [
                \'id\', \'hashString\', \'status\', \'percentDone\', \'name\',
                \'peersConnected\', \'peersSendingToUs\', \'peersGettingFromUs\',
                \'metadataPercentComplete\'
            ];
        }
        
        $data = [
            \'method\' => \'torrent-get\',
            \'arguments\' => [
                \'fields\' => $fields
            ]
        ];
    
        return $this->rpc($data);
    }
    
    public function removeTorrent($hash, $deleteLocalData = true) {
        $data = [
            \'method\' => \'torrent-remove\',
            \'arguments\' => [
                \'ids\' => [$hash],
                \'delete-local-data\' => $deleteLocalData,
            ],
        ];
    
        return $this->rpc($data);
    }
}',
    'TorrentManager.php' => '<?php
class TorrentManager {
    private static $instance = null;
    private $dataFile;
    private $downloadDir;
    private $zipDir;
    private $deleteAfterHours;
    private $logger;
    private $transmission;
    
    private function __construct() {
        $config = Config::getInstance();
        $this->dataFile = $config->get(\'paths.data_file\');
        $this->downloadDir = $config->get(\'paths.download_dir\');
        $this->zipDir = $config->get(\'paths.zip_dir\');
        $this->deleteAfterHours = $config->get(\'settings.delete_after_hours\', 24);
        $this->logger = Logger::getInstance();
        $this->transmission = TransmissionClient::getInstance();
        
        $this->ensureDirectoriesExist();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureDirectoriesExist() {
        $dirs = [
            $this->zipDir,
            dirname($this->dataFile)
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->logger->error("Nie można utworzyć katalogu: $dir");
                } else {
                    $this->logger->info("Utworzono katalog: $dir");
                }
            }
        }
    }
    
    public function getTorrentsData() {
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([]));
        }
        $fp = fopen($this->dataFile, \'r\');
        if ($fp === false) {
            $this->logger->error("Nie można otworzyć pliku torrents.json do odczytu.");
            return [];
        }
        if (flock($fp, LOCK_SH)) {
            $filesize = filesize($this->dataFile);
            $json = $filesize > 0 ? fread($fp, $filesize) : \'\';
            flock($fp, LOCK_UN);
        } else {
            $this->logger->error("Nie można zablokować pliku torrents.json do odczytu.");
            fclose($fp);
            return [];
        }
        fclose($fp);
        return json_decode($json, true) ?? [];
    }
    
    public function saveTorrentsData($data) {
        $fp = fopen($this->dataFile, \'w\');
        if ($fp === false) {
            $this->logger->error("Nie można otworzyć pliku torrents.json do zapisu.");
            return;
        }
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
        } else {
            $this->logger->error("Nie można zablokować pliku torrents.json do zapisu.");
        }
        fclose($fp);
    }
    
    public function getUserTorrents($userUuid) {
        $torrentsData = $this->getTorrentsData();
        $userTorrents = [];
        
        foreach ($torrentsData as $torrent) {
            if ($torrent[\'user_uuid\'] === $userUuid) {
                $torrent[\'zip_exists\'] = (!empty($torrent[\'zip_path\']) && file_exists($torrent[\'zip_path\']));
                $timeLeft = $this->deleteAfterHours * 3600 - (time() - $torrent[\'added_at\']);
                $torrent[\'time_left\'] = $timeLeft > 0 ? $timeLeft : 0;
                $userTorrents[] = $torrent;
            }
        }
        
        return $userTorrents;
    }
    
    public function addTorrent($userUuid, $torrentData, $isMagnet = false) {
        $result = $this->transmission->addTorrent($torrentData, $isMagnet);
        
        if ($result === null) {
            return [
                \'success\' => false,
                \'message\' => \'Błąd połączenia z Transmission.\'
            ];
        }
        
        if (isset($result[\'arguments\'][\'torrent-added\'])) {
            $torrentHash = $result[\'arguments\'][\'torrent-added\'][\'hashString\'];
            $torrentName = $result[\'arguments\'][\'torrent-added\'][\'name\'];
        } elseif (isset($result[\'arguments\'][\'torrent-duplicate\'])) {
            $torrentHash = $result[\'arguments\'][\'torrent-duplicate\'][\'hashString\'];
            $torrentName = $result[\'arguments\'][\'torrent-duplicate\'][\'name\'];
            return [
                \'success\' => false,
                \'message\' => \'Torrent jest już dodany.\'
            ];
        } else {
            return [
                \'success\' => false,
                \'message\' => \'Nie można uzyskać hash torrenta.\'
            ];
        }
        
        $torrents = $this->getTorrentsData();
        if (isset($torrents[$torrentHash])) {
            return [
                \'success\' => false,
                \'message\' => \'Torrent już istnieje.\'
            ];
        }
        
        $torrents[$torrentHash] = [
            \'name\' => $torrentName,
            \'hash\' => $torrentHash,
            \'status\' => \'downloading\',
            \'seeds\' => 0,
            \'peers\' => 0,
            \'percentDone\' => 0,
            \'added_at\' => time(),
            \'zip_path\' => \'\',
            \'zip_created_at\' => null,
            \'user_uuid\' => $userUuid
        ];
        
        $this->saveTorrentsData($torrents);
        
        return [
            \'success\' => true,
            \'message\' => \'Torrent został dodany pomyślnie.\'
        ];
    }
    
    public function zipTorrent($hash) {
        $torrents = $this->getTorrentsData();
        
        if (!isset($torrents[$hash])) {
            $this->logger->error("Nie znaleziono torrenta o hashu: $hash w zipper.");
            return false;
        }
        
        $torrent = &$torrents[$hash];
        
        if ($torrent[\'status\'] === \'completed\' && !empty($torrent[\'zip_path\']) && file_exists($torrent[\'zip_path\'])) {
            $this->logger->info("Torrent $hash już jest spakowany.");
            return true;
        }
        
        $downloadPath = $this->downloadDir . \'/\' . $torrent[\'name\'];
        
        if (!file_exists($downloadPath)) {
            $torrent[\'status\'] = \'error\';
            $this->saveTorrentsData($torrents);
            $this->logger->error("Katalog pobierania nie istnieje: $downloadPath dla torrenta $hash.");
            return false;
        }
        
        $safeName = preg_replace(\'/[^A-Za-z0-9\\-]/\', \'_\', $torrent[\'name\']);
        $zipName = $safeName . \'_\' . $hash . \'.zip\';
        $zipPath = $this->zipDir . \'/\' . $zipName;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($downloadPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($downloadPath) + 1);
                    if (!$zip->addFile($filePath, $relativePath)) {
                        $this->logger->error("Nie można dodać pliku $filePath do ZIP dla torrenta $hash.");
                    }
                }
            }
            
            $zip->close();
            
            if (file_exists($zipPath)) {
                $torrent[\'zip_path\'] = $zipPath;
                $torrent[\'status\'] = \'completed\';
                $torrent[\'zip_created_at\'] = time();
                $this->saveTorrentsData($torrents);
                $this->logger->info("Pomyślnie utworzono plik ZIP dla torrenta $hash: $zipPath");
                
                // Usunięcie torrenta z Transmission po pakowaniu
                $this->transmission->removeTorrent($hash, true);
                
                return true;
            } else {
                $torrent[\'status\'] = \'error\';
                $this->saveTorrentsData($torrents);
                $this->logger->error("Nie udało się utworzyć pliku ZIP dla torrenta $hash.");
                return false;
            }
        } else {
            $torrent[\'status\'] = \'error\';
            $this->saveTorrentsData($torrents);
            $this->logger->error("Nie można otworzyć pliku ZIP dla torrenta $hash.");
            return false;
        }
    }
    
    public function monitorTorrents() {
        $torrents = $this->getTorrentsData();
        $updated = false;
        
        if (empty($torrents)) {
            return;
        }
        
        $response = $this->transmission->getTorrents();
        if ($response === null) {
            $this->logger->error("Błąd połączenia z Transmission podczas monitorowania torrentów.");
            return;
        }
        
        if (!isset($response[\'arguments\'][\'torrents\'])) {
            $this->logger->error("Brak danych torrentów z Transmission.");
            return;
        }
        
        $transmissionTorrents = [];
        foreach ($response[\'arguments\'][\'torrents\'] as $t) {
            $transmissionTorrents[$t[\'hashString\']] = $t;
        }
        
        foreach ($torrents as $hash => &$torrent) {
            // Obsługa usuniętych torrentów
            if (!isset($transmissionTorrents[$hash])) {
                if (!empty($torrent[\'zip_path\']) && file_exists($torrent[\'zip_path\'])) {
                    if ($torrent[\'status\'] !== \'completed\') {
                        $torrent[\'status\'] = \'completed\';
                        $updated = true;
                        $this->logger->info("Torrent $hash został usunięty z Transmission, ale ZIP jest dostępny.");
                    }
                } else {
                    if ($torrent[\'status\'] !== \'error\') {
                        $torrent[\'status\'] = \'error\';
                        $updated = true;
                        $this->logger->error("Torrent $hash został usunięty z Transmission, ale ZIP nie istnieje.");
                    }
                }
                continue;
            }
            
            $t = $transmissionTorrents[$hash];
            
            if ($torrent[\'name\'] !== $t[\'name\']) {
                $this->logger->info("Aktualizacja nazwy torrenta $hash z \'{$torrent[\'name\']}\' na \'{$t[\'name\']}\'");
                $torrent[\'name\'] = $t[\'name\'];
                $updated = true;
            }
            
            if (isset($t[\'metadataPercentComplete\']) && $t[\'metadataPercentComplete\'] < 1) {
                if ($torrent[\'status\'] !== \'waiting_for_metadata\') {
                    $this->logger->info("Torrent $hash oczekuje na metadane.");
                    $torrent[\'status\'] = \'waiting_for_metadata\';
                    $updated = true;
                }
                continue;
            }
            
            $status = $t[\'status\'];
            $percentDone = isset($t[\'percentDone\']) ? $t[\'percentDone\'] : 0;
            $seeders = isset($t[\'peersConnected\']) ? $t[\'peersConnected\'] : 0;
            $peers = (isset($t[\'peersSendingToUs\']) ? $t[\'peersSendingToUs\'] : 0) + (isset($t[\'peersGettingFromUs\']) ? $t[\'peersGettingFromUs\'] : 0);
            
            $torrent[\'seeds\'] = $seeders;
            $torrent[\'peers\'] = $peers;
            $torrent[\'percentDone\'] = $percentDone;
            
            if ($status === 6 || $percentDone >= 1) {
                $downloadPath = $this->downloadDir . \'/\' . $torrent[\'name\'];
                if (!file_exists($downloadPath)) {
                    if ($torrent[\'status\'] !== \'error\') {
                        $torrent[\'status\'] = \'error\';
                        $updated = true;
                    }
                    continue;
                }
                
                if (!empty($torrent[\'zip_path\']) && file_exists($torrent[\'zip_path\'])) {
                    if ($torrent[\'status\'] !== \'completed\') {
                        $torrent[\'status\'] = \'completed\';
                        $updated = true;
                        $this->logger->info("Torrent $hash został skompresowany: {$torrent[\'zip_path\']}");
                    }
                } else {
                    if ($torrent[\'status\'] !== \'zipping\') {
                        $torrent[\'status\'] = \'zipping\';
                        $updated = true;
                        // Uruchomienie procesu pakowania w tle
                        $command = "/usr/bin/php " . escapeshellarg(__DIR__ . "/../zip_torrent.php") . " " . escapeshellarg($hash) . " > /dev/null 2>&1 &";
                        exec($command);
                        $this->logger->info("Uruchomiono pakowanie dla torrenta $hash.");
                    }
                }
            } else {
                if ($torrent[\'status\'] !== \'downloading\') {
                    $torrent[\'status\'] = \'downloading\';
                    $updated = true;
                }
            }
            
            // Sprawdzenie starych archiwów do usunięcia
            if ($torrent[\'status\'] === \'completed\' && isset($torrent[\'zip_created_at\']) && $torrent[\'zip_created_at\'] !== null) {
                $currentTime = time();
                $zipAge = $currentTime - $torrent[\'zip_created_at\'];
                if ($zipAge >= $this->deleteAfterHours * 3600) {
                    if (file_exists($torrent[\'zip_path\'])) {
                        if (unlink($torrent[\'zip_path\'])) {
                            $this->logger->info("Usunięto plik ZIP dla torrenta $hash: {$torrent[\'zip_path\']}");
                        } else {
                            $this->logger->error("Nie można usunąć pliku ZIP dla torrenta $hash: {$torrent[\'zip_path\']}");
                        }
                    }
                    
                    unset($torrents[$hash]);
                    $updated = true;
                    $this->logger->info("Usunięto torrent $hash z torrents.json po {$this->deleteAfterHours} godzinach.");
                }
            }
        }
        
        if ($updated) {
            $this->saveTorrentsData($torrents);
        }
    }
}'
];

foreach ($classFiles as $classFile) {
    $targetFile = $baseDir . '/classes/' . $classFile;
    
    if (file_put_contents($targetFile, $classesCode[$classFile])) {
        output("Zapisano klasę: $targetFile", "success");
    } else {
        output("Błąd: Nie można zapisać klasy: $targetFile", "error");
    }
}

// Krok 7: Utwórz plik bootstrap
$bootstrapFile = $baseDir . '/bootstrap.php';
$bootstrapCode = '<?php
// bootstrap.php - scentralizowany plik include
require_once __DIR__ . \'/classes/Config.php\';
require_once __DIR__ . \'/classes/Logger.php\';
require_once __DIR__ . \'/classes/UserManager.php\';
require_once __DIR__ . \'/classes/TransmissionClient.php\';
require_once __DIR__ . \'/classes/TorrentManager.php\';

// Rozpoczęcie sesji w bootstrap, aby uniknąć duplikatów session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
';

if (file_put_contents($bootstrapFile, $bootstrapCode)) {
    output("Utworzono plik bootstrap: $bootstrapFile", "success");
} else {
    output("Błąd: Nie można utworzyć pliku bootstrap: $bootstrapFile", "error");
    exit(1);
}

// Krok 8: Utwórz zadanie cron dla monitorowania
$cronCommand = "*/5 * * * * php " . $baseDir . "/monitor.php";
$cronFile = $baseDir . '/torrent_cron';
if (file_put_contents($cronFile, $cronCommand . PHP_EOL)) {
    output("Utworzono plik cron: $cronFile", "success");
    output("Aby zainstalować cron, wykonaj: crontab $cronFile", "info");
} else {
    output("Błąd: Nie można utworzyć pliku cron: $cronFile", "error");
}

// Krok 9: Ustaw uprawnienia
$webUser = 'www-data'; // Domyślny użytkownik serwera WWW w wielu systemach
$permissionDirs = [$baseDir . '/data', $baseDir . '/zips', $baseDir . '/logs'];

output("Uwaga: Upewnij się, że katalogi mają odpowiednie uprawnienia dla użytkownika serwera www.", "warning");
output("Przykładowe polecenie: chown -R $webUser:$webUser " . implode(' ', $permissionDirs), "info");

// Krok 10: Testuj połączenie z Transmission
output("Testuję połączenie z Transmission...", "info");

// Dołącz bootstrap, aby użyć naszych klas
require_once $bootstrapFile;

$config = Config::getInstance();
$transmission = TransmissionClient::getInstance();

$testData = [
    'method' => 'session-get'
];

$result = $transmission->rpc($testData);

if ($result !== null) {
    output("Połączenie z Transmission działa poprawnie ✓", "success");
} else {
    output("Błąd: Nie można połączyć się z Transmission. Sprawdź ustawienia w config.php.", "error");
    output("URL RPC: " . $config->get('transmission.rpc_url'), "info");
}

// Krok 11: Utwórz pliki kontrolerów
$controllerFiles = [
    'index.php' => '<?php
require_once \'bootstrap.php\';

$userManager = UserManager::getInstance();
$user_uuid = $userManager->getUserUUID();

$message = isset($_GET[\'message\']) ? $_GET[\'message\'] : \'\';
$messageType = isset($_GET[\'type\']) ? $_GET[\'type\'] : \'\';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Torrent Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
    /* Wyśrodkowanie pionowe i poziome formularza */
    .centered-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40vh;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Torrent Site</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Przełącz nawigację">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Usuń UUID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!empty($message)): ?>
        <div class="container mt-4">
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
            </div>
        </div>
    <?php endif; ?>

    <main class="mt-5 pt-4">
        <div class="container centered-container">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header text-center">
                        <i class="fas fa-upload me-2"></i>Dodaj Torrent
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="torrent" class="form-label">Prześlij plik .torrent</label>
                                <input class="form-control" type="file" id="torrent" name="torrent" accept=".torrent">
                            </div>
                            <div class="mb-3">
                                <label for="magnet" class="form-label">Lub wprowadź link magnet</label>
                                <input type="text" class="form-control" id="magnet" name="magnet" placeholder="magnet:?xt=urn:btih:...">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-hover">Pobierz</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela na dole -->
        <div class="container mt-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-list me-2"></i>Twoje Torrenty
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="torrentsTable" class="table table-dark table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nazwa</th>
                                            <th>Seedów</th>
                                            <th>Peerów</th>
                                            <th>Postęp</th>
                                            <th>Status</th>
                                            <th>Pozostały czas</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody id="torrentsTableBody">
                                        <!-- Dane załadowane przez JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <footer class="bg-dark text-center text-white mt-5">
        <div class="container p-4">
            <p>&copy; <?php echo date(\'Y\'); ?> Torrent Site. Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>',
    'upload.php' => '<?php
require_once \'bootstrap.php\';

$userManager = UserManager::getInstance();
$torrentManager = TorrentManager::getInstance();
$logger = Logger::getInstance();

$userManager->requireUUID();
$user_uuid = $userManager->getUserUUID();

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    $torrentMetainfo = \'\';
    $isMagnet = false;

    if (isset($_FILES[\'torrent\']) && $_FILES[\'torrent\'][\'error\'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[\'torrent\'][\'tmp_name\'];
        $originalName = basename($_FILES[\'torrent\'][\'name\']);
        $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
        
        if (strtolower($fileExt) !== \'torrent\') {
            showAlertAndRedirect(\'Dozwolone są tylko pliki .torrent.\', \'danger\');
        }

        $maxFileSize = 10 * 1024 * 1024;
        if ($_FILES[\'torrent\'][\'size\'] > $maxFileSize) {
            showAlertAndRedirect(\'Plik .torrent jest zbyt duży. Maksymalny rozmiar to 10MB.\', \'danger\');
        }

        $torrentContent = file_get_contents($tmpName);
        if ($torrentContent === false) {
            showAlertAndRedirect(\'Nie można odczytać pliku torrent.\', \'danger\');
        }
        
        $torrentMetainfo = base64_encode($torrentContent);
    } elseif (isset($_POST[\'magnet\']) && !empty(trim($_POST[\'magnet\']))) {
        $magnet = trim($_POST[\'magnet\']);
        
        if (strpos($magnet, \'magnet:\') !== 0) {
            showAlertAndRedirect(\'Nieprawidłowy link magnet.\', \'danger\');
        }
        
        $torrentMetainfo = $magnet;
        $isMagnet = true;
    } else {
        showAlertAndRedirect(\'Nie przesłano pliku .torrent ani linku magnet.\', \'warning\');
    }

    $result = $torrentManager->addTorrent($user_uuid, $torrentMetainfo, $isMagnet);
    
    if ($result[\'success\']) {
        showAlertAndRedirect($result[\'message\'], \'success\');
    } else {
        showAlertAndRedirect($result[\'message\'], \'danger\');
    }
} else {
    header(\'Location: index.php\');
    exit;
}

function showAlertAndRedirect($message, $type) {
    header(\'Location: index.php?message=\' . urlencode($message) . \'&type=\' . urlencode($type));
    exit;
}',
    'download.php' => '<?php
require_once \'bootstrap.php\';

$userManager = UserManager::getInstance();
$torrentManager = TorrentManager::getInstance();

$userManager->requireUUID();
$user_uuid = $userManager->getUserUUID();

if (!isset($_GET[\'hash\'])) {
    die(\'Nie podano hash torrenta.\');
}

$hash = $_GET[\'hash\'];
$torrents = $torrentManager->getTorrentsData();

if (!isset($torrents[$hash])) {
    die(\'Torrent nie istnieje.\');
}

$torrent = $torrents[$hash];
if ($torrent[\'user_uuid\'] !== $user_uuid) {
    die(\'Nie masz uprawnień do pobrania tego pliku.\');
}

if (empty($torrent[\'zip_path\']) || !file_exists($torrent[\'zip_path\'])) {
    die(\'Plik ZIP nie jest dostępny.\');
}

$zipPath = $torrent[\'zip_path\'];
$zipName = basename($zipPath);

header(\'Content-Description: File Transfer\');
header(\'Content-Type: application/zip\');
header(\'Content-Disposition: attachment; filename="\' . $zipName . \'"\');
header(\'Content-Length: \' . filesize($zipPath));
header(\'Cache-Control: must-revalidate\');
header(\'Pragma: public\');
header(\'Expires: 0\');

readfile($zipPath);
exit;',
    'logout.php' => '<?php
require_once \'bootstrap.php\';

$userManager = UserManager::getInstance();
$userManager->removeUUID();

header(\'Location: index.php?message=\' . urlencode(\'UUID został usunięty. Twoje torrenty nie będą już dostępne.\') . \'&type=\' . urlencode(\'warning\'));
exit;',
    'status.php' => '<?php
require_once \'bootstrap.php\';

$userManager = UserManager::getInstance();
$torrentManager = TorrentManager::getInstance();

$userManager->requireUUID();
$user_uuid = $userManager->getUserUUID();

header(\'Content-Type: application/json\');
$userTorrents = $torrentManager->getUserTorrents($user_uuid);
echo json_encode($userTorrents);',
    'monitor.php' => '<?php
require_once \'bootstrap.php\';

$torrentManager = TorrentManager::getInstance();
$torrentManager->monitorTorrents();',
    'zip_torrent.php' => '<?php
require_once \'bootstrap.php\';

$logger = Logger::getInstance();
$torrentManager = TorrentManager::getInstance();

if ($argc < 2) {
    $logger->error("Brak argumentu hash w zip_torrent.php.");
    exit("Brak argumentu hash.\n");
}

$hash = $argv[1];
$result = $torrentManager->zipTorrent($hash);

if ($result) {
    exit("Torrent został pomyślnie spakowany.\n");
} else {
    exit("Wystąpił błąd podczas pakowania torrenta.\n");
}'
];

foreach ($controllerFiles as $file => $content) {
    if (file_put_contents($baseDir . '/' . $file, $content)) {
        output("Utworzono plik kontrolera: $file", "success");
    } else {
        output("Błąd: Nie można utworzyć pliku kontrolera: $file", "error");
    }
}

// Instrukcje końcowe
output("\nInstalacja zakończona! Wykonaj następujące kroki:", "success");
output("1. Upewnij się, że serwer WWW (Apache/Nginx) jest skonfigurowany z PHP.", "info");
output("2. Zmodyfikuj plik config.php, jeśli potrzebujesz dostosować ustawienia.", "info");
output("3. Ustaw odpowiednie uprawnienia dla katalogów:", "info");
output("   sudo chown -R www-data:www-data " . implode(' ', $permissionDirs), "info");
output("4. Zainstaluj zadanie cron:", "info");
output("   crontab $cronFile", "info");
output("5. Otwórz stronę w przeglądarce!", "info");

if (!$isCli) {
    echo '</div>
            <div class="mt-4">
                <h2>Następne kroki</h2>
                <ol>
                    <li>Upewnij się, że serwer WWW (Apache/Nginx) jest skonfigurowany z PHP.</li>
                    <li>Zmodyfikuj plik config.php, jeśli potrzebujesz dostosować ustawienia.</li>
                    <li>Ustaw odpowiednie uprawnienia dla katalogów:
                        <pre>sudo chown -R www-data:www-data ' . implode(' ', $permissionDirs) . '</pre>
                    </li>
                    <li>Zainstaluj zadanie cron:
                        <pre>crontab ' . $cronFile . '</pre>
                    </li>
                    <li>Otwórz stronę w przeglądarce!</li>
                </ol>
            </div>
        </div>
    </body>
    </html>';
}
