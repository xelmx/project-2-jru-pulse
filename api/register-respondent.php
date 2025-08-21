<?php


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config/connection.php';

function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, "Invalid request method.", null, 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['type']) || !isset($data['identifier'])) {
    respond(false, "Incomplete data. 'type' and 'identifier' are required.", null, 400);
}

$type = $data['type'];
$identifier_email = trim($data['identifier']);
$respondent_id = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // First, always check if this email belongs to a registered student.
    $stmt_student = $db->prepare("SELECT id FROM students WHERE email = :email");
    $stmt_student->execute([':email' => $identifier_email]);
    $student_record = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if ($student_record) {

        // --- THIS PERSON IS A VERIFIED STUDENT ---
        $student_id = $student_record['id'];

        // Find the respondent record linked to this student's master ID.
        $stmt_find_resp = $db->prepare("SELECT id FROM respondents WHERE student_id = :student_id");
        $stmt_find_resp->execute([':student_id' => $student_id]);
        $respondent_record = $stmt_find_resp->fetch(PDO::FETCH_ASSOC);

        if ($respondent_record) {
            $respondent_id = $respondent_record['id'];
        } else {
            // First time this student is taking a survey. Create their record.
            $stmt_create_resp = $db->prepare("INSERT INTO respondents (respondent_type, student_id) VALUES ('student', :student_id)");
            $stmt_create_resp->execute([':student_id' => $student_id]);
            $respondent_id = $db->lastInsertId();
        }

    } else {
        // --- THIS PERSON IS A GUEST ---
        if ($type === 'student') {
            respond(false, "This student email is not registered. Please contact an administrator.", null, 403);
        }

        // Find the guest record using their unique email address.
        $stmt_find_resp = $db->prepare("SELECT id FROM respondents WHERE identifier_email = :email AND respondent_type = 'non-student'");
        $stmt_find_resp->execute([':email' => $identifier_email]);
        $respondent_record = $stmt_find_resp->fetch(PDO::FETCH_ASSOC);

        if ($respondent_record) {
            $respondent_id = $respondent_record['id'];
        } else {
            // First time this guest is taking a survey. Create their record.
            $stmt_create_resp = $db->prepare("INSERT INTO respondents (respondent_type, identifier_email) VALUES ('non-student', :email)");
            $stmt_create_resp->execute([':email' => $identifier_email]);
            $respondent_id = $db->lastInsertId();
        }
    }

    $db->commit();

    if ($respondent_id) {
        respond(true, "Respondent verified successfully.", ["respondent_id" => $respondent_id]);
    } else {
        throw new Exception("Critical error: Could not find or create a respondent ID.");
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(false, "A database error occurred: " . $e->getMessage(), null, 500);
}
?>