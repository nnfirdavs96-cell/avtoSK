<?php
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Необходима авторизация']); exit; }

$userId = (int)$_SESSION['user_id'];
$db     = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?");
    $s->execute([$userId]);
    echo json_encode(['count' => (int)$s->fetchColumn()]);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true) ?: $_POST;
$action = $data['action'] ?? '';

function cartCount(PDO $db, int $uid): int {
    $s = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?");
    $s->execute([$uid]);
    return (int)$s->fetchColumn();
}
function cartTotal(PDO $db, int $uid): float {
    $s = $db->prepare("SELECT COALESCE(SUM(c.quantity*p.price),0) FROM cart c JOIN parts p ON p.id=c.part_id WHERE c.user_id=?");
    $s->execute([$uid]);
    return (float)$s->fetchColumn();
}

switch ($action) {
    case 'add':
        $partId = (int)($data['part_id'] ?? 0);
        $qty    = max(1, (int)($data['quantity'] ?? 1));
        if (!$partId) { echo json_encode(['success'=>false,'error'=>'Неверный ID']); exit; }
        $ps = $db->prepare("SELECT id, stock FROM parts WHERE id=? AND is_active=1");
        $ps->execute([$partId]);
        if (!$ps->fetch()) { echo json_encode(['success'=>false,'error'=>'Товар не найден']); exit; }
        $db->prepare("INSERT INTO cart (user_id,part_id,quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity)")
           ->execute([$userId, $partId, $qty]);
        echo json_encode(['success'=>true,'cart_count'=>cartCount($db,$userId),'cart_total'=>cartTotal($db,$userId)]);
        break;
    case 'remove':
        $partId = (int)($data['part_id'] ?? 0);
        $db->prepare("DELETE FROM cart WHERE user_id=? AND part_id=?")->execute([$userId, $partId]);
        echo json_encode(['success'=>true,'cart_count'=>cartCount($db,$userId),'cart_total'=>cartTotal($db,$userId)]);
        break;
    case 'update':
        $partId = (int)($data['part_id'] ?? 0);
        $qty    = max(1, min(99, (int)($data['quantity'] ?? 1)));
        $db->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND part_id=?")->execute([$qty, $userId, $partId]);
        $ss = $db->prepare("SELECT c.quantity*p.price FROM cart c JOIN parts p ON p.id=c.part_id WHERE c.user_id=? AND c.part_id=?");
        $ss->execute([$userId, $partId]);
        $rowSub = (float)$ss->fetchColumn();
        echo json_encode(['success'=>true,'cart_count'=>cartCount($db,$userId),'cart_total'=>cartTotal($db,$userId),'row_subtotal'=>$rowSub]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Unknown action']);
}
