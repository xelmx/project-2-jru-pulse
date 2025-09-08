document.addEventListener('DOMContentLoaded', () => {
    // --- Global Variables for the NEW Layout ---
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const presetFiltersContainer = document.getElementById('preset-filters');
    const dashboardContent = document.getElementById('dashboard-content');
    const noDataMessage = document.getElementById('no-data-message');
    let trendsChart, ratingDistChart, sentimentChart;

    // --- CHART INITIALIZATION ---
    function initializeCharts() {
        const ratingCtx = document.getElementById('ratingDistChart').getContext('2d');
        ratingDistChart = new Chart(ratingCtx, { 
            type: 'bar', 
            data: { 
                labels: ['5', '4', '3', '2', '1'], 
                datasets: [{ data: [], 
                    backgroundColor: ['#22c55e', '#3b82f6', '#facc15', '#f97316', '#ef4444'], 
                    borderRadius: 4 
                }] 
            }, 
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false   
                    } 
                }, 
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            precision: 0 
                        } 
                    }, 
                    x: { 
                        grid: { 
                            display: false 
                        }
                    } 
                } 
            } 
        });
        const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
        sentimentChart = new Chart(sentimentCtx, { type: 'doughnut', data: { labels: ['Positive', 'Neutral', 'Negative'], datasets: [{ data: [], backgroundColor: ['#22c55e', '#a1a1aa', '#ef4444'], borderColor: '#ffffff', borderWidth: 2 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } } });
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        trendsChart = new Chart(trendsCtx, { type: 'line', data: { labels: [], datasets: [{ label: 'Satisfaction Score', data: [], borderColor: '#f59e0b', tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 1, max: 5 }, x: { type: 'time', time: { unit: 'day', displayFormats: { day: 'MMM d' } } } } } });
    }

    // --- DATA FETCHING ---
    async function updateDashboard(params = {}) {
        dashboardContent.style.opacity = '0.5';
        noDataMessage.classList.add('hidden');
        dashboardContent.classList.remove('hidden');
        try {
            const response = await fetch(`api/dashboard.php?${new URLSearchParams(params)}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            updateUI(result.data);
        } catch (error) {
            console.error('Failed to update dashboard:', error);
            dashboardContent.classList.add('hidden');
            noDataMessage.classList.remove('hidden');
        } finally {
            dashboardContent.style.opacity = '1';
        }
    }

    // --- Helper for rendering comparison text ---
    function renderComparison(elementId, change) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (change === null || change === undefined) { el.innerHTML = '&nbsp;'; return; }
        const color = change > 0 ? 'text-green-500' : (change < 0 ? 'text-red-500' : 'text-gray-500');
        const icon = change > 0 ? 'fa-arrow-up' : (change < 0 ? 'fa-arrow-down' : 'fa-minus');
        const text = change === 0 ? 'No change' : `${Math.abs(change)}% vs. last period`;
        el.innerHTML = `<span class="text-sm font-medium ${color}"><i class="fas ${icon} mr-1"></i>${text}</span>`;
    }

    // --- UI UPDATING ---
    function updateUI(data) {
        if (data.total_responses === 0) {
            dashboardContent.classList.add('hidden');
            noDataMessage.classList.remove('hidden');
            return;
        }
        startDateInput.value = data.startDate;
        endDateInput.value = data.endDate;

        document.getElementById('overall-satisfaction-score').textContent = data.overall_satisfaction.toFixed(1);
        document.getElementById('predicted-satisfaction-score').textContent = data.predicted_satisfaction.toFixed(1);
        document.getElementById('total-responses').textContent = data.total_responses.toLocaleString();
        const start = new Date(data.startDate), end = new Date(data.endDate);
        const dayDiff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        document.getElementById('feedback-frequency').textContent = (data.total_responses / (dayDiff > 0 ? dayDiff : 1)).toFixed(1);
        
        renderComparison('overall-satisfaction-comparison', data.comparisons.overall_satisfaction_change);
        renderComparison('predicted-satisfaction-comparison', data.comparisons.predicted_satisfaction_change);
        renderComparison('total-responses-comparison', data.comparisons.total_responses_change);

        const starsContainer = document.getElementById('overall-satisfaction-stars');
        starsContainer.innerHTML = '';
        const roundedStars = Math.round(data.overall_satisfaction);
        for (let i = 1; i <= 5; i++) { starsContainer.innerHTML += `<i class="${i <= roundedStars ? 'fas' : 'far'} fa-star text-jru-gold"></i>`; }

        trendsChart.data.labels = data.trends_labels;
        trendsChart.data.datasets[0].data = data.trends_data;
        trendsChart.update();

        const performanceContainer = document.getElementById('service-performance-bars');
        performanceContainer.innerHTML = '';
        if (data.service_performance && data.service_performance.length > 0) {
            data.service_performance.forEach(item => { const percentage = (item.value / 5) * 100; performanceContainer.innerHTML += `<div><div class="flex justify-between text-sm mb-1"><span class="truncate pr-2">${item.label}</span><span class="font-medium flex-shrink-0">${item.value.toFixed(1)}/5.0</span></div><div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-jru-blue h-2 rounded-full" style="width: ${percentage}%"></div></div></div>`; });
        } else { performanceContainer.innerHTML = `<p class="text-sm text-gray-500 text-center py-4">No performance data.</p>`; }
        
        const sentiments = data.sentiment_analysis;
        sentimentChart.data.datasets[0].data = [sentiments.Positive, sentiments.Neutral, sentiments.Negative];
        sentimentChart.update();
        const sentimentLegend = document.getElementById('sentiment-legend');
        sentimentLegend.innerHTML = ''; // Clear previous legend
        const totalSentiments = sentiments.Positive + sentiments.Neutral + sentiments.Negative;
        sentimentChart.data.labels.forEach((label, i) => {
            const value = sentimentChart.data.datasets[0].data[i];
            const percentage = totalSentiments > 0 ? ((value / totalSentiments) * 100).toFixed(0) : 0;
            const color = sentimentChart.data.datasets[0].backgroundColor[i];
            sentimentLegend.innerHTML += `<div><div class="flex items-center justify-center mb-1"><div class="w-3 h-3 rounded-full mr-2" style="background-color: ${color};"></div><span class="text-sm text-gray-600">${label}</span></div><p class="text-lg font-bold text-gray-900">${percentage}%</p></div>`;
        });
        
        ratingDistChart.data.datasets[0].data = [data.rating_distribution[5], data.rating_distribution[4], data.rating_distribution[3], data.rating_distribution[2], data.rating_distribution[1]];
        ratingDistChart.update();

        const concernsList = document.getElementById('common-feedback-list');
        concernsList.innerHTML = '';
        if (data.common_concerns && data.common_concerns.length > 0) {
            data.common_concerns.forEach(item => {
                const keyword = item[0].charAt(0).toUpperCase() + item[0].slice(1).replace(/([A-Z])/g, ' $1').trim(); // Add spaces for camelCase
                const count = item[1];
                concernsList.innerHTML += `<div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"><span class="text-sm font-medium text-gray-800">${keyword}</span><span class="text-xs bg-gray-200 text-gray-700 font-bold px-2 py-1 rounded-full">${count}</span></div>`;
            });
        } else { concernsList.innerHTML = `<p class="text-sm text-gray-500 text-center py-4">No common topics found.</p>`; }
    }
    
    // --- Event Listeners & Initial Load ---
    function setupEventListeners() {
        presetFiltersContainer.addEventListener('click', e => { if (e.target.tagName === 'BUTTON' && e.target.classList.contains('filter-btn')) { document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active')); e.target.classList.add('active'); updateDashboard({ period: e.target.dataset.period }); } });
        const handleDateChange = () => { document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active')); const startDate = startDateInput.value; const endDate = endDateInput.value; if (startDate && endDate && startDate <= endDate) { updateDashboard({ startDate, endDate }); } };
        startDateInput.addEventListener('change', handleDateChange);
        endDateInput.addEventListener('change', handleDateChange);
    }

    initializeCharts();
    setupEventListeners();
    document.querySelector('.filter-btn[data-period="this_week"]').click();
});