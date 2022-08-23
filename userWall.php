<?php
$link = connectDB();

if (!empty($_POST['addFriendID'])) {
    requestFriend($link);
}

if (!empty($_POST['deleteFriendID'])) {
    deleteFriend($link);
}

if (!empty($_POST['comment'])) {
    saveComment($link);
}

if (!empty($_POST['deleteCommentID'])) {
    deleteComment($link);
}
return showWall($link);