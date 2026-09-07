<div align="center">

# 🌊 Torrent Downloader v2

### Self-Hosted PHP Web Interface for Transmission RPC

**PHP • Transmission • RPC • Magnet Links • Torrent Files • Apache/Nginx**

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![Transmission](https://img.shields.io/badge/Engine-Transmission-D70000)
![Server](https://img.shields.io/badge/Web-Apache%20%7C%20Nginx-2ea44f)
![Use](https://img.shields.io/badge/Use-Authorized%20Content-success)

</div>

---

## 🚀 About

**Torrent Downloader v2** is a self-hosted PHP project that provides a browser-based layer for a Transmission daemon through its RPC interface. It is the newer iteration of the `Torrent_downloader` project and includes installer/bootstrap logic intended to simplify deployment on a compatible server.

It is designed for users searching for a **Transmission web interface**, **PHP torrent manager**, **self-hosted download panel**, **Transmission RPC GUI** or a lightweight browser frontend for a Transmission service they administer.

---

## ✨ Capabilities

| Feature | Description |
|---|---|
| 🌊 Transmission RPC | Connect to a Transmission daemon |
| 🧲 Magnet workflow | Handle magnet-oriented downloads |
| 📦 Torrent input | Work with `.torrent`-based transfers |
| 🌐 Web interface | Manage the workflow from a browser |
| ⚙️ Configurable endpoint | Point the app at your Transmission RPC service |
| 🛠️ Installer/bootstrap | Setup-oriented scripts included |
| 🖥️ Self-hosted | Designed for your own compatible server |

---

## 📋 Requirements

- PHP 7.4+
- PHP cURL / JSON and other extensions used by the deployment
- Transmission daemon with RPC enabled
- Apache, Nginx or another PHP-capable server

---

## 📦 Basic Setup

```bash
git clone https://github.com/Swir/Torrent_downloaderv2.git
cd Torrent_downloaderv2
php install.php
```

Review `Config.php`, installer behavior, filesystem permissions and Transmission settings before deployment.

---

## 🔐 Deployment Security

Keep Transmission RPC authentication enabled and restrict network exposure appropriately. Do not expose management interfaces publicly without authentication, HTTPS and suitable firewall/access controls.

---

## 🔍 Discoverability

`transmission web interface php` • `transmission rpc gui` • `php torrent manager` • `self hosted torrent web ui` • `transmission frontend` • `magnet web interface` • `torrent download panel php`

---

## ⚖️ Responsible Use

Use this project only to transfer content you are legally authorized to download or distribute.

---

## 👨‍💻 Maintainer

Maintained by **Swir** — [@Swir](https://github.com/Swir)

<div align="center">

### 🌊 A lightweight PHP control layer for your own Transmission server

⭐ **Star the repository if it helps your self-hosted setup!**

</div>
