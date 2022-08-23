<?php

setcookie('auth', '', time() - 1);
$_SESSION = [];
header('Location: /');