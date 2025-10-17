<?php
session_start();
session_destroy();
header('Location: http://localhost/autotec/index.php');
exit();
?>
