<?php
// classes/Config.php
class Config {
    private static $instance = null;
    private $config = [];
    
    private function __construct() {
        $this->config = include __DIR__ . '/../config.php';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        $keys = explode('.', $key);
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
}

// classes/UserManager.php
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
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
    
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);
    
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    public function getUserUUID() {
        if (isset($_COOKIE['user_uuid'])) {
            return $_COOKIE['user_uuid'];
        } elseif (isset($_SESSION['user_uuid'])) {
            return $_SESSION['user_uuid'];
        } else {
            $uuid = $this->generateUUID();
            setcookie('user_uuid', $uuid, [
                'expires' => time() + (365 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            $_SESSION['user_uuid'] = $uuid;
            return $uuid;
        }
    }
    
    public function requireUUID() {
        if (!isset($_COOKIE['user_uuid']) && !isset($_SESSION['user_uuid'])) {
            header('Location: index.php');
            exit;
        }
    }
    
    public function removeUUID() {
        setcookie('user_uuid', '', time() - 3600, "/");
        session_unset();
        session_destroy();
    }
}

// classes/Logger.php
class Logger {
    private static $instance = null;
    private $logDir;
    
    private function __construct() {
        $config = Config::getInstance();
        $this->logDir = $config->get('paths.logs_dir');
        
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
    
    public function log($message, $level = 'INFO') {
        $logFile = $this->logDir . '/app.log';
        $date = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$date][$level] $message\n", FILE_APPEND);
    }
    
    public function error($message) {
        $this->log($message, 'ERROR');
    }
    
    public function info($message) {
        $this->log($message, 'INFO');
    }
    
    public function debug($message) {
        $this->log($message, 'DEBUG');
    }
}

// classes/TransmissionClient.php
class TransmissionClient {
    private static $instance = null;
    private $rpcUrl;
    private $username;
    private $password;
    private $sessionId = null;
    private $logger;
    
    private function __construct() {
        $config = Config::getInstance();
        $this->rpcUrl = $config->get('transmission.rpc_url');
        $this->username = $config->get('transmission.username', '');
        $this->password = $config->get('transmission.password', '');
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
    
        $headers = ['Content-Type: application/json'];
        if ($this->sessionId) {
            $headers[] = 'X-Transmission-Session-Id: ' . $this->sessionId;
        }
    
        if (!empty($this->username) && !empty($this->password)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $response = curl_exec($ch);
    
        if ($response === false) {
            $error = 'Błąd połączenia z Transmission: ' . curl_error($ch);
            $this->logger->error($error);
            curl_close($ch);
            return null;
        }
    
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
    
        if ($http_code == 409) {
            if (preg_match('/X-Transmission-Session-Id: (\S+)/', $header, $matches)) {
                $this->sessionId = trim($matches[1]);
                curl_close($ch);
                return $this->rpc($data); // Powtórz z ID sesji
            } else {
                $error = 'Nie można uzyskać Session ID od Transmission.';
                $this->logger->error($error);
                curl_close($ch);
                return null;
            }
        }
    
        curl_close($ch);
    
        $response_data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Niepoprawny format JSON od Transmission: ' . json_last_error_msg();
            $this->logger->error($error . " - Odpowiedź: " . $body);
            return null;
        }
    
        if (isset($response_data['result']) && $response_data['result'] !== 'success') {
            $error = 'Błąd Transmission RPC: ' . $response_data['result'];
            $this->logger->error($error . " - Odpowiedź: " . $body);
            return null;
        }
    
        return $response_data;
    }
    
    public function addTorrent($torrentData, $isMagnet = false) {
        $data = [
            'method' => 'torrent-add',
            'arguments' => []
        ];
    
        if ($isMagnet) {
            $data['arguments']['filename'] = $torrentData;
        } else {
            $data['arguments']['metainfo'] = $torrentData;
        }
    
        return $this->rpc($data);
    }
    
    public function getTorrents($fields = []) {
        if (empty($fields)) {
            $fields = [
                'id', 'hashString', 'status', 'percentDone', 'name',
                'peersConnected', 'peersSendingToUs', 'peersGettingFromUs',
                'metadataPercentComplete'
            ];
        }
        
        $data = [
            'method' => 'torrent-get',
            'arguments' => [
                'fields' => $fields
            ]
        ];
    
        return $this->rpc($data);
    }
    
    public function removeTorrent($hash, $deleteLocalData = true) {
        $data = [
            'method' => 'torrent-remove',
            'arguments' => [
                'ids' => [$hash],
                'delete-local-data' => $deleteLocalData,
            ],
        ];
    
        return $this->rpc($data);
    }
}

// classes/TorrentManager.php
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
        $this->dataFile = $config->get('paths.data_file');
        $this->downloadDir = $config->get('paths.download_dir');
        $this->zipDir = $config->get('paths.zip_dir');
        $this->deleteAfterHours = $config->get('settings.delete_after_hours', 24);
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
        $fp = fopen($this->dataFile, 'r');
        if ($fp === false) {
            $this->logger->error("Nie można otworzyć pliku torrents.json do odczytu.");
            return [];
        }
        if (flock($fp, LOCK_SH)) {
            $filesize = filesize($this->dataFile);
            $json = $filesize > 0 ? fread($fp, $filesize) : '';
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
        $fp = fopen($this->dataFile, 'w');
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
            if ($torrent['user_uuid'] === $userUuid) {
                $torrent['zip_exists'] = (!empty($torrent['zip_path']) && file_exists($torrent['zip_path']));
                $timeLeft = $this->deleteAfterHours * 3600 - (time() - $torrent['added_at']);
                $torrent['time_left'] = $timeLeft > 0 ? $timeLeft : 0;
                $userTorrents[] = $torrent;
            }
        }
        
        return $userTorrents;
    }
    
    public function addTorrent($userUuid, $torrentData, $isMagnet = false) {
        $result = $this->transmission->addTorrent($torrentData, $isMagnet);
        
        if ($result === null) {
            return [
                'success' => false,
                'message' => 'Błąd połączenia z Transmission.'
            ];
        }
        
        if (isset($result['arguments']['torrent-added'])) {
            $torrentHash = $result['arguments']['torrent-added']['hashString'];
            $torrentName = $result['arguments']['torrent-added']['name'];
        } elseif (isset($result['arguments']['torrent-duplicate'])) {
            $torrentHash = $result['arguments']['torrent-duplicate']['hashString'];
            $torrentName = $result['arguments']['torrent-duplicate']['name'];
            return [
                'success' => false,
                'message' => 'Torrent jest już dodany.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Nie można uzyskać hash torrenta.'
            ];
        }
        
        $torrents = $this->getTorrentsData();
        if (isset($torrents[$torrentHash])) {
            return [
                'success' => false,
                'message' => 'Torrent już istnieje.'
            ];
        }
        
        $torrents[$torrentHash] = [
            'name' => $torrentName,
            'hash' => $torrentHash,
            'status' => 'downloading',
            'seeds' => 0,
            'peers' => 0,
            'percentDone' => 0,
            'added_at' => time(),
            'zip_path' => '',
            'zip_created_at' => null,
            'user_uuid' => $userUuid
        ];
        
        $this->saveTorrentsData($torrents);
        
        return [
            'success' => true,
            'message' => 'Torrent został dodany pomyślnie.'
        ];
    }
    
    public function zipTorrent($hash) {
        $torrents = $this->getTorrentsData();
        
        if (!isset($torrents[$hash])) {
            $this->logger->error("Nie znaleziono torrenta o hashu: $hash w zipper.");
            return false;
        }
        
        $torrent = &$torrents[$hash];
        
        if ($torrent['status'] === 'completed' && !empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
            $this->logger->info("Torrent $hash już jest spakowany.");
            return true;
        }
        
        $downloadPath = $this->downloadDir . '/' . $torrent['name'];
        
        if (!file_exists($downloadPath)) {
            $torrent['status'] = 'error';
            $this->saveTorrentsData($torrents);
            $this->logger->error("Katalog pobierania nie istnieje: $downloadPath dla torrenta $hash.");
            return false;
        }
        
        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $torrent['name']);
        $zipName = $safeName . '_' . $hash . '.zip';
        $zipPath = $this->zipDir . '/' . $zipName;
        
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
                $torrent['zip_path'] = $zipPath;
                $torrent['status'] = 'completed';
                $torrent['zip_created_at'] = time();
                $this->saveTorrentsData($torrents);
                $this->logger->info("Pomyślnie utworzono plik ZIP dla torrenta $hash: $zipPath");
                
                // Usunięcie torrenta z Transmission po pakowaniu
                $this->transmission->removeTorrent($hash, true);
                
                return true;
            } else {
                $torrent['status'] = 'error';
                $this->saveTorrentsData($torrents);
                $this->logger->error("Nie udało się utworzyć pliku ZIP dla torrenta $hash.");
                return false;
            }
        } else {
            $torrent['status'] = 'error';
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
        
        if (!isset($response['arguments']['torrents'])) {
            $this->logger->error("Brak danych torrentów z Transmission.");
            return;
        }
        
        $transmissionTorrents = [];
        foreach ($response['arguments']['torrents'] as $t) {
            $transmissionTorrents[$t['hashString']] = $t;
        }
        
        foreach ($torrents as $hash => &$torrent) {
            // Obsługa usuniętych torrentów
            if (!isset($transmissionTorrents[$hash])) {
                if (!empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
                    if ($torrent['status'] !== 'completed') {
                        $torrent['status'] = 'completed';
                        $updated = true;
                        $this->logger->info("Torrent $hash został usunięty z Transmission, ale ZIP jest dostępny.");
                    }
                } else {
                    if ($torrent['status'] !== 'error') {
                        $torrent['status'] = 'error';
                        $updated = true;
                        $this->logger->error("Torrent $hash został usunięty z Transmission, ale ZIP nie istnieje.");
                    }
                }
                continue;
            }
            
            $t = $transmissionTorrents[$hash];
            
            if ($torrent['name'] !== $t['name']) {
                $this->logger->info("Aktualizacja nazwy torrenta $hash z '{$torrent['name']}' na '{$t['name']}'");
                $torrent['name'] = $t['name'];
                $updated = true;
            }
            
            if (isset($t['metadataPercentComplete']) && $t['metadataPercentComplete'] < 1) {
                if ($torrent['status'] !== 'waiting_for_metadata') {
                    $this->logger->info("Torrent $hash oczekuje na metadane.");
                    $torrent['status'] = 'waiting_for_metadata';
                    $updated = true;
                }
                continue;
            }
            
            $status = $t['status'];
            $percentDone = isset($t['percentDone']) ? $t['percentDone'] : 0;
            $seeders = isset($t['peersConnected']) ? $t['peersConnected'] : 0;
            $peers = (isset($t['peersSendingToUs']) ? $t['peersSendingToUs'] : 0) + (isset($t['peersGettingFromUs']) ? $t['peersGettingFromUs'] : 0);
            
            $torrent['seeds'] = $seeders;
            $torrent['peers'] = $peers;
            $torrent['percentDone'] = $percentDone;
            
            if ($status === 6 || $percentDone >= 1) {
                $downloadPath = $this->downloadDir . '/' . $torrent['name'];
                if (!file_exists($downloadPath)) {
                    if ($torrent['status'] !== 'error') {
                        $torrent['status'] = 'error';
                        $updated = true;
                    }
                    continue;
                }
                
                if (!empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
                    if ($torrent['status'] !== 'completed') {
                        $torrent['status'] = 'completed';
                        $updated = true;
                        $this->logger->info("Torrent $hash został skompresowany: {$torrent['zip_path']}");
                    }
                } else {
                    if ($torrent['status'] !== 'zipping') {
                        $torrent['status'] = 'zipping';
                        $updated = true;
                        // Uruchomienie procesu pakowania w tle
                        $command = "/usr/bin/php " . escapeshellarg(__DIR__ . "/../zip_torrent.php") . " " . escapeshellarg($hash) . " > /dev/null 2>&1 &";
                        exec($command);
                        $this->logger->info("Uruchomiono pakowanie dla torrenta $hash.");
                    }
                }
            } else {
                if ($torrent['status'] !== 'downloading') {
                    $torrent['status'] = 'downloading';
                    $updated = true;
                }
            }
            
            // Sprawdzenie starych archiwów do usunięcia
            if ($torrent['status'] === 'completed' && isset($torrent['zip_created_at']) && $torrent['zip_created_at'] !== null) {
                $currentTime = time();
                $zipAge = $currentTime - $torrent['zip_created_at'];
                if ($zipAge >= $this->deleteAfterHours * 3600) {
                    if (file_exists($torrent['zip_path'])) {
                        if (unlink($torrent['zip_path'])) {
                            $this->logger->info("Usunięto plik ZIP dla torrenta $hash: {$torrent['zip_path']}");
                        } else {
                            $this->logger->error("Nie można usunąć pliku ZIP dla torrenta $hash: {$torrent['zip_path']}");
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
}
