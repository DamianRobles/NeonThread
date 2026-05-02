-- ================================================
-- schema.sql — NeonThread
-- Estructura de la base de datos en SQLite.
--
-- SQLite genera el archivo .db automáticamente al
-- primer acceso desde db.php, por lo que este archivo
-- sirve como referencia y documentación del esquema.
--
-- Para recrear la BD desde cero:
--   1. Elimina el archivo neonthread.db (si existe)
--   2. Ejecuta este script con: sqlite3 neonthread.db < schema.sql
-- ================================================
-- Activa el soporte de claves foráneas (desactivado por defecto en SQLite)
PRAGMA foreign_keys = ON;

-- ------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------
CREATE TABLE
    IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL, -- hash bcrypt con password_hash()
        avatar TEXT DEFAULT NULL,
        rol TEXT NOT NULL DEFAULT 'user' CHECK (rol IN ('user', 'admin')), -- solo dos roles posibles
        created_at TEXT DEFAULT (datetime ('now'))
    );

-- ------------------------------------------------
-- Tabla: secciones
-- ------------------------------------------------
CREATE TABLE
    IF NOT EXISTS secciones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        descripcion TEXT,
        icono TEXT DEFAULT 'bi-chat-dots' -- clase de Bootstrap Icons
    );

-- Datos iniciales de las 4 secciones del foro
INSERT INTO
    secciones (nombre, descripcion, icono)
VALUES
    (
        'Videojuegos',
        'Cyberpunk 2077, Deus Ex, Observer y todo el gaming distópico.',
        'bi-controller'
    ),
    (
        'Libros',
        'Neuromante, Snow Crash, ¿Sueñan los androides? y más literatura.',
        'bi-book-fill'
    ),
    (
        'Juegos de Mesa',
        'Shadowrun, Android Netrunner, Cyberpunk RED y otros TTRPGs.',
        'bi-dice-5-fill'
    ),
    (
        'Lore Cyberpunk',
        'Corporaciones, tecnología, filosofía distópica y estética del género.',
        'bi-cpu-fill'
    );

-- ------------------------------------------------
-- Tabla: hilos
-- ------------------------------------------------
CREATE TABLE
    IF NOT EXISTS hilos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        contenido TEXT NOT NULL,
        usuario_id INTEGER NOT NULL,
        seccion_id INTEGER NOT NULL,
        created_at TEXT DEFAULT (datetime ('now')),
        updated_at TEXT DEFAULT (datetime ('now')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (seccion_id) REFERENCES secciones (id) ON DELETE CASCADE
    );

-- Trigger que actualiza updated_at al editar un hilo
-- SQLite no soporta ON UPDATE CURRENT_TIMESTAMP, se hace con trigger
CREATE TRIGGER IF NOT EXISTS hilos_updated_at AFTER
UPDATE ON hilos FOR EACH ROW BEGIN
UPDATE hilos
SET
    updated_at = datetime ('now')
WHERE
    id = OLD.id;

END;

-- ------------------------------------------------
-- Tabla: respuestas
-- ------------------------------------------------
CREATE TABLE
    IF NOT EXISTS respuestas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contenido TEXT NOT NULL,
        usuario_id INTEGER NOT NULL,
        hilo_id INTEGER NOT NULL,
        created_at TEXT DEFAULT (datetime ('now')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (hilo_id) REFERENCES hilos (id) ON DELETE CASCADE
    );

-- ------------------------------------------------
-- Tabla: likes
-- Permite dar like a un hilo O a una respuesta.
-- Uno de los dos IDs debe ser NULL en cada fila.
-- ------------------------------------------------
CREATE TABLE
    IF NOT EXISTS likes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL,
        hilo_id INTEGER DEFAULT NULL,
        respuesta_id INTEGER DEFAULT NULL,
        created_at TEXT DEFAULT (datetime ('now')),
        -- Un usuario solo puede dar like una vez por ítem
        UNIQUE (usuario_id, hilo_id),
        UNIQUE (usuario_id, respuesta_id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (hilo_id) REFERENCES hilos (id) ON DELETE CASCADE,
        FOREIGN KEY (respuesta_id) REFERENCES respuestas (id) ON DELETE CASCADE
    );