<?php

$link = connectDB();
mysqli_query($link, "SET NAMES 'utf8'");

if (
    !empty($_POST['login'])
    && !empty($_POST['password'])
    && !empty($_POST['confirm'])
) {
    if ($_POST['password'] === $_POST['confirm']) {
        $login = $_POST['login'];
        $password = $_POST['password'];

        $queryUser = "SELECT `vk_users`.`login` FROM `vk_users` WHERE `login` = '$login'";
        $userFromDB = mysqli_fetch_assoc(mysqli_query($link, $queryUser));

        $validationLogin = preg_match('#^[a-zA-Z0-9_-]+@[a-z]+\.{1}[a-z]{2,10}$#', $login);
        $validationPass  = preg_match('#^\w{4,12}$#', $password);

        if (empty($userFromDB)) {
            if ($validationLogin === 1) {
                if ($validationPass === 1) {
                    $password = password_hash($password, PASSWORD_DEFAULT);

                    $queryUser = "INSERT INTO `vk_users` (`login`, `password`) VALUES ('$login', '$password')";
                    mysqli_query($link, $queryUser) or die(mysqli_error($link));
                    $lastInsertID = mysqli_insert_id($link);

                    $queryProfile = "INSERT INTO `vk_profile` (`owner_id`) VALUES ('$lastInsertID')";
                    mysqli_query($link, $queryProfile) or die(mysqli_error($link));

                    $message = 'Добро пожаловть Мандалорец!';
                    $queryWall = "INSERT INTO `vk_wall` (`owner_id`, `author_id`, `message`) VALUES ('$lastInsertID', '6', '$message')";
                    mysqli_query($link, $queryWall) or die(mysqli_error($link));

                    $afterRegistration = '
                        <p>Предлагаем вам заполнить информацию о себе. Это можно сделать позднее в меню "Профиль"</p>
                    ';
                    setcookie('oneTimeAfterRegistration', $afterRegistration, time() + 3600 * 24 * 365);
                    header('Location: /login');
                } else {
                    $answerToUser = 'Пароль должен содержать от 4 до 12 символов';
                }
            } else {
                $answerToUser = 'Введите логин вида e-mail';
            }
        } else {
            $answerToUser = 'E-mail занят, попробуйте другой';
        }
    } else {
        $answerToUser = 'Пароли не свопадают';
    }
} else {
    $answerToUser = 'Заполните форму полностью';
}
return showRegistration();