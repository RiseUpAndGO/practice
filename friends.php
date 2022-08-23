<?php

$link = connectDB();

if (!empty($_POST['receiveFriendID'])) {
    updateFriendship($link);
}
return showFriends($link);