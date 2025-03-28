<?php
// bootstrap.php - scentralizowany plik include
require_once __DIR__ . '/classes/Config.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/UserManager.php';
require_once __DIR__ . '/classes/TransmissionClient.php';
require_once __DIR__ . '/classes/TorrentManager.php';

// Rozpoczęcie sesji w bootstrap, aby uniknąć duplikatów session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// index.php - uproszczona wersja
require_once 'bootstrap.php';

$userManager = UserManager::getInstance();
$user_uuid = $userManager->getUserUUID();

$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : '';

// HTML pozostaje głównie taki sam, usunięto tylko zduplikowany session_start()

// status.php - uproszczona wersja
require_once 'bootstrap.php';

$userManager = UserManager::getInstance();
$torrentManager = TorrentManager::getInstance();

$userManager->requireUUID();
$user_uuid = $userManager->getUserUUID();

header('Content-Type: application/json');
$userTorrents = $torrentManager->getUserTorrents($user_uuid);
echo json_encode($userTorrents);

// upload.php - uproszczona wersja
require_once 'bootstrap.php';

$userManager = UserManager::getInstance();
$torrentManager = TorrentManager::getInstance();
$logger = Logger::getInstance();

$userManager->requireUUID();
$user_uuid = $userManager->getUserUUID();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $torrentMetainfo = '';
    $isMagnet = false;

    if (isset($_FILES['torrent']) && $_FILES['torrent']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['torrent']['tmp_name'];
        $originalName = basename($_FILES['torrent']['name']);
        $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
        
        if (strtolower($fileExt) !== 'torrent') {
            showAlertAndRedirect('Dozwolone są tylko pliki .torrent.', 'danger');
        }

        $maxFileSize = 10 * 1024 * 1024;
        if ($_FILES['torrent']['size'] > $maxFileSize) {
            showAlertAndRedirect('Plik .torrent jest zbyt duży. Maksymalny rozmiar to 10MB.', 'danger');
        }

        $torrentContent = file_get_contents($tmpName);
        if ($torrentContent === false) {
            showAlertAndRedirect('Nie można odczytać pliku torrent.', 'danger');
        }
        
        $torrentMetainfo = base64_encode($torrentContent);
    } elseif (isset($_POST['magnet']) && !empty(trim($_POST['magnet']))) {
        $magnet = trim($_POST['magnet']);
        
        if (strpos($magnet, 'magnet:') !== 0) {
            showAlertAndRedirect('Nieprawidłowy link magnet.', 'danger');
        }
        
        $torrentMetainfo = $magnet;
        $isMagnet = true;
    } else {
        showAlertAndRedirect('Nie przesłano pliku .torrent ani linku magnet.', 'warning');
    }

    $result = $torrentManager->addTorrent($user_uuid, $torrentMetainfo, $isMagnet);
    
    if ($result['success']) {
        showAlertAndRedirect($result['message'], 'success');
    } else {
        showAlertAndRedirect($result['message'], 'danger');
    }
} else {
    header('Location: index.php');
    exit;
}

function showAlertAndRedirect($message, $type) {
    header('Location: index.php?message=' . urlencode($message) . '&type=' . urlencode($type));
    exit;
}

// download.php - uproszczona wersja
require_once 'bootstrap.php';

$userManager = UserManager::getInstance();
$torrentManager = TorrentManager::getInstance();

$userManager->requireUUID();
$user_uuid = $userManager->getUserUUID();

if (!isset($_GET['hash'])) {
    die('Nie podano hash torrenta.');
}

$hash = $_GET['hash'];
$torrents = $torrentManager->getTorrentsData();

if (!isset($torrents[$hash])) {
    die('Torrent nie istnieje.');
}

$torrent = $torrents[$hash];
if ($torrent['user_uuid'] !== $user_uuid) {
    die('Nie masz uprawnień do pobrania tego pliku.');
}

if (empty($torrent['zip_path']) || !file_exists($torrent['zip_path'])) {
    die('Plik ZIP nie jest dostępny.');
}

$zipPath = $torrent['zip_path'];
$zipName = basename($zipPath);

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($zipPath);
exit;

// logout.php - uproszczona wersja
require_once 'bootstrap.php';

$userManager = UserManager::getInstance();
$userManager->removeUUID();

header('Location: index.php?message=' . urlencode('UUID został usunięty. Twoje torrenty nie będą już dostępne.') . '&type=' . urlencode('warning'));
exit;

// monitor.php - uproszczona wersja
require_once 'bootstrap.php';

$torrentManager = TorrentManager::getInstance();
$torrentManager->monitorTorrents();

// zip_torrent.php - zastępuje zipper.php
require_once 'bootstrap.php';

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
}
