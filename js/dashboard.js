document.addEventListener('DOMContentLoaded', () => {
    //Global Variables
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const presetFiltersContainer = document.getElementById('preset-filters');
    let trendsChart, ratingDistChart, sentimentChart; //Chart.js instances

  
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

        const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
        sentimentChart = new Chart(sentimentCtx, {

            type: 'doughnut',
            data: {
                labels: ['Positive', 'Neutral', 'Negative'],
                datasets:[{
                    data: [0, 0, 0], //Initial empty data
                    backgroundColor: ['#22c55e', '#9ca3af', '#ef4444'],
                    borderColor: '#ffffff',
                    broderWidth: 2
                }]
            },

            options: {
                responsive: true,
                maintainAspectRation: false,
                plugins: {
                   legend: { display: false},
                   tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.label}: ${context.raw} comments`;
                            }
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
        
        // URating Distribution Chart 
       const ratingData = [data.rating_distribution[5], data.rating_distribution[4], data.rating_distribution[3], data.rating_distribution[2], data.rating_distribution[1]];
        ratingDistChart.data.datasets[0].data = ratingData;
        ratingDistChart.update();

        // --- Chart: Satisfaction Trends ---
    trendsChart.data.labels = data.trends_labels;
    trendsChart.data.datasets[0].data = data.trends_data;
    trendsChart.update();

        //SERVICE PERFORMANCE
    const performanceContainer = document.getElementById('service-performance-bars');
    performanceContainer.innerHTML = ''; // Clear previous results

    if (data.service_performance && data.service_performance.length > 0) {
        data.service_performance.forEach(item => {
            const percentage = (item.value / 5) * 100;
            const performanceElement = `
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="truncate pr-2">${item.label}</span>
                        <span class="font-medium flex-shrink-0">${item.value.toFixed(1)}/5.0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-jru-blue h-2 rounded-full" style="width: ${percentage}%"></div>
                    </div>
                </div>`;
            performanceContainer.innerHTML += performanceElement;
        });
    } else {
        performanceContainer.innerHTML = `<p class="text-sm text-gray-500 text-center py-4">No specific service performance data for this period.</p>`;
    }

        // --- 1. Update Sentiment Analysis ---
        const sentiments = data.sentiment_analysis;
        const totalSentiments = sentiments.Positive + sentiments.Neutral + sentiments.Negative;

        // Update the doughnut chart
        sentimentChart.data.datasets[0].data = [sentiments.Positive, sentiments.Neutral, sentiments.Negative];
        sentimentChart.update();

        // Update the percentage labels
        const positivePercent = totalSentiments > 0 ? ((sentiments.Positive / totalSentiments) * 100).toFixed(0) : 0;
        const neutralPercent = totalSentiments > 0 ? ((sentiments.Neutral / totalSentiments) * 100).toFixed(0) : 0;
        const negativePercent = totalSentiments > 0 ? ((sentiments.Negative / totalSentiments) * 100).toFixed(0) : 0;
        
        document.getElementById('sentiment-positive-percent').textContent = `${positivePercent}%`;
        document.getElementById('sentiment-neutral-percent').textContent = `${neutralPercent}%`;
        document.getElementById('sentiment-negative-percent').textContent = `${negativePercent}%`;

        // --- 2. Update Common Feedback ---
        const concernsList = document.getElementById('common-feedback-list');
        const concernsData = data.common_concerns;
        concernsList.innerHTML = ''; // Clear existing list

        if (concernsData && concernsData.length > 0) {
            // The API gives an array of arrays, e.g., [["wifi", 5], ["staff", 3]]
            concernsData.forEach(item => {
                const keyword = item[0];
                const count = item[1];
                const concernElement = `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm font-medium text-gray-800">${keyword.charAt(0).toUpperCase() + keyword.slice(1)}</span>
                        <span class="text-xs bg-gray-200 text-gray-700 font-bold px-2 py-1 rounded-full">${count}</span>
                    </div>`;
                concernsList.innerHTML += concernElement;
            });
        } else {
            concernsList.innerHTML = `<p class="text-sm text-gray-500 text-center py-4">No common feedback topics found for this period.</p>`;
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
    document.querySelector('.filter-btn[data-period="this_week"]').click();
});