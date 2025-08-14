<?php // api/submit-response.php

header("Content-Type: application/json");
// Add your other standard CORS headers here
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/connection.php';

// A simple function to send back a JSON response.
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => $success, "message" => $message, "data" => $data]);
    exit;
}

// We only accept POST requests for this action.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, "Invalid request method.", null, 405);
}

// Get the raw POST data from the JavaScript fetch call.
$data = json_decode(file_get_contents("php://input"), true);

// --- Data Validation ---
// Make sure we have the essential pieces of information.
if (!isset($data['survey_id']) || !isset($data['respondent']) || !isset($data['answers'])) {
    respond(false, "Incomplete data. survey_id, respondent, and answers are required.", null, 400);
}

$survey_id = $data['survey_id'];
$respondent_info = $data['respondent'];
$answers_json = json_encode($data['answers']); // Convert the answers array to a JSON string for DB storage.


// --- Database Operations ---
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();

    // --- Step 1: Create the Respondent Record ---
    
    // Default values
    $type_to_insert = 'non-student'; // Default to non-student
    $student_id_to_insert = null;
    $email_to_insert = null;

    // Check the type sent from JavaScript
    if (isset($respondent_info['type']) && $respondent_info['type'] === 'student') {
        $type_to_insert = 'student';
        // Future logic to find student_id will go here.
        // For now, student_id_to_insert remains null.
    } else {
        // This is the 'non-student' path.
        // It's safer to check if the 'identifier' (email) exists.
        if (isset($respondent_info['identifier'])) {
            $email_to_insert = $respondent_info['identifier'];
        }
    }

    $respondent_query = "INSERT INTO respondents (respondent_type, student_id, identifier_email) VALUES (:type, :student_id, :email)";
    $respondent_stmt = $db->prepare($respondent_query);
    $respondent_stmt->execute([
        ':type' => $type_to_insert, // Use our safe, processed variable
        ':student_id' => $student_id_to_insert,
        ':email' => $email_to_insert
    ]);

    $respondent_id = $db->lastInsertId();

    // The rest of the script (Step 2 and Step 3) remains exactly the same.
    // --- Step 2: Create the Survey Response Record ---
    $response_query = "INSERT INTO survey_responses (survey_id, respondent_id, answers_json) VALUES (:survey_id, :respondent_id, :answers_json)";
    $response_stmt = $db->prepare($response_query);
    $response_stmt->execute([
        ':survey_id' => $survey_id,
        ':respondent_id' => $respondent_id,
        ':answers_json' => $answers_json
    ]);

    // --- Step 3: Lock the Survey ---
    $lock_query = "UPDATE surveys SET is_locked = 1 WHERE id = :survey_id";
    $lock_stmt = $db->prepare($lock_query);
    $lock_stmt->execute([':survey_id' => $survey_id]);

    $db->commit();

    respond(true, "Thank You For Your Feedback!");

} catch (Exception $e) {
    $db->rollBack();
    respond(false, "An error occurred while saving your response: " . $e->getMessage(), null, 500);
}

?>