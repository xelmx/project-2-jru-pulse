<?php
// --- api/dashboard.php (DEFINITIVE, FINAL PREDICTIVE VERSION) ---

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

// --- HELPER FUNCTIONS ---
function respond($success, $message, $data = null, $code = 200) { 
    http_response_code($code); 
    echo json_encode(["success" => (bool)$success, 
    "message" => $message, 
    "data" => $data]); exit; 
}

function calculate_change($current, $previous) { 
    if ($previous == 0) return null; 
    return round((($current - $previous) / $previous) * 100); 
}

function linear_regression($data) {
    $n = count($data); if ($n < 2) return null;
    $sum_x = 0; $sum_y = 0; $sum_xy = 0; 
    $sum_x_sq = 0;
    foreach ($data as $point) { $x = $point['x']; 
        $y = $point['y']; 
        $sum_x += $x; 
        $sum_y += $y; 
        $sum_xy += $x * $y; 
        $sum_x_sq += $x * $x; }

    $denominator = ($n * $sum_x_sq) - ($sum_x * $sum_x); 
    if ($denominator == 0) return null;
    $slope = (($n * $sum_xy) - ($sum_x * $sum_y)) / $denominator;
    $intercept = ($sum_y - ($slope * $sum_x)) / $n;
    return ['slope' => $slope, 'intercept' => $intercept];
}

function callAiApi($texts) {
    if (empty($texts)) return null;
    $ai_api_url = 'http://127.0.0.1:8000/analyze';
    $postData = json_encode(['texts' => $texts]);
    $ch = curl_init($ai_api_url);
    curl_setopt_array($ch, [ CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 300 ]);
    $response = curl_exec($ch); curl_close($ch);
    return $response ? json_decode($response, true) : null;
}

// --- DB CONNECTION & USER PERMISSIONS ---
try { 
    $database = new Database(); 
    $db = $database->getConnection(); 
} catch (Exception $e) {
    respond(false, "Database connection failed: " . $e->getMessage(), null, 500); 
}

if (!isset($_SESSION['user_data'])) { 
    respond(false, "Authentication required.", null, 401); 
}

$user_role = $_SESSION['user_data']['role'];
$user_office_id = $_SESSION['user_data']['office_id'] ?? null;

// --- DATE FILTER LOGIC ---
$period = $_GET['period'] ?? 'this_week';
$startDate = $_GET['startDate'] ?? null; $endDate = $_GET['endDate'] ?? null;

if (!$startDate || !$endDate) {
    $end_dt_preset = new DateTime();
    switch ($period) {
        case 'this_month': 
            $start_dt_preset = new DateTime('first day of this month'); 
            break;
        case 'this_quarter': 
            $month = $end_dt_preset->format('n'); $quarter = ceil($month / 3); 
            $start_month = ($quarter - 1) * 3 + 1; $start_dt_preset = new DateTime(date('Y') . "-$start_month-01"); 
            break;
        case 'this_year': 
            $start_dt_preset = new DateTime('first day of January this year'); 
            break;
        case 'all_time': 
            $start_dt_preset = new DateTime('2020-01-01'); 
            break;
        default: 
            $start_dt_preset = new DateTime('monday this week'); 
            break;
    }
    $startDate = $start_dt_preset->format('Y-m-d'); $endDate = $end_dt_preset->format('Y-m-d');
}

// --- REUSABLE DATA AGGREGATION FUNCTION ---
function getAggregatedDataForPeriod($db, $startDate, $endDate, $user_role, $user_office_id) {
    $endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
    $params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];
    $query = "SELECT s.office_id, o.name as office_name, sr.answers_json, s.questions_json, sr.submitted_at 
              FROM survey_responses sr 
              JOIN surveys s 
              ON sr.survey_id = s.id 
              JOIN offices o 
              ON s.office_id = o.id
              WHERE sr.submitted_at 
              BETWEEN :startDate 
              AND :endDatePlusOne";
    if ($user_role === 'office_head') {
        if (empty($user_office_id)) { throw new Exception("Office Head user is not assigned to an office.", 403); }
        $query .= " AND s.office_id = :user_office_id";
        $params[':user_office_id'] = $user_office_id;
    }
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $periodData = [ 'total_responses' => count($results), 'all_ratings' => [], 'suggestionsForAI' => [], 'office_ratings' => [], 'daily_data' => [] ];

    foreach ($results as $row) {
        $date = (new DateTime($row['submitted_at']))->format('Y-m-d');
        if (!isset($periodData['daily_data'][$date])) { $periodData['daily_data'][$date] = ['ratings' => [], 'sentiments' => []]; }
        $questions = json_decode($row['questions_json'], true);
        $answers = json_decode($row['answers_json'], true);
        
        $questionMap = []; // Create lookup map for this specific survey
        foreach ($questions as $q) { if (isset($q['id'])) { $questionMap[$q['id']] = ['type' => $q['type'] ?? 'unknown', 'title' => $q['title'] ?? 'Untitled']; } }
        
        foreach ($answers as $ans) {
            if (isset($ans['question_id'], $questionMap[$ans['question_id']])) {
                $questionInfo = $questionMap[$ans['question_id']];
                if (in_array($questionInfo['type'], ['likert', 'rating'])) {
                    $ratingValue = filter_var($ans['answer'], FILTER_VALIDATE_INT);
                    if ($ratingValue !== false) {
                        // Use the 'title' for aggregation, not the full text
                        $periodData['all_ratings'][] = ['rating' => $ratingValue, 'question_title' => $questionInfo['title']];
                        $periodData['daily_data'][$date]['ratings'][] = $ratingValue;
                        if (!isset($periodData['office_ratings'][$row['office_name']])) { $periodData['office_ratings'][$row['office_name']] = []; }
                        $periodData['office_ratings'][$row['office_name']][] = $ratingValue;
                    }
                } elseif ($questionInfo['title'] === 'Suggestions') {
                    $suggestionText = trim($ans['answer']);
                    $cleanText = strtolower($suggestionText);
                    $junkPhrases = ['na', 'n/a', 'none', 'wala', '.', 'no comment'];
                    if (!in_array($cleanText, $junkPhrases) && strlen($cleanText) > 5) {
                        $periodData['suggestionsForAI'][] = ['text' => $suggestionText, 'date' => $date];
                    }
                }
            }
        }
    }
    return $periodData;
}

try {
    // --- 1. FETCH & PROCESS DATA FOR BOTH PERIODS ---
    $currentDataAggregated = getAggregatedDataForPeriod($db, $startDate, $endDate, $user_role, $user_office_id);
    $start_dt = new DateTime($startDate); $end_dt = new DateTime($endDate); $interval = $start_dt->diff($end_dt);
    $prev_end_dt = (clone $start_dt)->modify('-1 day');
    $prev_start_dt = (clone $prev_end_dt)->modify('-' . $interval->format('%a') . ' days');
    $previousDataAggregated = getAggregatedDataForPeriod($db, $prev_start_dt->format('Y-m-d'), $prev_end_dt->format('Y-m-d'), $user_role, $user_office_id);

    // --- 2. AI INTEGRATION & SENTIMENT TREND CALCULATION ---
    $sentiment_map = ['Negative' => 0, 'Neutral' => 1, 'Positive' => 2];
    $recent_comments = [];
    $aiDataCurrent = callAiApi(array_column($currentDataAggregated['suggestionsForAI'], 'text'));
    if ($aiDataCurrent && isset($aiDataCurrent['items'])) {
        foreach ($aiDataCurrent['items'] as $index => $item) {
            $date = $currentDataAggregated['suggestionsForAI'][$index]['date'];
            $sentiment_score = $sentiment_map[$item['sentiment']] ?? 1;
            $currentDataAggregated['daily_data'][$date]['sentiments'][] = $sentiment_score;
            // Capture recent comments with their sentiment
            $recent_comments[] = ['text' => $item['text'], 'sentiment' => $item['sentiment']];
        }
    }

    // --- 3. BUILD FINAL HISTORICAL TREND ARRAYS ---
    $satisfaction_historical = []; $sentiment_historical = [];
    ksort($currentDataAggregated['daily_data']);
    foreach ($currentDataAggregated['daily_data'] as $date => $data) {
        $timestamp = (new DateTime($date))->getTimestamp();
        if (!empty($data['ratings'])) { $satisfaction_historical[] = ['x' => $timestamp, 'y' => round(array_sum($data['ratings']) / count($data['ratings']), 2)]; }
        if (!empty($data['sentiments'])) { $sentiment_historical[] = ['x' => $timestamp, 'y' => round(array_sum($data['sentiments']) / count($data['sentiments']), 2)]; }
    }

    // --- 4. GENERATE FORECASTS ---
    $satisfaction_forecast = []; $sentiment_forecast = [];
    $satisfaction_regression = linear_regression($satisfaction_historical);
    if ($satisfaction_regression && count($satisfaction_historical) > 1) {
        $last_point = end($satisfaction_historical); $forecast_days = max(3, floor(count($satisfaction_historical) / 4));
        for ($i = 1; $i <= $forecast_days; $i++) {
            $future_date_ts = $last_point['x'] + ($i * 86400);
            $forecast_score = $satisfaction_regression['slope'] * $future_date_ts + $satisfaction_regression['intercept'];
            $satisfaction_forecast[] = ['x' => $future_date_ts * 1000, 'y' => round(max(1, min(5, $forecast_score)), 2)];
        }
    }

    // --- 5. CALCULATE FINAL METRICS ---
    $current_ratings_only = array_column($currentDataAggregated['all_ratings'], 'rating');
    $previous_ratings_only = array_column($previousDataAggregated['all_ratings'], 'rating');
    $overall_satisfaction_current = !empty($current_ratings_only) ? round(array_sum($current_ratings_only) / count($current_ratings_only), 2) : 0;
    $overall_satisfaction_previous = !empty($previous_ratings_only) ? round(array_sum($previous_ratings_only) / count($previous_ratings_only), 2) : 0;

    $rating_distribution = array_fill_keys([5, 4, 3, 2, 1], 0);
    foreach ($current_ratings_only as $rating) { $rating_distribution[$rating]++; }

    $service_performance = [];
    $performance_agg = [];
    foreach ($currentDataAggregated['all_ratings'] as $r) {
        if (!isset($performance_agg[$r['question_title']])) { $performance_agg[$r['question_title']] = []; }
        $performance_agg[$r['question_title']][] = $r['rating'];
    }
    foreach ($performance_agg as $title => $ratings) { 
        $service_performance[] = ['label' => $title, 'value' => round(array_sum($ratings) / count($ratings), 1)]; 
    }
    
    //  Calculate Top/Bottom Offices
    $office_performance = [];
    foreach($currentDataAggregated['office_ratings'] as $office => $ratings) {
        $office_performance[] = ['name' => $office, 'score' => round(array_sum($ratings) / count($ratings), 2), 'response_count' => count($ratings)];
    }
    usort($office_performance, function($a, $b) { return $b['score'] <=> $a['score']; });
    $top_offices = array_slice($office_performance, 0, 3);
    $bottom_offices = array_slice(array_reverse($office_performance), 0, 3);

    // --- 6. PREPARE FINAL DATA PACKAGE ---
    $finalData = [
        'startDate' => $startDate, 'endDate' => $endDate,
        'total_responses' => $currentDataAggregated['total_responses'],
        'overall_satisfaction' => $overall_satisfaction_current,
        'predicted_satisfaction_kpi' => !empty($satisfaction_forecast) ? end($satisfaction_forecast)['y'] : null,
        'comparisons' => [
            'overall_satisfaction_change' => calculate_change($overall_satisfaction_current, $overall_satisfaction_previous),
            'total_responses_change' => calculate_change($currentDataAggregated['total_responses'], $previousDataAggregated['total_responses'])
        ],
        'satisfaction_historical' => $satisfaction_historical, 'satisfaction_forecast' => $satisfaction_forecast,
        'sentiment_historical' => $sentiment_historical, 'sentiment_forecast' => $sentiment_forecast,
        'rating_distribution' => $rating_distribution,
        'service_performance' => $service_performance,
        'office_performance' => ['top' => $top_offices, 'bottom' => $bottom_offices],
        'sentiment_analysis' => $aiDataCurrent['summary']['sentiment_counts'] ?? ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0],
        'common_concerns' => isset($aiDataCurrent['common_concerns']) ? array_slice($aiDataCurrent['common_concerns'], 0, 5) : [],
        'recent_comments' => array_slice($recent_comments, -5) // Get the last 5 comments
    ];
    
    respond(true, "Dashboard data retrieved successfully.", $finalData);

} catch (Exception $e) { respond(false, "An unexpected error occurred: " . $e->getMessage(), null, 500); }
?>