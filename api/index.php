<?php
/**
 * API Front Controller / Dispatcher
 * Allows clean routing regardless of server mod_rewrite setup
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Normalize URI relative to this api directory
$pos = strpos($uri, '/api');
$endpoint = '';
if ($pos !== false) {
    $endpoint = trim(substr($uri, $pos + 4), '/');
}
if (empty($endpoint) && !empty($_GET['endpoint'])) {
    $endpoint = trim($_GET['endpoint'], '/');
}

// 1. Team Registration
if ($endpoint === 'register' || $endpoint === 'register.php') {
    require __DIR__ . '/register.php';
    exit;
}

// 2. Authentication
if ($endpoint === 'login' || $endpoint === 'login.php') {
    require __DIR__ . '/login.php';
    exit;
}

// 3. Team Status
if (preg_match('#^team/status/(.+)$#', $endpoint, $matches) || $endpoint === 'team/status' || $endpoint === 'team_status.php') {
    if (isset($matches[1])) {
        $_GET['teamName'] = urldecode($matches[1]);
    }
    require __DIR__ . '/team_status.php';
    exit;
}

// 4. Admin: Teams List
if ($endpoint === 'admin/teams' || $endpoint === 'admin_teams.php') {
    require __DIR__ . '/admin_teams.php';
    exit;
}

// 5. Admin: Approve Team
if (preg_match('#^admin/approve/([0-9]+)$#', $endpoint, $matches) || $endpoint === 'admin_approve.php') {
    if (isset($matches[1])) {
        $_GET['id'] = $matches[1];
    }
    require __DIR__ . '/admin_approve.php';
    exit;
}

// 6. Admin: Reject Team
if (preg_match('#^admin/reject/([0-9]+)$#', $endpoint, $matches) || $endpoint === 'admin_reject.php') {
    if (isset($matches[1])) {
        $_GET['id'] = $matches[1];
    }
    require __DIR__ . '/admin_reject.php';
    exit;
}

// 7. Admin: Delete Team
if (preg_match('#^admin/team/([0-9]+)$#', $endpoint, $matches) || $endpoint === 'admin_delete.php') {
    if (isset($matches[1])) {
        $_GET['id'] = $matches[1];
    }
    require __DIR__ . '/admin_delete.php';
    exit;
}

// 8. Admin: Export CSV
if ($endpoint === 'admin/export-csv' || $endpoint === 'export_csv.php') {
    require __DIR__ . '/export_csv.php';
    exit;
}

// Fallback: Welcome / Healthcheck
jsonResponse([
    'service' => "HACKFEST '26 API",
    'status'  => 'online',
    'organizer' => "Department of Commerce Computer Applications, St. Joseph's College (Autonomous)",
    'endpoints' => [
        'POST api/register'         => 'Team registration',
        'POST api/login'            => 'Authentication',
        'GET  api/team/status/{id}' => 'Team status & gate pass',
        'GET  api/admin/teams'      => 'List registered teams',
        'POST api/admin/approve/{id}'=> 'Approve team & email gate pass',
        'POST api/admin/reject/{id}' => 'Reject team',
        'GET  api/admin/export-csv' => 'Export CSV'
    ]
]);
