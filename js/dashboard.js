
document.addEventListener('DOMContentLoaded', () => {
    // GLOBAL REFERENCES
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const presetFiltersContainer = document.getElementById('preset-filters');
    let trendsChart;

    // --- SIDEBAR LOGIC 
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');
        });
    }

    // --- CHART INITIALIZATION ---
    function initializeCharts() {
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Satisfaction Score', data: [], borderColor: '#f59e0b', tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 1, max: 5 } } }
        });

        // Note: ML charts are placeholders until we integrate the ML model
    }

    //  DATA FETCHING & UI UPDATING
    async function updateDashboard(params = {}) {
        document.getElementById('metrics-grid').style.opacity = '0.5';
        const queryParams = new URLSearchParams(params);
        try {
            const response = await fetch(`api/dashboard.php?${queryParams.toString()}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            updateUI(result.data);
        } catch (error) {
            console.error('Failed to update dashboard:', error);
            alert('Could not load dashboard data.');
        } finally {
            document.getElementById('metrics-grid').style.opacity = '1';
        }
    }

    function updateUI(data) {
        // Update date inputs
        startDateInput.value = data.startDate;
        endDateInput.value = data.endDate;

        // Update metric cards
        document.getElementById('total-responses').textContent = data.total_responses.toLocaleString();
        const overallScore = data.overall_satisfaction.toFixed(1);
        document.getElementById('overall-satisfaction-score').textContent = overallScore;

        const starsContainer = document.getElementById('overall-satisfaction-stars');
        starsContainer.innerHTML = '';
        const roundedStars = Math.round(data.overall_satisfaction);
        for (let i = 1; i <= 5; i++) {
            starsContainer.innerHTML += `<i class="${i <= roundedStars ? 'fas' : 'far'} fa-star text-jru-gold"></i>`;
        }
        
        // Rating Distribution
        document.getElementById('rating-dist-total').textContent = `${data.total_responses.toLocaleString()} total`;
        const ratingContainer = document.getElementById('rating-distribution-bars');
        ratingContainer.innerHTML = '';
        const ratingColors = { 5: 'bg-green-500', 4: 'bg-blue-500', 3: 'bg-yellow-500', 2: 'bg-orange-500', 1: 'bg-red-500' };
        for (let i = 5; i >= 1; i--) {
            const count = data.rating_distribution[i] || 0;
            const percentage = data.total_responses > 0 ? (count / data.total_responses) * 100 : 0;
            ratingContainer.innerHTML += `
                <div class="flex items-center text-xs">
                    <span class="w-3">${i}</span>
                    <div class="flex-1 mx-2 bg-gray-200 rounded-full h-1.5"><div class="${ratingColors[i]} h-1.5 rounded-full" style="width: ${percentage}%"></div></div>
                    <span class="w-8 text-right">${count.toLocaleString()}</span>
                </div>`;
        }

        // DYNAMICALLY Service Performance
        const performanceContainer = document.getElementById('service-performance-bars');
        performanceContainer.innerHTML = '';
        if (data.service_performance.length > 0) {
            data.service_performance.forEach(item => {
                const percentage = (item.value / 5) * 100;
                performanceContainer.innerHTML += `
                    <div>
                        <div class="flex justify-between text-sm mb-1"><span>${item.label}</span><span class="font-medium">${item.value.toFixed(1)}/5.0</span></div>
                        <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-jru-blue h-2 rounded-full" style="width: ${percentage}%"></div></div>
                    </div>`;
            });
        } else {
            performanceContainer.innerHTML = `<p class="text-sm text-gray-500">No performance data for this period.</p>`;
        }
        
        // Update trends chart
        trendsChart.data.labels = data.trends_labels;
        trendsChart.data.datasets[0].data = data.trends_data;
        trendsChart.update();

        // Note: Notifications and Export modal will be handled separately
    }

    // --- EVENT LISTENERS ---
    function setupEventListeners() {
        // --- Date and Preset Filter Listeners ---
        presetFiltersContainer.addEventListener('click', e => {
            if (e.target.tagName === 'BUTTON' && e.target.classList.contains('filter-btn')) {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                updateDashboard({ period: e.target.dataset.period });
            }
        });

        const handleDateChange = () => {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            if (startDate && endDate && startDate <= endDate) {
                updateDashboard({ startDate, endDate });
            }
        };
        startDateInput.addEventListener('change', handleDateChange);
        endDateInput.addEventListener('change', handleDateChange);

        // --- Trends Chart Modal Listeners ---
        const trendsModal = document.getElementById('trends-modal');
        const openModalBtn = document.getElementById('open-trends-modal-btn');
        const closeModalBtn = document.getElementById('close-trends-modal-btn');

        if (openModalBtn) openModalBtn.addEventListener('click', () => trendsModal.classList.remove('hidden'));
        if (closeModalBtn) closeModalBtn.addEventListener('click', () => trendsModal.classList.add('hidden'));
        if (trendsModal) trendsModal.addEventListener('click', e => { if (e.target === trendsModal) trendsModal.classList.add('hidden') });

        // --- Notification Bell & Dropdown Listeners ---
        const notificationBell = document.getElementById('notification-bell');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const markAllReadBtn = document.getElementById('mark-all-read-btn');
        
        if (notificationBell) {
            notificationBell.addEventListener('click', e => {
                e.stopPropagation();
                notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
            });
        }
        document.addEventListener('click', () => {
            if (notificationDropdown) notificationDropdown.style.display = 'none';
        });
        if (notificationDropdown) {
            notificationDropdown.addEventListener('click', e => e.stopPropagation());
        }
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', markAllAsRead); // Assumes markAllAsRead function exists
        }

        // --- Feedback Details Modal (for notifications) ---
        const feedbackModal = document.getElementById('feedback-modal');
        const closeFeedbackModalBtn = document.getElementById('close-feedback-modal-btn');
        
        if (closeFeedbackModalBtn) closeFeedbackModalBtn.addEventListener('click', () => feedbackModal.classList.add('hidden'));
        if (feedbackModal) feedbackModal.addEventListener('click', e => { if (e.target === feedbackModal) feedbackModal.classList.add('hidden') });

        // --- Export Data Modal Listeners ---
        const exportModal = document.getElementById('export-modal');
        const openExportModalBtn = document.getElementById('open-export-modal-btn');
        const closeExportModalBtn = document.getElementById('close-export-modal-btn');
        const cancelExportBtn = document.getElementById('cancel-export-btn');
        const exportForm = document.getElementById('export-form');

        if (openExportModalBtn) {
            openExportModalBtn.addEventListener('click', () => {
                exportModal.classList.remove('hidden');
                // initializeExportModal(); // Assumes this function exists to set dates
            });
        }
        if (closeExportModalBtn) closeExportModalBtn.addEventListener('click', () => exportModal.classList.add('hidden'));
        if (cancelExportBtn) cancelExportBtn.addEventListener('click', () => exportModal.classList.add('hidden'));
        // if (exportForm) exportForm.addEventListener('submit', handleExportSubmit); // Assumes this function exists
    }

    // --- INITIAL LOAD ---
    initializeCharts();
    setupEventListeners();
    populateOfficeFilter(); // The function we created to populate the export modal
    document.querySelector('.filter-btn[data-period="this_week"]').click(); // Click the default filter to trigger the first data load
});