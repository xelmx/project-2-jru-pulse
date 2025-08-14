<?php // api/register-respondent.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config/connection.php';

function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => $success, "message" => $message, "data" => $data]);
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
$identifier_email = $data['identifier'];

try {
    $database = new Database();
    $db = $database->getConnection();

    $student_id_to_insert = null;
    $email_for_non_student = null;

    if ($type === 'student') {
        $stmt = $db->prepare("SELECT id FROM students WHERE email = :email"); // verify the student against the master list.
        $stmt->execute([':email' => $identifier_email]);
        $student_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student_record) {
            $student_id_to_insert = $student_record['id'];
        } else {
            respond(false, "This student email is not registered. Please contact an administrator.", null, 403);
        }
    } else {
        $email_for_non_student = $identifier_email; // This is the non-student path. Just save their email.
    }

    $respondent_query = "INSERT INTO respondents (respondent_type, student_id, identifier_email) VALUES (:type, :student_id, :email)"; // Now, create the official Respondent record.
    $respondent_stmt = $db->prepare($respondent_query);
    $respondent_stmt->execute([
        ':type' => $type,
        ':student_id' => $student_id_to_insert,
        ':email' => $email_for_non_student
    ]);

    $new_respondent_id = $db->lastInsertId();

    if ($new_respondent_id) {
        respond(true, "Respondent created successfully.", ["respondent_id" => $new_respondent_id]);
    } else {
        throw new Exception("Failed to create respondent record.");
    }

} catch (Exception $e) {
    respond(false, "A database error occurred: " . $e->getMessage(), null, 500);
}
?>