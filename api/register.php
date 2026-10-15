<?php
/**
 * API: Team Registration
 * HACKFEST '26 - Department of Commerce Computer Applications, St. Joseph's College
 */

require_once __DIR__ . '/../config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed. Use POST.'], 405);
}

try {
    $teamName    = trim($_POST['teamName'] ?? '');
    $password    = $_POST['password'] ?? '';
    $college     = trim($_POST['college'] ?? '');
    $track       = trim($_POST['track'] ?? 'General Application Development');
    $leaderName  = trim($_POST['leaderName'] ?? '');
    $leaderEmail = strtolower(trim($_POST['leaderEmail'] ?? ''));
    $leaderPhone = trim($_POST['leaderPhone'] ?? '');
    $leaderDept  = trim($_POST['leaderDept'] ?? 'Commerce Computer Applications');
    $member2Name = trim($_POST['member2Name'] ?? '');
    $member2Email= strtolower(trim($_POST['member2Email'] ?? ''));
    $member2Phone= trim($_POST['member2Phone'] ?? '');
    $member2Dept = trim($_POST['member2Dept'] ?? 'Commerce Computer Applications');
    $paymentRef  = trim($_POST['paymentRef'] ?? '');
    $paymentRef2 = trim($_POST['paymentRef2'] ?? $paymentRef);

    // Validation
    if (empty($teamName) || empty($password) || empty($college) ||
        empty($leaderName) || empty($leaderEmail) ||
        empty($member2Name) || empty($member2Email)) {
        jsonResponse(['error' => 'Please provide all required fields for both team members.'], 400);
    }

    if (!filter_var($leaderEmail, FILTER_VALIDATE_EMAIL) || !filter_var($member2Email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Please enter valid email addresses for both participants.'], 400);
    }

    // Handle Payment Receipt 1 (Participant 1 / Leader)
    if (!isset($_FILES['payment_pic']) || $_FILES['payment_pic']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Participant 1 payment receipt screenshot is required.'], 400);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'gif'];
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Process Receipt 1
    $file1 = $_FILES['payment_pic'];
    $ext1 = strtolower(pathinfo($file1['name'], PATHINFO_EXTENSION));
    if (!in_array($ext1, $allowedExtensions)) {
        jsonResponse(['error' => 'Invalid file type for Receipt 1. Please upload an image (JPG, PNG, WEBP) or PDF.'], 400);
    }
    if ($file1['size'] > 10 * 1024 * 1024) {
        jsonResponse(['error' => 'Receipt 1 file size exceeds limit (Max 10MB).'], 400);
    }
    $fileName1 = 'payment1-' . time() . '-' . mt_rand(100000, 999999) . '.' . $ext1;
    $targetPath1 = $uploadDir . $fileName1;
    if (!move_uploaded_file($file1['tmp_name'], $targetPath1)) {
        jsonResponse(['error' => 'Failed to save Receipt 1. Please check server folder permissions.'], 500);
    }

    // Process Receipt 2 (Participant 2 - if provided or fallback to receipt 1)
    $fileName2 = $fileName1;
    if (isset($_FILES['payment_pic2']) && $_FILES['payment_pic2']['error'] === UPLOAD_ERR_OK) {
        $file2 = $_FILES['payment_pic2'];
        $ext2 = strtolower(pathinfo($file2['name'], PATHINFO_EXTENSION));
        if (!in_array($ext2, $allowedExtensions)) {
            jsonResponse(['error' => 'Invalid file type for Receipt 2. Please upload an image (JPG, PNG, WEBP) or PDF.'], 400);
        }
        if ($file2['size'] > 10 * 1024 * 1024) {
            jsonResponse(['error' => 'Receipt 2 file size exceeds limit (Max 10MB).'], 400);
        }
        $fileName2 = 'payment2-' . time() . '-' . mt_rand(100000, 999999) . '.' . $ext2;
        $targetPath2 = $uploadDir . $fileName2;
        if (!move_uploaded_file($file2['tmp_name'], $targetPath2)) {
            jsonResponse(['error' => 'Failed to save Receipt 2. Please check server folder permissions.'], 500);
        }
    }

    $db = getDatabase();

    // Auto-migrate schema in case columns are missing
    try { $db->exec("ALTER TABLE teams ADD COLUMN payment_pic2 VARCHAR(255) NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE teams ADD COLUMN payment_ref2 VARCHAR(100) NULL"); } catch (Exception $e) {}

    // Check duplicate team name
    $checkStmt = $db->prepare("SELECT id FROM teams WHERE LOWER(team_name) = LOWER(:teamName) LIMIT 1");
    $checkStmt->execute([':teamName' => $teamName]);
    if ($checkStmt->fetch()) {
        if (file_exists($targetPath1)) @unlink($targetPath1);
        if ($fileName2 !== $fileName1 && file_exists($uploadDir . $fileName2)) @unlink($uploadDir . $fileName2);
        jsonResponse(['error' => 'A team with this name already exists! Please choose another name.'], 400);
    }

    // Insert Team
    $insertStmt = $db->prepare("
        INSERT INTO teams (
            team_name, password, college, track,
            leader_name, leader_email, leader_phone, leader_dept,
            member2_name, member2_email, member2_phone, member2_dept,
            payment_pic, payment_ref, payment_pic2, payment_ref2, status
        ) VALUES (
            :team_name, :password, :college, :track,
            :leader_name, :leader_email, :leader_phone, :leader_dept,
            :member2_name, :member2_email, :member2_phone, :member2_dept,
            :payment_pic, :payment_ref, :payment_pic2, :payment_ref2, 'pending'
        )
    ");

    $insertStmt->execute([
        ':team_name'     => $teamName,
        ':password'      => $password,
        ':college'       => $college,
        ':track'         => $track,
        ':leader_name'   => $leaderName,
        ':leader_email'  => $leaderEmail,
        ':leader_phone'  => $leaderPhone,
        ':leader_dept'   => $leaderDept,
        ':member2_name'  => $member2Name,
        ':member2_email' => $member2Email,
        ':member2_phone' => $member2Phone,
        ':member2_dept'  => $member2Dept,
        ':payment_pic'   => $fileName1,
        ':payment_ref'   => $paymentRef,
        ':payment_pic2'  => $fileName2,
        ':payment_ref2'  => $paymentRef2
    ]);

    $insertedId = (int)$db->lastInsertId();
    $passId = 'HF26-' . (1000 + $insertedId);

    // Update Pass ID
    $updateStmt = $db->prepare("UPDATE teams SET pass_id = :passId WHERE id = :id");
    $updateStmt->execute([':passId' => $passId, ':id' => $insertedId]);

    jsonResponse([
        'success' => true,
        'message' => 'Team registered successfully! Please wait for committee approval.',
        'teamId'  => $insertedId,
        'passId'  => $passId
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error during registration: ' . $e->getMessage()], 500);
}
