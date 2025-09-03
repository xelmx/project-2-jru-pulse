<?php 

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// --- Data Validation ---
if (!isset($data['survey_id']) || !isset($data['respondent']['id']) || !isset($data['answers'])) {
    respond(false, "Incomplete data. survey_id, respondent.id, and answers are required.", null, 400);
}

// --- Get the PRE-EXISTING data from the JavaScript state ---
$survey_id = $data['survey_id'];
$respondent_id = $data['respondent']['id']; // This is the key change!
$answers_json = json_encode($data['answers']);

// --- Database Operations ---
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();

    // --- Step 1 (Formerly Step 2): Create the Survey Response Record ---
    // This query now uses the correct, pre-existing respondent_id.
    $response_query = "INSERT INTO survey_responses (survey_id, respondent_id, answers_json) VALUES (:survey_id, :respondent_id, :answers_json)";
    $response_stmt = $db->prepare($response_query);
    $response_stmt->execute([
        ':survey_id' => $survey_id,
        ':respondent_id' => $respondent_id,
        ':answers_json' => $answers_json
    ]);

    // --- Step 2 : Lock the Survey ---
    // This ensures that once a survey gets its first response, its structure cannot be changed.
    $lock_query = "UPDATE surveys SET is_locked = 1 WHERE id = :survey_id";
    $lock_stmt = $db->prepare($lock_query);
    $lock_stmt->execute([':survey_id' => $survey_id]);

    $db->commit();

    respond(true, "Thank You For Your Feedback!");

} catch (Exception $e) {
    // If anything fails, roll back the entire transaction to prevent partial data.
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(false, "An error occurred while saving your response: " . $e->getMessage(), null, 500);
}

?>