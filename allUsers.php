<?php

$link          = connectDB();
$queryAllUsers = 'SELECT `id`, `login` FROM `vk_users`';
$users         = extractFromDB($queryAllUsers, $link);

if (!empty($users)) {
    return allUsers($users);
}