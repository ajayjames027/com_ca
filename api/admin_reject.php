<?php
/**
 * API: Admin - Reject Team Registration
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed. Use POST.'], 405);
}

$teamId = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($teamId <= 0 && !empty($_SERVER['PATH_INFO'])) {
    $teamId = intval(trim($_SERVER['PATH_INFO'], '/'));
}

if ($teamId <= 0) {
    jsonResponse(['error' => 'Invalid or missing team ID.'], 400);
}

$inputData = json_decode(file_get_contents('php://input'), true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}
$note = trim($inputData['note'] ?? 'Payment details could not be verified on the college portal.');

try {
    $db = getDatabase();
    $stmt = $db->prepare("UPDATE teams SET status = 'rejected', admin_notes = :note WHERE id = :id");
    $stmt->execute([
        ':note' => $note,
        ':id'   => $teamId
    ]);

    jsonResponse(['success' => true, 'message' => 'Team marked as rejected.']);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error rejecting team: ' . $e->getMessage()], 500);
}
