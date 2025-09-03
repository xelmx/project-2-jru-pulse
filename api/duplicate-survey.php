<?php

// --- SECURITY: Only admins can access this API ---
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to access this resource."]);
    exit;
}

header("Content-Type: apllication/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, x-requested-with");

if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/connection.php';

//Standard function to send back responses.
function respond($success, $message, $data = null, $code = 200){
    http_response_code($code);
    echo json_encode ([
        "success" => (bool)$success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Only allow POST requests for this action.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, "Invalid request method.", null, 405);
}

// Make sure the ID of the survey to copy is provided in the URL.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    respond(false, "A valid source survey ID is required.", null, 400);
}
$source_survey_id = intval($_GET['id']);

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Fetch all data for the original survey.
    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = :id");
    $stmt->execute([':id' => $source_survey_id]);
    $original_survey = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no survey was found, send a clear error.
    if (!$original_survey) {
        respond(false, "The survey you are trying to copy does not exist.", null, 404);
    }

    // 2. Prepare the title for the new survey.
    $new_title = $original_survey['title'] . " (Copy)";

    // 3. Insert the new record. We reset 'status' to 'draft' and 'is_locked' to 0.
    $insert_query = "INSERT INTO surveys (title, description, office_id, service_id, status, questions_json, is_locked) 
                     VALUES (:title, :description, :office_id, :service_id, 'draft', :questions_json, 0)";
    
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->execute([
        ':title' => $new_title,
        ':description' => $original_survey['description'],
        ':office_id' => $original_survey['office_id'],
        ':service_id' => $original_survey['service_id'],
        ':questions_json' => $original_survey['questions_json']
    ]);

    // 4. Get the ID of the new copy.
    $new_survey_id = $db->lastInsertId();

    if ($new_survey_id) {
        // Success! Send back the new ID so the JavaScript knows where to go.
        respond(true, "Survey duplicated successfully.", ["new_survey_id" => $new_survey_id]);
    } else {
        // This is a fallback if the insert fails for some reason.
        throw new Exception("Failed to create the new survey record.");
    }

} catch (Exception $e) {
    // Catch any other errors and report them.
    respond(false, "An error occurred: " . $e->getMessage(), null, 500);
}

?>