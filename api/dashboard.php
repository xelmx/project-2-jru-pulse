<?php
session_start();
header('Content-Type: application/json');

require_once '../config/connection.php';

// Standard respond helper function for consistent API output
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]);
    exit;
}

try {
    // Standard database connection
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed: " . $e->getMessage(), null, 500);
}

// --- 1. Date Filter Logic ---
$period = $_GET['period'] ?? 'this_week';
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;

// This block now ONLY sets the string dates if a preset is used.
if (!$startDate || !$endDate) {
    $end_dt_preset = new DateTime();
    switch ($period) {
        case 'this_month':
            $start_dt_preset = new DateTime('first day of this month');
            break;
        case 'this_quarter':
            $month = $end_dt_preset->format('n');
            $quarter = ceil($month / 3);
            $start_month = ($quarter - 1) * 3 + 1;
            $start_dt_preset = new DateTime(date('Y') . "-$start_month-01");
            break;
        case 'this_year':
            $start_dt_preset = new DateTime('first day of January this year');
            break;
        case 'all_time':
            $start_dt_preset = new DateTime('2020-01-01'); // A reasonable "all time" start
            break;
        case 'this_week':
        default:
            $start_dt_preset = new DateTime('monday this week');
            break;
    }
    // Set the string dates from the objects we just created
    $startDate = $start_dt_preset->format('Y-m-d');
    $endDate = $end_dt_preset->format('Y-m-d');
}

// $startDate and $endDate are guaranteed to be valid date strings.
$endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
$dateFilterClause = "sr.submitted_at BETWEEN :startDate AND :endDatePlusOne";
$params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];


// Use DateTime objects for accurate comparison and add one day to the end date for BETWEEN clause
$endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
$dateFilterClause = "sr.submitted_at BETWEEN :startDate AND :endDatePlusOne";
$params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];

// --- 2. Data Calculation ---
$data = [];

try {
    // Step 1: Fetch raw JSON data with a simpler query
    $query = "
        SELECT 
            sr.answers_json,
            s.questions_json,
            sr.submitted_at
        FROM survey_responses sr
        JOIN surveys s ON sr.survey_id = s.id
        WHERE {$dateFilterClause}
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize all metrics
    $data['total_responses'] = count($results);
    $data['overall_satisfaction'] = 0;
    $data['rating_distribution'] = array_fill_keys([5, 4, 3, 2, 1], 0);
    $data['service_performance'] = [];
    $data['trends_labels'] = [];
    $data['trends_data'] = [];
    
    $allRatings = []; // This will hold our processed ratings

    if ($data['total_responses'] > 0) {
        // Step 2: Process the JSON data in PHP
        foreach ($results as $row) {
            $questions = json_decode($row['questions_json'], true);
            $answers = json_decode($row['answers_json'], true);
            
            // Create a quick lookup map of question types and titles by ID
            $questionMap = [];
            foreach ($questions as $q) {
                if (isset($q['id'])) {
                    $questionMap[$q['id']] = [
                        'type' => $q['type'] ?? 'unknown',
                        'title' => $q['title'] ?? 'Untitled Question'
                    ];
                }
            }
            
            // Iterate through answers and extract only the ratings from likert/rating types
            foreach ($answers as $ans) {
                $q_id = $ans['question_id'];
                if (isset($questionMap[$q_id])) {
                    $questionInfo = $questionMap[$q_id];
                    if ($questionInfo['type'] === 'likert' || $questionInfo['type'] === 'rating') {
                        // Sanitize answer to ensure it's a numeric value
                        $ratingValue = filter_var($ans['answer'], FILTER_VALIDATE_INT);
                        if ($ratingValue !== false && $ratingValue >= 1 && $ratingValue <= 5) {
                            $allRatings[] = [
                                'rating' => $ratingValue,
                                'question_title' => $questionInfo['title'],
                                'submission_date' => (new DateTime($row['submitted_at']))->format('Y-m-d')
                            ];
                        }
                    }
                }
            }
        }
        
        // Step 3: Aggregate metrics from the processed data
        if (!empty($allRatings)) {
            // Metric 1: Overall Satisfaction
            $totalScore = 0;
            foreach ($allRatings as $row) { $totalScore += $row['rating']; }
            $data['overall_satisfaction'] = round($totalScore / count($allRatings), 1);

            // Metric 2: Rating Distribution
            foreach ($allRatings as $row) { $data['rating_distribution'][$row['rating']]++; }

            // Metric 3: Service Performance
            $performance = [];
            foreach ($allRatings as $row) {
                if (!isset($performance[$row['question_title']])) {
                    $performance[$row['question_title']] = ['total' => 0, 'count' => 0];
                }
                $performance[$row['question_title']]['total'] += $row['rating'];
                $performance[$row['question_title']]['count']++;
            }
            foreach ($performance as $title => $values) {
                $data['service_performance'][] = [
                    'label' => $title, 
                    'value' => round($values['total'] / $values['count'], 1)
                ];
            }

            // Metric 4: Satisfaction Trends
            $trends = [];
            foreach ($allRatings as $row) {
                $date = $row['submission_date'];
                if (!isset($trends[$date])) {
                    $trends[$date] = ['total' => 0, 'count' => 0];
                }
                $trends[$date]['total'] += $row['rating'];
                $trends[$date]['count']++;
            }
            ksort($trends); // Sort by date
            foreach($trends as $date => $values) {
                $data['trends_labels'][] = $date;
                $data['trends_data'][] = round($values['total'] / $values['count'], 1);
            }
        }
    }
    
    // Pass back the date range for the UI
    $data['startDate'] = $startDate;
    $data['endDate'] = $endDate;

    respond(true, "Dashboard data retrieved successfully.", $data);

} catch (PDOException $e) {
    respond(false, "API Error: " . $e->getMessage(), null, 500);
} catch (Exception $e) {
    respond(false, "An unexpected error occurred: " . $e->getMessage(), null, 500);
}

?>