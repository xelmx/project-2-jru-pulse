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

    // Step 1: ALWAYS check if the identifier email belongs to a registered student first.  "single source of truth" rule.
    $stmt_student = $db->prepare("SELECT id FROM students WHERE email = :email LIMIT 1");
    $stmt_student->execute([':email' => $identifier_email]);
    $student_record = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if ($student_record) {
        // --- THIS PERSON IS A VERIFIED STUDENT ---
        $student_id = $student_record['id'];
        
        // Now, find or create their single entry in the respondents table.
        $stmt_find_resp = $db->prepare("SELECT id FROM respondents WHERE student_id = :student_id LIMIT 1");
        $stmt_find_resp->execute([':student_id' => $student_id]);
        $respondent_record = $stmt_find_resp->fetch(PDO::FETCH_ASSOC);

        if ($respondent_record) {
            // Found them. Use the existing ID.
            $respondent_id = $respondent_record['id'];
        } else {
            // Not found. Create a new respondent record linked to their student ID.
            $stmt_create_resp = $db->prepare("INSERT INTO respondents (respondent_type, student_id) VALUES ('student', :student_id)");
            $stmt_create_resp->execute([':student_id' => $student_id]);
            $respondent_id = $db->lastInsertId();
        }

    } else {
        // --- THIS PERSON IS A GUEST ---
        // Safety check: If the front-end THOUGHT they were a student but they're not in the DB, it's an error.
        if ($type === 'student') {
            respond(false, "This student email is not registered. Please contact an administrator or sign in as a guest.", null, 403);
        }

        // Step 2a: Find or create their identity in the new 'guests' table.
        $stmt_find_guest = $db->prepare("SELECT id FROM guests WHERE email = :email LIMIT 1");
        $stmt_find_guest->execute([':email' => $identifier_email]);
        $guest_record = $stmt_find_guest->fetch(PDO::FETCH_ASSOC);
        $guest_id = null;

        if ($guest_record) {
            // Found an existing guest.
            $guest_id = $guest_record['id'];
        } else {
            // New guest. Create them.
            if (!isset($data['first_name']) || !isset($data['last_name'])) {
                 respond(false, "First name and last name are required for new guests.", null, 400);
            }
            $stmt_create_guest = $db->prepare("INSERT INTO guests (first_name, last_name, email) VALUES (:first_name, :last_name, :email)");
            $stmt_create_guest->execute([
                ':first_name' => trim($data['first_name']),
                ':last_name' => trim($data['last_name']),
                ':email' => $identifier_email
            ]);
            $guest_id = $db->lastInsertId();
        }

        // Step 2b: Now that we have a guest_id, find or create their entry in the respondents table.
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

    if ($respondent_id) {
        respond(true, "Respondent verified successfully.", ["respondent_id" => $respondent_id]);
    } else {
        // This should theoretically never happen, but it's a good safety net.
        throw new Exception("Critical error: Could not find or create a respondent ID.");
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(false, "A database error occurred: " . $e->getMessage(), null, 500);
}
?>