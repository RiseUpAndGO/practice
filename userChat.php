<?php
$link = connectDB();

if (!empty($_POST['message'])) {
    $messageToDB = [
        'link'      => $link,
        'myID'      => $_SESSION['userID'],
        'companion' => $_SESSION['receiveID'],
        'message'   => $_POST['message'],
        'chatID'    => getChatID($link),
    ];
    saveChat($messageToDB);
}
return getMessages($link);