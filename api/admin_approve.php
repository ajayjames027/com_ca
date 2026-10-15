<?php
/**
 * API: Admin - Approve Team & Trigger Automated Email Dispatch
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/smtp_mailer.php';
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

try {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM teams WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        jsonResponse(['error' => 'Team not found.'], 404);
    }

    $passId = !empty($team['pass_id']) ? $team['pass_id'] : ('HF26-' . (1000 + $teamId));

    $updateStmt = $db->prepare("UPDATE teams SET status = 'approved', pass_id = :passId WHERE id = :id");
    $updateStmt->execute([
        ':passId' => $passId,
        ':id'     => $teamId
    ]);

    $team['status'] = 'approved';
    $team['pass_id'] = $passId;

    // Send automated email notification directly via Gmail SMTP (ajayjames027@gmail.com)
    $emailResult = sendVerificationEmailPHP($team);

    jsonResponse([
        'success' => true,
        'message' => 'Team approved successfully and Gate Pass issued.',
        'email'   => $emailResult
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Error approving team: ' . $e->getMessage()], 500);
}
