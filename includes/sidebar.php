<div id="sidebar" class="sidebar-transition sidebar-expanded bg-blue-950 shadow-lg flex flex-col border-r border-gray-200">
    <!-- Logo Section -->
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center">
            <button id="sidebarToggle" class="p-2 rounded-lg hover:bg-gray-600 transition-colors mr-3">
                <i class="fas fa-bars text-gray-100"></i>
            </button>
            <div id="logoContainer" class="logo-transition flex items-center">
                <img src="assets/jru-pulse-final-white.png" alt="JRU-PULSE" class="h-8 w-auto">
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 p-4 overflow-y-auto">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'dashboard') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-tachometer-alt text-lg w-6"></i>
                    <span class="menu-text ml-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="survey-management.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'survey-management') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-poll-h text-lg w-6"></i>
                    <span class="menu-text ml-3">Survey Management</span>
                </a>
            </li>
            
           
            <li>
                <a href="student-management.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'student-management') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-school text-lg w-6"></i>
                    <span class="menu-text ml-3">Student Management</span>
                </a>
            </li>
             <li>
                <a href="guest-management.php" 
                class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'guest-management') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-user-tag text-lg w-6"></i>
                    <span class="menu-text ml-3">Guest Management</span>
                </a>
            </li>
            
            <li>
                <a href="performance-analytics-reports.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'analytics') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-chart-line text-lg w-6"></i>
                    <span class="menu-text ml-3">Performance Analytics</span>
                </a>
            </li>
             <li>
                <a href="user-management.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'user-management') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-users text-lg w-6"></i>
                    <span class="menu-text ml-3">User Management</span>
                </a>
            </li>
            <li>
                <a href="settings.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'settings') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    <i class="fas fa-cog text-lg w-6"></i>
                    <span class="menu-text ml-3">Settings</span>
                </a>
            </li>
        </ul>
        <div> <a href="survey-builder.php" 
                   class="flex items-center px-3 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'survey-builder') ? 'bg-blue-50 text-jru-blue font-medium' : 'text-gray-50 hover:bg-gray-600'; ?>">
                    
                </a></div>

        <!-- Quick Actions 
        <div class="mt-8">
            <div id="quickActionsHeader" class="menu-text text-xs font-semibold text-gray-50 uppercase tracking-wider mb-3">
                Quick Actions
            </div>
            <div class="space-y-2">
                <button id="quickNewSurvey" class="flex items-center w-full px-3 py-2 text-sm text-gray-50 hover:bg-gray-600 rounded-lg transition-colors">
                    <i class="fas fa-plus text-sm w-6"></i>
                    <span class="menu-text ml-3">New Survey</span>
                </button>
                <button class="flex items-center w-full px-3 py-2 text-sm text-gray-50 hover:bg-gray-600 rounded-lg transition-colors">
                    <i class="fas fa-download text-sm w-6"></i>
                    <span class="menu-text ml-3">Export Data</span>
                </button>
            </div>
        </div> -->
    </nav>
   
    <!-- User Profile  -->
    <div class="p-4 border-t border-gray-200 relative">
        <div id="userMenu" class="absolute bottom-full mb-2 left-0 right-0 p-2 hidden">
            <div class="bg-jru-gold rounded-lg shadow-xl">
                <a href="includes/logout.php" id="logoutBtn" class="flex items-center w-full px-3 py-3 text-sm text-red-400 hover:bg-red-900 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="menu-text ml-2">Logout</span>
                </a>
            </div>
        </div>
        <button id="userMenuBtn" class="w-full flex items-center text-left hover:bg-gray-700 p-2 rounded-lg transition-colors">
            <div class="w-10 h-10 bg-gradient-to-r from-jru-gold to-yellow-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-bold text-white">
                    <?php echo htmlspecialchars(strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1))); ?>
                </span>
            </div>
            <div id="userInfo" class="menu-text ml-3 flex-1 overflow-hidden">
                <p class="text-sm font-medium text-gray-50 truncate"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <p class="text-xs text-gray-100 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div class="menu-text ml-2">
                <i class="fas fa-ellipsis-v text-gray-400"></i>
            </div>
        </button>
    </div>

    
</div>