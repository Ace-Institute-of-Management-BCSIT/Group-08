<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/Contact Us Page/contact.php';
    exit();
}
header('Location: Contact Us Page/contactus.php');
exit();
?>
