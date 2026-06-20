<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/Login Page/login.php';
    exit();
}
header('Location: Login Page/login.html');
exit();
?>
