document.addEventListener('DOMContentLoaded', () => {
    //Global Variables
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const presetFiltersContainer = document.getElementById('preset-filters');
    let trendsChart;
    let ratingDistChart; // Chart for Rating Distribution

  
    // --- CHART INITIALIZATION 
    function initializeCharts() {
        // Trends Chart 
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Satisfaction Score', data: [], borderColor: '#f59e0b', tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 1, max: 5 } } }
        });

        // Initialize the Rating Distribution Bar Chart -->
        const ratingCtx = document.getElementById('ratingDistChart').getContext('2d');
        ratingDistChart = new Chart(ratingCtx, {
            type: 'bar',
            data: {
                labels: ['5', '4', '3', '2', '1'],
                datasets: [{
                    label: 'Count',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: ['#22c55e', '#3b82f6', '#facc15', '#f97316', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.raw} responses`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Ensure y-axis shows only whole numbers
                        }
                    },
                    x: {
                        grid: {
                            display: false // Hide the vertical grid lines
                        }
                    }
                }
            }
        });
    }

    // --- DATA FETCHING--
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

        // Update metric cards (no change)
        document.getElementById('total-responses').textContent = data.total_responses.toLocaleString();
        const overallScore = data.overall_satisfaction.toFixed(1);
        document.getElementById('overall-satisfaction-score').textContent = overallScore;
        const start = new Date(data.startDate);
        const end = new Date(data.endDate);
        const dayDifference = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        const dailyAverage = (data.total_responses / (dayDifference > 0 ? dayDifference : 1)).toFixed(1);
        document.getElementById('feedback-frequency').textContent = dailyAverage;
        const starsContainer = document.getElementById('overall-satisfaction-stars');
        starsContainer.innerHTML = '';
        const roundedStars = Math.round(data.overall_satisfaction);
        for (let i = 1; i <= 5; i++) {
            starsContainer.innerHTML += `<i class="${i <= roundedStars ? 'fas' : 'far'} fa-star text-jru-gold"></i>`;
        }
        
        // Update the Rating Distribution Chart -->
        document.getElementById('rating-dist-total').textContent = `${data.total_responses.toLocaleString()} total`;
        // Prepare the data array in the correct order [5-star, 4-star, ..., 1-star]
        const newRatingData = [
            data.rating_distribution[5] || 0,
            data.rating_distribution[4] || 0,
            data.rating_distribution[3] || 0,
            data.rating_distribution[2] || 0,
            data.rating_distribution[1] || 0
        ];
        ratingDistChart.data.datasets[0].data = newRatingData;
        ratingDistChart.update();

        // Service Performance
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
    }
    
    // --- OFFICE FILTER POPULATION ---
    async function populateOfficeFilter() {
        try {
            const response = await fetch('api/offices.php');
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            const officeSelect = document.getElementById('export-office');
            officeSelect.innerHTML = '<option value="all">All Offices</option>'; 
            result.data.forEach(office => {
                const option = document.createElement('option');
                option.value = office.id;
                option.textContent = office.name;
                officeSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to populate office filter:', error);
        }
    }

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
            // Note: need to create a function named markAllAsRead for this to work
            // markAllReadBtn.addEventListener('click', markAllAsRead); 
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
                // Note: May need a function to initialize dates here
                // initializeExportModal(); 
            });
        }
        if (closeExportModalBtn) closeExportModalBtn.addEventListener('click', () => exportModal.classList.add('hidden'));
        if (cancelExportBtn) cancelExportBtn.addEventListener('click', () => exportModal.classList.add('hidden'));
        if (exportForm) {
            // Note: Need to create a function to handle the export
            // exportForm.addEventListener('submit', handleExportSubmit);
        }
}

    // --- INITIAL LOAD ---
    initializeCharts();
    setupEventListeners();
    populateOfficeFilter();
    document.querySelector('.filter-btn[data-period="this_week"]').click();
});