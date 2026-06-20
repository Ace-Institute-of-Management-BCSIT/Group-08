<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/Signup page/signup.php';
    exit();
}
header('Location: Signup page/signup.html');
exit();
?>
