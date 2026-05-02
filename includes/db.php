<?php

/**
 * includes/db.php — Conexión a la base de datos SQLite
 *
 * Crea la conexión PDO y expone la variable $pdo para que cualquier
 * página que haga require_once de este archivo pueda ejecutar consultas.
 *
 * En el primer acceso, si las tablas no existen, las crea automáticamente.
 * El archivo .db se genera solo — no requiere configuración manual.
 *
 * Uso en otras páginas:
 *   require_once 'includes/db.php';        (desde la raíz)
 *   require_once '../includes/db.php';     (desde subcarpetas como admin/)
 */

// Ruta absoluta al archivo de la BD, relativa a este mismo archivo.
// __DIR__ apunta siempre al directorio de db.php, sin importar desde
// dónde se haga el include.
$dbPath = __DIR__ . '/../neonthread.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);

    // Lanzar excepciones ante cualquier error de SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Devolver arrays asociativos por defecto en fetch()
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Activar soporte de claves foráneas (SQLite lo desactiva por defecto)
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Crear las tablas si no existen (se ejecuta solo en el primer acceso)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT    NOT NULL UNIQUE,
            email      TEXT    NOT NULL UNIQUE,
            password   TEXT    NOT NULL,
            avatar     TEXT    DEFAULT NULL,
            rol        TEXT    NOT NULL DEFAULT 'user'
            CHECK(rol IN ('user', 'admin')),
            created_at TEXT    DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS secciones (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre      TEXT    NOT NULL,
            descripcion TEXT,
            icono       TEXT    DEFAULT 'bi-chat-dots'
        );

        CREATE TABLE IF NOT EXISTS hilos (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo     TEXT    NOT NULL,
            contenido  TEXT    NOT NULL,
            usuario_id INTEGER NOT NULL,
            seccion_id INTEGER NOT NULL,
            created_at TEXT    DEFAULT (datetime('now')),
            updated_at TEXT    DEFAULT (datetime('now')),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)  ON DELETE CASCADE,
            FOREIGN KEY (seccion_id) REFERENCES secciones(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS respuestas (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            contenido  TEXT    NOT NULL,
            usuario_id INTEGER NOT NULL,
            hilo_id    INTEGER NOT NULL,
            created_at TEXT    DEFAULT (datetime('now')),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (hilo_id)    REFERENCES hilos(id)    ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS likes (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id   INTEGER NOT NULL,
            hilo_id      INTEGER DEFAULT NULL,
            respuesta_id INTEGER DEFAULT NULL,
            created_at   TEXT    DEFAULT (datetime('now')),
            UNIQUE(usuario_id, hilo_id),
            UNIQUE(usuario_id, respuesta_id),
            FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE CASCADE,
            FOREIGN KEY (hilo_id)      REFERENCES hilos(id)      ON DELETE CASCADE,
            FOREIGN KEY (respuesta_id) REFERENCES respuestas(id) ON DELETE CASCADE
        );
    ");

    // Insertar las secciones solo si la tabla está vacía
    $count = $pdo->query('SELECT COUNT(*) FROM secciones')->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO secciones (nombre, descripcion, icono) VALUES
                ('Videojuegos',    'Cyberpunk 2077, Deus Ex, Observer y todo el gaming distópico.',          'bi-controller'),
                ('Libros',         'Neuromante, Snow Crash, ¿Sueñan los androides? y más literatura.',       'bi-book-fill'),
                ('Juegos de Mesa', 'Shadowrun, Android Netrunner, Cyberpunk RED y otros TTRPGs.',            'bi-dice-5-fill'),
                ('Lore Cyberpunk', 'Corporaciones, tecnología, filosofía distópica y estética del género.',  'bi-cpu-fill');
        ");
    }
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
