<?php
session_start();

if (!isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["rol"] === "administrador") {
    header("Location: libros.php");
} else {
    header("Location: prestamos.php");
}
exit;
