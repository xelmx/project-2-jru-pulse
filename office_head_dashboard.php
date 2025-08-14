<?php
session_start(); // Must be at the top

// Check if the user is logged in and is an office head
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'office_head') {
    // Not logged in or not an office head, redirect to login page
    header('Location: index.html');
    exit;
}

$user = $_SESSION['user_data'];
$office_name = isset($user['office_name']) ? htmlspecialchars($user['office_name']) : 'Your Office';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JRU Pulse - <?php echo $office_name; ?> Dashboard</title>
    <!-- Include your CSS (Tailwind, FontAwesome, custom styles) like in admin_dashboard.php -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script> /* Tailwind config */ </script>
    <style> /* Your dashboard styles */ </style>
</head>
<body>
    <div class="header"> <!-- Use similar header structure as admin_dashboard.php -->
        <div class="brand-logo">
            <img src="assets/JRU-PULSE-logo.svg" alt="JRU Pulse Logo">
            <h1>JRU-<span class="pulse">PULSE</span> Office Head</h1>
        </div>
        <div class="user-info">
            <?php if (isset($user['picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['picture']); ?>" alt="User Picture" class="w-10 h-10 rounded-full mr-3 border-2 border-jru-orange">
            <?php endif; ?>
            <span><?php echo htmlspecialchars($user['name']); ?> (Head of <?php echo $office_name; ?>)</span>
            <a href="logout.php" class="logout-btn ml-4">Logout</a>
        </div>
    </div>

    <div class="container mx-auto p-4">
        <div class="card bg-white p-6 rounded-lg shadow-md mb-6">
            <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            <p>You are viewing the dashboard for: <strong><?php echo $office_name; ?></strong>.</p>
        </div>

        <div class="card bg-white p-6 rounded-lg shadow-md mb-6">
            <h3><?php echo $office_name; ?> Performance</h3>
            <p>This section will display performance metrics specific to your office.</p>
            <!-- Add logic to fetch and display office-specific data -->
        </div>

        <div class="card bg-white p-6 rounded-lg shadow-md">
            <h3>Reports from Admin</h3>
            <p>View reports shared by the administration here.</p>
            <!-- Add logic to fetch and display reports shared with this office head -->
        </div>
    </div>

</body>
</html>