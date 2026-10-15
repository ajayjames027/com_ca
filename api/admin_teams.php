<?php
/**
 * API: Admin - List All Registered Teams
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

try {
    $db = getDatabase();
    $stmt = $db->query("SELECT * FROM teams ORDER BY id DESC");
    $teams = $stmt->fetchAll();

    foreach ($teams as &$t) {
        if (empty($t['pass_id'])) {
            $t['pass_id'] = 'HF26-' . (1000 + (int)$t['id']);
        }
    }
    unset($t);

    jsonResponse($teams);
} catch (Exception $e) {
    jsonResponse(['error' => 'Failed to fetch teams: ' . $e->getMessage()], 500);
}
