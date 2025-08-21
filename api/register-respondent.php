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

    $stmt_student = $db->prepare("SELECT id FROM students WHERE email = :email LIMIT 1");
    $stmt_student->execute([':email' => $identifier_email]);
    $student_record = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if ($student_record) {
        // --- THIS PERSON IS A VERIFIED STUDENT ---
        $student_id = $student_record['id'];
        $stmt_find_resp = $db->prepare("SELECT id FROM respondents WHERE student_id = :student_id LIMIT 1");
        $stmt_find_resp->execute([':student_id' => $student_id]);
        $respondent_record = $stmt_find_resp->fetch(PDO::FETCH_ASSOC);
        if ($respondent_record) {
            $respondent_id = $respondent_record['id'];
        } else {
            $stmt_create_resp = $db->prepare("INSERT INTO respondents (respondent_type, student_id) VALUES ('student', :student_id)");
            $stmt_create_resp->execute([':student_id' => $student_id]);
            $respondent_id = $db->lastInsertId();
        }
    } else {
        // --- THIS PERSON IS A GUEST ---
        if ($type === 'student') {
            respond(false, "This student email is not registered. Please sign in as a guest.", null, 403);
        }

        $stmt_find_guest = $db->prepare("SELECT id FROM guests WHERE email = :email LIMIT 1");
        $stmt_find_guest->execute([':email' => $identifier_email]);
        $guest_record = $stmt_find_guest->fetch(PDO::FETCH_ASSOC);
        $guest_id = null;

        if ($guest_record) {
            $guest_id = $guest_record['id'];
        } else {
            // --- MODIFIED SECTION ---
            // We now also require a 'role' for new guests.
            if (!isset($data['first_name']) || !isset($data['last_name']) || !isset($data['role'])) {
                 respond(false, "First name, last name, and role are required for new guests.", null, 400);
            }
            // The INSERT query now includes the 'role' column.
            $stmt_create_guest = $db->prepare("INSERT INTO guests (first_name, last_name, email, role) VALUES (:first_name, :last_name, :email, :role)");
            $stmt_create_guest->execute([
                ':first_name' => trim($data['first_name']),
                ':last_name' => trim($data['last_name']),
                ':email' => $identifier_email,
                ':role' => trim($data['role']) // Save the new role data
            ]);
            $guest_id = $db->lastInsertId();
            // --- END OF MODIFIED SECTION ---
        }

        $stmt_find_resp = $db->prepare("SELECT id FROM respondents WHERE guest_id = :guest_id LIMIT 1");
        $stmt_find_resp->execute([':guest_id' => $guest_id]);
        $respondent_record = $stmt_find_resp->fetch(PDO::FETCH_ASSOC);
        if ($respondent_record) {
            $respondent_id = $respondent_record['id'];
        } else {
            $stmt_create_resp = $db->prepare("INSERT INTO respondents (respondent_type, guest_id) VALUES ('guest', :guest_id)");
            $stmt_create_resp->execute([':guest_id' => $guest_id]);
            $respondent_id = $db->lastInsertId();
        }
    }

    $db->commit();
    respond(true, "Respondent verified successfully.", ["respondent_id" => $respondent_id]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    respond(false, "A database error occurred: " . $e->getMessage(), null, 500);
}
?>