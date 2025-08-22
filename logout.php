<?php
session_start();

if (isset($_SESSION['admin'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
} elseif (isset($_SESSION['cliente_id'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
}
exit();
?>