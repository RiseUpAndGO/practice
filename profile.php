<?php

$link = connectDB();

if (!empty($_POST)) {
    saveProfile($link);
}

if (getCookie('auth')) {
    return editProfile($link);
}