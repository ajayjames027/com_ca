<?php
/**
 * API: Admin - Delete Team Registration
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'DELETE' && $method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed. Use DELETE or POST.'], 405);
}

$teamId = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($teamId <= 0 && !empty($_SERVER['PATH_INFO'])) {
    $teamId = intval(trim($_SERVER['PATH_INFO'], '/'));
}

if ($teamId <= 0) {
    jsonResponse(['error' => 'Invalid or missing team ID.'], 400);
}

try {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT payment_pic FROM teams WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $teamId]);
    $team = $stmt->fetch();

    if ($team && !empty($team['payment_pic'])) {
        $filePath = __DIR__ . '/../uploads/' . basename($team['payment_pic']);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    $delStmt = $db->prepare("DELETE FROM teams WHERE id = :id");
    $delStmt->execute([':id' => $teamId]);

    jsonResponse(['success' => true, 'message' => 'Team deleted successfully.']);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error deleting team: ' . $e->getMessage()], 500);
}
