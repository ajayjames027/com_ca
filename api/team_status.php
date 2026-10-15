<?php
/**
 * API: Fetch Team Status & Gate Pass Data
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

$teamName = trim($_GET['teamName'] ?? '');

// If not in query param, check PATH_INFO (e.g. /api/team_status.php/CyberKnights)
if (empty($teamName) && !empty($_SERVER['PATH_INFO'])) {
    $teamName = trim($_SERVER['PATH_INFO'], '/');
}

if (empty($teamName)) {
    jsonResponse(['error' => 'Team name is required.'], 400);
}

try {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM teams WHERE LOWER(team_name) = LOWER(:teamName) OR LOWER(pass_id) = LOWER(:teamName) LIMIT 1");
    $stmt->execute([':teamName' => $teamName]);

    $team = $stmt->fetch();
    if (!$team) {
        jsonResponse(['error' => 'Team not found.'], 404);
    }

    if (empty($team['pass_id'])) {
        $team['pass_id'] = 'HF26-' . (1000 + (int)$team['id']);
    }

    jsonResponse(['team' => $team]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error fetching team status: ' . $e->getMessage()], 500);
}
