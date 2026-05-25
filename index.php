<?php
// index.php
session_start();
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
// Contoh Routing Sederhana
switch($page) {
case 'login':
include 'views/login.php';
break;
case 'register':
include 'views/register.php';
break;
case 'dashboard-masyarakat':
// Proteksi Halaman (Ekuivalen dengan Middleware Auth Laravel)
if (!isset($_SESSION['user'])) { header("Location: index.php?page=login");
exit; }
include 'views/masyarakat/dashboard.php';
break;
default:
include 'views/home.php';
break;
}
