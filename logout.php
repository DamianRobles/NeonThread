<?php
/**
 * logout.php — Cierre de sesión
 *
 * Destruye la sesión activa y redirige al inicio.
 * No tiene vista propia — es solo lógica de servidor.
 */

session_start();

// Eliminar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión en el servidor
session_destroy();

// Redirigir al inicio
header('Location: index.php');
exit;