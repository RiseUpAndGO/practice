<?php
//⊗ppPmSDPrm - №3
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/vk/functions.php';

$url = $_SERVER['REQUEST_URI'];
$auth = getCookie('auth');

Routing($url, $auth);