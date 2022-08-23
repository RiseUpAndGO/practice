<?php

function Routing(string $url, bool $auth): mixed
{
    if ($auth) {
        if (preg_match('#/id_(?<userID>[0-9]+)#', $url, $params)) {
            $_SESSION['urlUserID'] = $params['userID'];
            return makeContent('userWall.php');
        }
        if (preg_match('#/chat_with_id_(?<receiveID>[0-9]+)#', $url, $params)) {
            $_SESSION['receiveID'] = $params['receiveID'];
            return makeContent('userChat.php');
        }
        $pageList = [
            '/'         => 'userWall.php',
            '/allusers' => 'allUsers.php',
            '/allchats' => 'allChats.php',
            '/friends'  => 'friends.php',
            '/profile'  => 'profile.php',
            '/logout'   => 'logout.php',
        ];
    } else {
        $pageList = [
            '/'             => 'login.php',
            '/login'        => 'login.php',
            '/registration' => 'register.php',
            '/logout'       => 'logout.php',
        ];
    }
    if (array_key_exists($url, $pageList)) {
        return makeContent($pageList[$url]);
    }
    return makeContent('404.php');
}

function makeContent(string $pages): void
{
    $root   = $_SERVER['DOCUMENT_ROOT'] . '/vk/';
    $layout = file_get_contents($root . 'layout.php');
    $page   = require_once "$root" . "$pages";

    $layout = str_replace('{{ title }}', $page['title'], $layout);
    $layout = str_replace('{{ content }}', $page['content'], $layout);
    $layout = str_replace('{{ user }}', $page['user'], $layout);

    echo $layout;
}

function connectDB(): mysqli
{
    $hostDB = 'localhost';
    $userDB = 'root';
    $passDB = 'root';
    $nameBD = 'mydb';

    return $link = mysqli_connect($hostDB, $userDB, $passDB, $nameBD);
}

function extractFromDB(string $query, mysqli $link): array
{
    mysqli_query($link, "SET NAMES 'utf8'");

    $result = mysqli_query($link, $query) or die(mysqli_error($link));

    for ($data = []; $row = mysqli_fetch_assoc($result); $data[] = $row) ;

    return $data;
}

function showAuthorization(): array
{
    if (!getCookie('auth')) {
        $title   = 'Вход';
        $content = '<h2>Авторизация</h2>';
        if (isset($answerToUser)) {
            $content .= $answerToUser;
        }
        $content .= '
        <form action="" method="post">
            <p><input name="login" placeholder="Введите логин"></p>
            <p><input name="password" type="password" placeholder="Введите пароль"></p>
            <p><input type="submit" value="Войти"></p>
        </form>
        <form action="/registration" method="post">
            <input type="submit" value="Зарегистрироваться">
        </form>
    ';
        return returnContent($title, $content);
    }
    return require_once $_SERVER['DOCUMENT_ROOT'] . '/vk/404.php';
}

function showRegistration(): array
{
    if (!getCookie('auth')) {
        $title   = 'Регистрация';
        $content = '<h2>Регистрация</h2>';
        if (isset($answerToUser)) {
            $content .= $answerToUser;
        }
        $content .= '
            <form action="" method="POST">
                <p><input name="login" placeholder="Введите e-mail"></p>
                <p><input name="password" type="password" placeholder="Введите пароль"></p>
                <p><input name="confirm" type="password" placeholder="Повторите пароль"></p>
                <p><input type="submit" value="Зарегистрироваться!"></p>
            </form>
            <form action="/login" method="post">
                <input type="submit" value="Страница входа">
            </form>
            ';

        return returnContent($title, $content);
    }
    return require_once $_SERVER['DOCUMENT_ROOT'] . '/vk/404.php';
}

function allUsers(array $users): array
{
    $title   = 'Список всех пользователей';
    $content = '<h2>Список всех пользователей</h2>';

    foreach ($users as $user) {
        $content .= "
			<div>
				<hr>
				<a href='/id_{$user['id']}'>{$user['login']}</a>
				<hr>
			</div>
		";
    }
    return returnContent($title, $content);
}

function showWall(mysqli $link): array
{
    $queryWall = "
        SELECT 
            `vk_wall`.`id`, `vk_wall`.`owner_id`, `vk_wall`.`author_id`, `vk_wall`.`message`, `vk_wall`.`create_date`,
            `vk_profile`.`firstname`, `vk_profile`.`lastname`, `vk_profile`.`age`, `vk_profile`.`about_yourself`,
            `owner_users`.`login` AS ownerLogin,
            `author_users`.`login` AS authorLogin,
            `author_users`.`id` AS authorID
        FROM 
            `vk_wall`
        LEFT JOIN 
            `vk_users` AS `owner_users`
        ON 
            `owner_users`.`id` = `vk_wall`.`owner_id`
        LEFT JOIN
            `vk_users` AS `author_users`
        ON
            `author_users`.`id` = `vk_wall`.`author_id`
        LEFT JOIN
            `vk_profile`
        ON
            `vk_profile`.`owner_id` = `owner_users`.`id`
        WHERE 
            `vk_wall`.`owner_id` = {$_SESSION['urlUserID']}
        ORDER BY
            `vk_wall`.`create_date`
    ";
    $wall      = extractFromDB($queryWall, $link);
    if (!empty($wall)) {
        $title   = "Стена - {$wall[0]['ownerLogin']}";
        $content = showProfile($wall, $link);

        foreach ($wall as $comment) {
            $content .= "
                <div style='color: red'>
                    <i>Комментарий от <a href='/id_{$comment['authorID']}'>{$comment['authorLogin']}</a> . {$comment['create_date']}</i>
                    <p>{$comment['message']}</p>
                    --------------------------------------------------------------------
                </div>
            ";
            if ($_SESSION['userID'] === $_SESSION['urlUserID'] || $_SESSION['userID'] === $comment['author_id']) {
                $content .= '
                <form action="" method="post">
                    <input name="deleteCommentID" type="hidden" value="' . $comment['id'] . '">
                    <input type="submit" value="Удалить">
                </form>
            ';
            }
        }
        $content .= addWallComment();

        return returnContent($title, $content);
    }
    return require_once $_SERVER['DOCUMENT_ROOT'] . '/vk/404.php';
}

function addWallComment(): string
{
    return '
        <div>
            <form action="" method="post">
            <b><label for="commentInput">Добавить комменатрий:</label></b><br>
            <textarea name="comment" id="commentInput" rows="6" cols="50"></textarea><br>
            <input type="submit" value="Отправить">
            </form>
        </div>
    ';
}

function saveComment(mysqli $link): void
{
    $comment  = $_POST['comment'];
    $authorID = $_SESSION['userID'];

    $querySaveComment = "INSERT INTO `vk_wall` (`owner_id`, `author_id`, `message`) 
            VALUES ({$_SESSION['urlUserID']}, '$authorID', '$comment')";
    mysqli_query($link, "SET NAMES 'utf8'");
    mysqli_query($link, $querySaveComment) or die(mysqli_error($link));

    header("Refresh: 0");
}

function deleteComment(mysqli $link): void
{
    $commentID          = $_POST['deleteCommentID'];
    $queryDeleteComment = "DELETE FROM `vk_wall` WHERE `vk_wall`.`id` = '$commentID'";
    mysqli_query($link, $queryDeleteComment) or die(mysqli_error($link));

    header("Refresh: 0");
}

function showAllChats(mysqli $link)
{
    $myID = $_SESSION['userID'];

    $queryAllChats = "
    SELECT
        `user_one`.`id` AS userOneID,
        `user_one`.`login` AS userOne,
        `user_two`.`id` AS userTwoID,
        `user_two`.`login` AS userTwo
    FROM
        `vk_chats`
    LEFT JOIN
        `vk_users` AS `user_one`
    ON
        `user_one`.`id` = `vk_chats`.`user_one`
    LEFT JOIN
        `vk_users` AS `user_two`
    ON
        `user_two`.`id` = `vk_chats`.`user_two`
    WHERE
        (`vk_chats`.`user_one` = $myID)
    OR
        (`vk_chats`.`user_two` = $myID)
    ";
    $chats = extractFromDB($queryAllChats, $link);

    $title       = 'Все ваши чаты';
    $content     = '<h2>Ваши чаты</h2>';

    if (!empty($chats)) {
        foreach ($chats as $chat) {
            if ($chat['userOneID'] == $myID) {
                $companionID   = $chat['userTwoID'];
                $companionName = $chat['userTwo'];
            } else {
                $companionID   = $chat['userOneID'];
                $companionName = $chat['userOne'];
            }
            $content .= '
                <hr>
                <div>
                    <a href="/chat_with_id_' . $companionID . '">' . $companionName . '</a>
                </div>
                <hr>
            ';
        }
    } else {
        $content .= '<p>У вас еще нет начатых бесед</p>';
    }
    return returnContent($title, $content);
}

function startChat(): string
{
    $myID = $_SESSION['userID'];
    $anotherUserID = $_SESSION['urlUserID'];
    if ($myID !== $anotherUserID) {
        return '
            <form action="/chat_with_id_' . $anotherUserID . '" method="post">
                <input type="submit" value="Начать беседу">
            </form>
        ';
    }
    return '';
}

function addChatComment(): string
{
    return '
        <div>
            <form action="" method="post">
            <b><label for="chatMessage">Сообщение:</label></b><br>
            <textarea name="message" id="chatMessage" rows="4" cols="40"></textarea><br>
            <input type="submit" value="Отправить">
            </form>
        </div>
    ';
}

function getChatID(mysqli $link): int|bool
{
    $myID        = $_SESSION['userID'];
    $companionID = $_SESSION['receiveID'];

    $queryChat = "
    SELECT
        `vk_chats`.`id`
    FROM
        `vk_chats`
    WHERE
        (vk_chats.`user_one` = $myID AND `vk_chats`.`user_two` = $companionID)
    OR
        (`vk_chats`.`user_one` = $companionID AND `vk_chats`.`user_two` = $myID)
    ";
    $chatID = extractFromDB($queryChat, $link);

    if (!empty($chatID)) {
        return $chatID[0]['id'];
    }

    if ($myID !== $companionID) {
        $insertChat = "INSERT INTO `vk_chats` (`user_one`, `user_two`) VALUES ($myID, $companionID)";
        mysqli_query($link, "SET NAMES 'utf8'");
        mysqli_query($link, $insertChat) or die(mysqli_error($link));

        return mysqli_insert_id($link);
    }
    return false;
}

function getMessages(mysqli $link): array
{
    $myID        = $_SESSION['userID'];
    $companionID = $_SESSION['receiveID'];

    $chatID = getChatID($link);
    if (! $chatID) {
        return require_once $_SERVER['DOCUMENT_ROOT'] . '/vk/404.php';
    }

    $queryMessages = "
    SELECT
        `vk_pm`.`author_id`,
        `vk_pm`.`recipient_id`,
        `vk_pm`.`create_date`,
        `vk_pm`.`message`,
        `vk_users`.`login` AS `author`
    FROM
        `vk_pm`
    LEFT JOIN
        `vk_users`
    ON
        `vk_users`.`id` = `vk_pm`.`author_id`
    WHERE
        `vk_pm`.`chat_id` = $chatID
    ";
    $messages       = extractFromDB($queryMessages, $link);

    $queryCompanion = "SELECT `login` FROM `vk_users` WHERE `id` = $companionID";
    $companion      = extractFromDB($queryCompanion, $link);

    $title   = "Чат с {$companion[0]['login']}";
    $content = "<h2>Чат с {$companion[0]['login']}</h2>";
    if (!empty($messages)) {
        foreach ($messages as $message) {
            if ($message['author_id'] == $myID) {
                $content .= '
                <p>
                    <hr>
                    <span style="color: green">Вы - ' . $message['create_date'] . '</span><br>
                    ' . $message['message'] . '
                    <hr>
                </p> 
            ';
            } else {
                $content .= '
                <p>
                    <hr>
                    <span style="color: red">От ' . $message['author'] . ' - ' . $message['create_date'] . '</span><br>
                    ' . $message['message'] . '
                    <hr>
                </p> 
            ';
            }
        }
    } else {
        $content = 'В вашем чате еще нет сообщений';
    }
    $content .= addChatComment();

    return returnContent($title, $content);
}

function saveChat(array $message): void
{
    $link = $message['link'];
    $querySaveMessage = "INSERT INTO vk_pm (`author_id`, `recipient_id`, `message`, `chat_id`) 
            VALUES ({$message['myID']}, {$message['companion']}, '{$message['message']}', {$message['chatID']})";
    mysqli_query($link, "SET NAMES 'utf8'");
    mysqli_query($link, $querySaveMessage) or die(mysqli_error($link));

    header("Refresh: 0");
}

function friendManager(mysqli $link): string
{
    $userID   = $_SESSION['userID'];
    $friendID = $_SESSION['urlUserID'];
    if ($userID !== $friendID) {
        $queryFriend = "
            SELECT * FROM `vk_friends`
            WHERE (`friend_one` = $userID OR `friend_two` = $userID)
            AND (`friend_one` = $friendID OR `friend_two` = $friendID)
        ";
        $friends     = extractFromDB($queryFriend, $link);

        if (!empty($friends)) {
            if ($friends[0]['status'] === '1') {
                return '
                    <span style="color: #008800;  border: 2px solid red">Это ваш друг</span>
                    <form action="" method="post">
                        <input name="deleteFriendID" type="hidden" value="' . $friends[0]['id'] . '">
                        <input type="submit" value="Удалить из друзей">
                    </form>
                ';
            }
            if ($friends[0]['friend_one'] === $userID && $friends[0]['status'] === '0') {
                return '<span style="color: #0000cc; border: 2px solid red">Заявка на дружбу отправлена</span>';
            }
            if ($friends[0]['friend_two'] === $userID && $friends[0]['status'] === '0') {
                return '<span style="color: #0000cc; border: 2px solid red">Этот пользователь вам отправил заявку на дружбу</span>';
            }
        } else {
            return '
                 <form action="" method="post">
                    <input name="addFriendID" type="hidden" value="' . $friendID . '">
                    <input type="submit" value="Добавить в друзья">
                </form>
            ';
        }
    }
    return '';
}

function requestFriend(mysqli $link): void
{
    $userID   = $_SESSION['userID'];
    $friendID = $_POST['addFriendID'];
    $status   = 0;

    $queryAddFriend =
        "INSERT INTO `vk_friends` (`friend_one`, `friend_two`, `status`) VALUES ($userID, $friendID, $status)";
    mysqli_query($link, "SET NAMES 'utf8'");
    mysqli_query($link, $queryAddFriend) or die(mysqli_error($link));

    header("Refresh: 0");
}

function showFriends(mysqli $link): array
{
    $userID          = $_SESSION['userID'];
    $myLogin         = $_SESSION['userName'];
    $queryAllFriends = "
        SELECT
            `friend_one`.`login` AS `friendOne`,
            `friend_two`.`login` AS `friendTwo`,
            `vk_friends`.`friend_one` AS `friendOneID`,
            `vk_friends`.`friend_two` AS `friendTwoID`,
            `vk_friends`.`status`,
            `vk_friends`.`id`
        FROM
            `vk_friends`
        LEFT JOIN
            `vk_users` AS `friend_one`
        ON
            `friend_one`.`id` = `vk_friends`.`friend_one`
        LEFT JOIN
            `vk_users` AS `friend_two`
        ON
            `friend_two`.`id` = `vk_friends`.`friend_two`
        WHERE
            `friend_one` = $userID OR `friend_two` = $userID
    ";
    $allFriends      = extractFromDB($queryAllFriends, $link);

    $title   = 'Ваши друзья и заявки';
    $content = '<h2>Список друзей</h2>';

    foreach ($allFriends as $friend) {
        $requestID = $friend['id'];
        if ($friend['friendOne'] === $myLogin && $friend['status'] === '0') {
            $login     = $friend['friendTwo'];
            $content  .= showFriendRequest($login, $requestID, 0);
        } elseif ($friend['friendTwo'] === $myLogin && $friend['status'] === '0') {
            $login     = $friend['friendOne'];
            $content  .= showFriendRequest($login, $requestID, 0);
        } elseif ($friend['friendOne'] === $myLogin && $friend['status'] === '1') {
            $login     = $friend['friendTwo'];
            $friendID  = $friend['friendTwoID'];
            $content  .= showFriendRequest($login, $friendID, 1);
        } elseif ($friend['friendTwo'] === $myLogin && $friend['status'] === '1') {
            $login     = $friend['friendOne'];
            $friendID  = $friend['friendOneID'];
            $content  .= showFriendRequest($login, $friendID, 1);
        }
    }
    return returnContent($title, $content);
}

function showFriendRequest (string $login, int $id, int $status): string
{
    if ($status === 0) {
        return '
        <div>
 	        Заявка от - ' . $login . '
 	        <form action="" method="post">
                <input name="receiveFriendID" type="hidden" value="' . $id . '">
                <input type="submit" value="Принять дружбу">
            </form>
        </div>
    ';
    }
    return '
        <div>
		    <a href="/id_' . $id . '">' . $login . '</a>
		</div>
    ';
}

function updateFriendship(mysqli $link): void
{
    $rowID = $_POST['receiveFriendID'];
    $queryFriend = "UPDATE `vk_friends` SET `status` = 1 WHERE `id` = $rowID";

    mysqli_query($link, "SET NAMES 'utf8'");
    mysqli_query($link, $queryFriend) or die(mysqli_error($link));

    header("Refresh: 0");
}

function deleteFriend(mysqli $link): void
{
    $friendID          = $_POST['deleteFriendID'];
    $queryDeleteComment = "DELETE FROM `vk_friends` WHERE `id` = '$friendID'";
    mysqli_query($link, $queryDeleteComment) or die(mysqli_error($link));

    header("Refresh: 0");
}

function showProfile(array $wall, mysqli $link): string
{
    $friendStatus = friendManager($link);
    $startChat = startChat();
    return "
        <h2>Стена - {$wall[0]['ownerLogin']}</h2>
        $friendStatus
        $startChat
        <p>Имя и фамилия - {$wall[0]['firstname']} {$wall[0]['lastname']}</p>
        <p>Возраст - {$wall[0]['age']}</p>
        <p>О себе - {$wall[0]['about_yourself']}</p>
        <hr>
    ";
}

function editProfile(mysqli $link): array
{
    $queryProfile = "SELECT * FROM `vk_profile` WHERE `owner_id` = {$_SESSION['userID']}";
    $profile      = extractFromDB($queryProfile, $link);

    $title = 'Редактировать профиль';

    $content = '<h2>Ваш профиль</h2>';
    if (getCookie('oneTimeAfterRegistration')) {
        $content .= $_COOKIE['oneTimeAfterRegistration'];
        setcookie('oneTimeAfterRegistration', '', time() - 1);
    }
    $content .= '
        <div>
            <form action="" method="post">
                <p><b><label for="firstname">Имя:</label></b></p>
                <input name="firstname" id="firstname" type="text" value="' . $profile[0]['firstname'] . '">
                
                <p><b><label for="lastname">Фамилия:</label></b></p>
                <input name="lastname" id="lastname" type="text" value="' . $profile[0]['lastname'] . '">
                
                <p><b><label for="age">Возраст:</label></b></p>
                <input name="age" id="age" type="number" value="' . $profile[0]['age'] . '">
                
                <p><b><label for="aboutYourself">О себе:</label></b></p>
                <textarea name="aboutYourself" id="aboutYourself" rows="6" cols="50">' . $profile[0]['about_yourself'] . '</textarea><br>
                
                <input type="submit" value="Сохранить профиль">
            </form>
        </div>
    ';

    return returnContent($title, $content);
}

function saveProfile(mysqli $link): void
{
    $userID        = $_SESSION['userID'];
    $firstname     = $_POST['firstname'];
    $lastname      = $_POST['lastname'];
    $age           = !empty($_POST['age']) ? $_POST['age'] : 'null';
    $aboutYourself = $_POST['aboutYourself'];

    $query = "UPDATE
                `vk_profile` 
                SET
                `firstname` = '$firstname',
                `lastname` = '$lastname',
                `age` = $age,
                `about_yourself` = '$aboutYourself'
                WHERE
                `owner_id` = '$userID'";

    mysqli_query($link, "SET NAMES 'utf8'");
    mysqli_query($link, $query) or die(mysqli_error($link));

    header("Location: /id_$userID");
}

function showUserBar(): string
{
    if (getCookie('auth')) {
        $user = "<p>Добро пожаловать - {$_SESSION['userName']}</p>";
        $user .= '
        <style>
            form { display : inline-block; } 
        </style>
        <form action="/id_' . $_SESSION['userID'] . '" method="post">
            <input type="submit" value="На стену">
        </form>
        <form action="/profile" method="post">
            <input type="submit" value="Профиль">
        </form>
        <form action="/allusers" method="post">
            <input type="submit" value="Пользователи">
        </form>
        <form action="/allchats" method="post">
            <input type="submit" value="Чаты">
        </form>
        <form action="/friends" method="post">
            <input type="submit" value="Друзья">
        </form>
        <form action="/logout" method="post">
            <input type="submit" value="Выйти">
        </form>
    ';
    } else {
        $user = 'Войдите или зарегистрируйтесь.';
    }
    return $user;
}

function returnContent(string $title, string $content): array
{
    $user = require_once $_SERVER['DOCUMENT_ROOT'] . '/vk/user.php';
    return [
        'title'   => $title,
        'content' => $content,
        'user'    => $user
    ];
}

function getCookie($cookieName): mixed
{
    if (isset($_COOKIE[$cookieName])) {
        return $_COOKIE[$cookieName];
    } else {
        return false;
    }
}