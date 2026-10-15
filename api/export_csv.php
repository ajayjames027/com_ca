<?php
/**
 * API: Admin - Export Registrations as CSV
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

try {
    $db = getDatabase();
    $stmt = $db->query("SELECT * FROM teams ORDER BY id ASC");
    $teams = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hackfest26_teams.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Add BOM for Microsoft Excel UTF-8 compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Header row
    fputcsv($output, [
        'ID',
        'Pass No',
        'Team Name',
        'Status',
        'College',
        'Track',
        'Leader Name',
        'Leader Email',
        'Leader Phone',
        'Leader Dept',
        'Member 2 Name',
        'Member 2 Email',
        'Member 2 Phone',
        'Member 2 Dept',
        'Payment Ref',
        'Created At'
    ]);

    foreach ($teams as $r) {
        $pass = !empty($r['pass_id']) ? $r['pass_id'] : ('HF26-' . (1000 + (int)$r['id']));
        fputcsv($output, [
            $r['id'],
            $pass,
            $r['team_name'] ?? '',
            $r['status'] ?? 'pending',
            $r['college'] ?? '',
            $r['track'] ?? 'General',
            $r['leader_name'] ?? '',
            $r['leader_email'] ?? '',
            $r['leader_phone'] ?? '',
            $r['leader_dept'] ?? '',
            $r['member2_name'] ?? '',
            $r['member2_email'] ?? '',
            $r['member2_phone'] ?? '',
            $r['member2_dept'] ?? '',
            $r['payment_ref'] ?? '',
            $r['created_at'] ?? ''
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Error generating CSV: " . $e->getMessage();
    exit;
}
