<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: Details Page/details.php' . ($query ? '?' . $query : ''));
exit();
?>
