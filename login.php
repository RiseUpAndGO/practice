<?php
$link = connectDB();
mysqli_query($link, "SET NAMES 'utf8'");

$answers = [
    '<p>Неверный логин или пароль</p>',
    '<p>Введите логин и пароль</p>',
];

if (!empty($_POST['login'])) {
    $login = $_POST['login'];
    $query = "
        SELECT 
            `vk_users`.`login`,
            `vk_users`.`password`,
            `vk_users`.`id` AS `userID`,
            `vk_wall`.`owner_id` AS `wallID`,
            `vk_profile`.`owner_id` AS `profileID`,
            `vk_profile`.`firstname`,
            `vk_profile`.`lastname`,
            `vk_profile`.`age`,
            `vk_profile`.`about_yourself`
        FROM 
            `vk_users`
        LEFT JOIN 
            `vk_profile` 
        ON
            `vk_profile`.`owner_id` = `vk_users`.`id`
        LEFT JOIN
            `vk_wall` 
        ON
            `vk_wall`.`owner_id` = `vk_users`.`id`
        WHERE
            `vk_users`.`login` = '$login'";
    $result = mysqli_query($link, $query);
    $user = mysqli_fetch_assoc($result);

    if (!empty($user)) {
        $hash = $user['password'];
        if (password_verify($_POST['password'], $hash)) {
            setcookie('auth', '1', time() + 1800);
            $_SESSION = [
                'userName'  => $user['login'],
                'userID'    => $user['userID'],
                'wallID'    => $user['wallID'],
                'profileID' => $user['profileID'],
            ];

            if (getCookie('oneTimeAfterRegistration')) {
                header("Location: /profile");
            } else {
                header("Location: /id_{$user['userID']}");
            }
        } else {
            $answerToUser = $answers[0];
        }
    } else {
        $answerToUser = $answers[0];
    }
} else {
    $answerToUser = $answers[1];
}
return showAuthorization();