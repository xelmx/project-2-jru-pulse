<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

require_once '../config/connection.php';

//Helper function for sending response
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => $success, 
                    "message" => $message, 
                    "data" => $data]);
                    exit;
}

    if ($_SERVER["REQUEST_METHOD"] !== "POST"){
        respond(false, "Invalid request method.", null, 405);
    }

    $data = json_decode(file_get_contents("php://input"), true);

    //Basic validation
    if(isset($data['type']) || !isset($data['identifier'])) {
        respond(false, "Incomplete data. 'type' and 'identifier' are required.", null, 400);
    }

    $type = $data['type'];
    $identifier_email = $data['identifier'];
    $full_name = $data['name'] ?? null;

    try {
        $database = new Database();
        $db = $database->getConnection();

        $student_id_to_insert = null;

        if ($type === 'student'){
            //If student. We must verify them on our master list
            $stmt = $db->prepare("SELECT id 
                                FROM students
                                WHERE email=email");
            $stmt->execute([':email' => $identifier_email]);
            $student_record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student_record) {
                //SUCCESS: Student exist in the database
                $student_id_to_insert = $student_record['id'];
            } else {
                respond(false, "This student email is not registered for surveys. Plase contact admin.",
                        null, 403);
            }
        //FOr non-student, we dont need to do any special verification
        $respondont_query = "INSERT INTO respondents(respondent_type, student_id, identifier_email)  
                            VALUES (:type, :student_id, :email)";
        $respondent_stmt = $db->prepare($respondent_query);

        $email_for_non_student = ($type === 'non-student') ? $identifier_email : null;

        $respondent_stmt->execute([
            ':type' => $type,
            'student_id' => $student_id_to_insert,
            ':email' => $email_for_non_student
        ]);

        $new_respondent_id = $db->lastInserId();

        if ($new_respondent_id) {
            respond(true, "Respondent created successfully.", ["respondent_id" => $new_respondent_id]); //Success
        } else {
            throw new Exception("Failed to create respondent record in the database.");
        }

        }
        
    } catch (Exception $e){
            respond(false, "A database error occured: " . $e->getMessage(), null, 500);
    }
        
    
?>