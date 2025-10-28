<?php
session_start();
session_destroy();
header('Location: https://autotec-production.up.railway.app/index.php');
exit();
?>
