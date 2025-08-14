<?php
// --- api/dashboard.php ---

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

if (!$startDate || !$endDate) {
    $end_dt = new DateTime();
    switch ($period) {
        case 'this_month':
            $start_dt = new DateTime('first day of this month');
            break;
        case 'this_quarter':
            $month = $end_dt->format('n');
            $quarter = ceil($month / 3);
            $start_month = ($quarter - 1) * 3 + 1;
            $start_dt = new DateTime(date('Y') . "-$start_month-01");
            break;
        case 'this_year':
            $start_dt = new DateTime('first day of January this year');
            break;
        case 'all_time':
            $start_dt = new DateTime('2020-01-01'); // A reasonable "all time" start
            break;
        case 'this_week':
        default:
            $start_dt = new DateTime('monday this week');
            break;
    }
    $startDate = $start_dt->format('Y-m-d');
    $endDate = $end_dt->format('Y-m-d');
}
$endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
$dateFilterClause = "WHERE sr.submitted_at BETWEEN :startDate AND :endDatePlusOne";
$params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];


// --- 2. Data Calculation ---
$data = [];

try {
    // Metric 1: Total Responses
    $stmt = $db->prepare("SELECT COUNT(id) as total FROM survey_responses sr $dateFilterClause");
    $stmt->execute($params);
    $data['total_responses'] = (int) $stmt->fetchColumn();

    // Initialize all metrics to avoid errors if there are no responses
    $data['overall_satisfaction'] = 0;
    $data['rating_distribution'] = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $data['service_performance'] = [];
    $data['trends_labels'] = [];
    $data['trends_data'] = [];

    if ($data['total_responses'] > 0) {
        // --- This is the advanced query to extract all numeric ratings from the JSON ---
        $query = "
            WITH AllRatings AS (
                SELECT 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(jt.answer_obj, '$.answer')) AS SIGNED) as rating,
                    JSON_UNQUOTE(JSON_EXTRACT(q.question_obj, '$.title')) as question_title,
                    DATE(sr.submitted_at) as submission_date
                FROM survey_responses sr
                JOIN surveys s ON sr.survey_id = s.id,
                     JSON_TABLE(sr.answers_json, '$[*]' COLUMNS (
                        question_id INT PATH '$.question_id',
                        answer_obj JSON PATH '$'
                     )) AS jt,
                     JSON_TABLE(s.questions_json, '$[*]' COLUMNS (
                        id INT PATH '$.id',
                        type VARCHAR(20) PATH '$.type',
                        question_obj JSON PATH '$'
                     )) AS q
                WHERE jt.question_id = q.id AND q.type IN ('likert', 'rating') AND $dateFilterClause
            )
            SELECT * FROM AllRatings;
        ";
        
        // We need to re-bind params since we can't use named params inside JSON_TABLE in some versions
        $stmt = $db->prepare(str_replace(array_keys($params), '?', $query));
        $stmt->execute(array_values($params));
        $allRatings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($allRatings)) {
            // Metric 2: Overall Satisfaction
            $totalScore = 0;
            foreach ($allRatings as $row) { $totalScore += $row['rating']; }
            $data['overall_satisfaction'] = round($totalScore / count($allRatings), 1);

            // Metric 3: Rating Distribution
            foreach ($allRatings as $row) { $data['rating_distribution'][$row['rating']]++; }

            // Metric 4: Service Performance
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

            // Metric 5: Satisfaction Trends
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
}
?>