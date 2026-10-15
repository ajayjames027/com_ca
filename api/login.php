<?php
/**
 * API: User & Admin Authentication
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed. Use POST.'], 405);
}

// Read JSON input or standard POST data
$inputData = json_decode(file_get_contents('php://input'), true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}

$username = trim($inputData['username'] ?? '');
$password = $inputData['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(['error' => 'Username and password are required.'], 400);
}

// 1. Check Admin Credentials
if ($username === ADMIN_USER && $password === ADMIN_PASS) {
    jsonResponse([
        'role'  => 'admin',
        'token' => 'admin-session-active'
    ]);
}

// 2. Check Team Credentials
try {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM teams WHERE LOWER(team_name) = LOWER(:username) AND password = :password LIMIT 1");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password
    ]);

    $team = $stmt->fetch();

    if (!$team) {
        jsonResponse(['error' => 'Invalid team name or password.'], 401);
    }

    if (empty($team['pass_id'])) {
        $team['pass_id'] = 'HF26-' . (1000 + (int)$team['id']);
    }

    jsonResponse([
        'role' => 'user',
        'team' => $team
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Authentication server error: ' . $e->getMessage()], 500);
}
