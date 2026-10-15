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

    // Validation
    if (empty($teamName) || empty($password) || empty($college) ||
        empty($leaderName) || empty($leaderEmail) ||
        empty($member2Name) || empty($member2Email)) {
        jsonResponse(['error' => 'Please provide all required fields for both team members.'], 400);
    }

    if (!filter_var($leaderEmail, FILTER_VALIDATE_EMAIL) || !filter_var($member2Email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Please enter valid email addresses for both participants.'], 400);
    }

    // Handle Payment Receipt Upload
    if (!isset($_FILES['payment_pic']) || $_FILES['payment_pic']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Payment receipt screenshot is required. Please upload your proof of payment.'], 400);
    }

    $file = $_FILES['payment_pic'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'gif'];
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($extension, $allowedExtensions)) {
        jsonResponse(['error' => 'Invalid file type. Please upload an image (JPG, PNG, WEBP) or PDF receipt.'], 400);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        jsonResponse(['error' => 'File size exceeds limit (Max 10MB).'], 400);
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = 'payment-' . time() . '-' . mt_rand(100000, 999999) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        jsonResponse(['error' => 'Failed to save uploaded receipt. Please check folder permissions.'], 500);
    }

    $db = getDatabase();

    // Check duplicate team name
    $checkStmt = $db->prepare("SELECT id FROM teams WHERE LOWER(team_name) = LOWER(:teamName) LIMIT 1");
    $checkStmt->execute([':teamName' => $teamName]);
    if ($checkStmt->fetch()) {
        if (file_exists($targetPath)) {
            @unlink($targetPath);
        }
        jsonResponse(['error' => 'A team with this name already exists! Please choose another name.'], 400);
    }

    // Insert Team
    $insertStmt = $db->prepare("
        INSERT INTO teams (
            team_name, password, college, track,
            leader_name, leader_email, leader_phone, leader_dept,
            member2_name, member2_email, member2_phone, member2_dept,
            payment_pic, payment_ref, status
        ) VALUES (
            :team_name, :password, :college, :track,
            :leader_name, :leader_email, :leader_phone, :leader_dept,
            :member2_name, :member2_email, :member2_phone, :member2_dept,
            :payment_pic, :payment_ref, 'pending'
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
        ':payment_pic'   => $fileName,
        ':payment_ref'   => $paymentRef
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
