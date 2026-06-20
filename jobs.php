<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: Jobs page/jobs.php' . ($query ? '?' . $query : ''));
exit();
?>
