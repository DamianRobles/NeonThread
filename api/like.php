<?php
/**
 * api/like.php — Toggle like (POST JSON)
 * Body: { "tipo": "hilo"|"respuesta", "id": int }
 * Respuesta: { "liked": bool, "total": int }
 */
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['error' => 'No autenticado']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$tipo = $body['tipo'] ?? '';
$id   = (int)($body['id'] ?? 0);

if (!in_array($tipo, ['hilo','respuesta'], true) || $id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos']); exit;
}

$uid = (int)$_SESSION['usuario_id'];

try {
    if ($tipo === 'hilo') {
        $c = $pdo->prepare('SELECT id FROM hilos WHERE id=?'); $c->execute([$id]);
        if (!$c->fetch()) { http_response_code(404); echo json_encode(['error'=>'No encontrado']); exit; }

        $e = $pdo->prepare('SELECT id FROM likes WHERE usuario_id=? AND hilo_id=?');
        $e->execute([$uid, $id]);
        if ($e->fetch()) {
            $pdo->prepare('DELETE FROM likes WHERE usuario_id=? AND hilo_id=?')->execute([$uid,$id]);
            $liked = false;
        } else {
            $pdo->prepare('INSERT INTO likes (usuario_id,hilo_id) VALUES (?,?)')->execute([$uid,$id]);
            $liked = true;
        }
        $t = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE hilo_id=?'); $t->execute([$id]);
    } else {
        $c = $pdo->prepare('SELECT id FROM respuestas WHERE id=?'); $c->execute([$id]);
        if (!$c->fetch()) { http_response_code(404); echo json_encode(['error'=>'No encontrado']); exit; }

        $e = $pdo->prepare('SELECT id FROM likes WHERE usuario_id=? AND respuesta_id=?');
        $e->execute([$uid, $id]);
        if ($e->fetch()) {
            $pdo->prepare('DELETE FROM likes WHERE usuario_id=? AND respuesta_id=?')->execute([$uid,$id]);
            $liked = false;
        } else {
            $pdo->prepare('INSERT INTO likes (usuario_id,respuesta_id) VALUES (?,?)')->execute([$uid,$id]);
            $liked = true;
        }
        $t = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE respuesta_id=?'); $t->execute([$id]);
    }

    echo json_encode(['liked' => $liked, 'total' => (int)$t->fetchColumn()]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error' => 'Error interno']);
}
