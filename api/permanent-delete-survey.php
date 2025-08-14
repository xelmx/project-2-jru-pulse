<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/connection.php';
function respond($s, $m, $d=null, $c=200){http_response_code($c);echo json_encode(["success"=>(bool)$s,"message"=>$m,"data"=>$d]);exit;}

try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    respond(false, "DB Connection Failed", null, 500);
}

if ($_SERVER["REQUEST_METHOD"] == 'DELETE') { //// This script only handles DELETE requests
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        respond(false, "A valid Survey ID is required for deletion.", null, 400);
    }
    $id = intval($_GET['id']);

    $db->beginTransaction(); /// Use a transaction for safety
    try {
        
        $stmt_responses = $db->prepare("DELETE FROM survey_responses WHERE survey_id = :survey_id"); // Delete all responses associated with this survey.
        $stmt_responses->bindValue(':survey_id', $id);
        $stmt_responses->execute();

        $stmt_survey = $db->prepare("DELETE FROM surveys WHERE id = :id"); //Delete the survey itself.
        $stmt_survey->bindValue(':id', $id);
        $stmt_survey->execute();

        $db->commit(); // If both queries succeed, commit the transaction.
        respond(true, "Survey and all its responses have been permanently deleted.");

    } catch (PDOException $e) {
        // If anything fails, roll back all changes.
        $db->rollBack();
        respond(false, "Database error during permanent deletion: " . $e->getMessage(), null, 500);
    }
} else {
    respond(false, "Method Not Allowed.", null, 405);
}
?>