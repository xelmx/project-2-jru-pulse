document.addEventListener('DOMContentLoaded', () => {
    // --- Global Variables for the NEW Layout ---
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const presetFiltersContainer = document.getElementById('preset-filters');
    const dashboardContent = document.getElementById('dashboard-content');
    const noDataMessage = document.getElementById('no-data-message');
    let satisfactionTrendChart, sentimentTrendChart, ratingDistChart, sentimentDoughnutChart;

    // --- CHART INITIALIZATION ---
    function initializeCharts() {
        const satisfactionCtx = document.getElementById('satisfactionTrendChart').getContext('2d');
        satisfactionTrendChart = new Chart(satisfactionCtx, { type: 'line', data: { datasets: [ { label: 'Historical Avg.', data: [], borderColor: '#003366', tension: 0.3, fill: true, backgroundColor: 'rgba(0, 51, 102, 0.1)' }, { label: 'Forecast', data: [], borderColor: '#FFC425', borderDash: [5, 5], tension: 0.3, fill: false } ] }, options: { responsive: true, maintainAspectRatio: false, scales: { x: { type: 'time', time: { unit: 'day' } }, y: { min: 1, max: 5 } } } });
        const sentimentCtx = document.getElementById('sentimentTrendChart').getContext('2d');
        sentimentTrendChart = new Chart(sentimentCtx, { type: 'line', data: { datasets: [ { label: 'Historical Avg.', data: [], borderColor: '#003366', tension: 0.3 }, { label: 'Forecast', data: [], borderColor: '#FFC425', borderDash: [5, 5], tension: 0.3 } ] }, options: { responsive: true, maintainAspectRatio: false, scales: { x: { type: 'time', time: { unit: 'day' } }, y: { min: 0, max: 2, ticks: { callback: (v) => { if (v === 0) return 'Negative'; if (v === 1) return 'Neutral'; if (v === 2) return 'Positive'; return ''; } } } } } });
        const ratingCtx = document.getElementById('ratingDistChart').getContext('2d');
        ratingDistChart = new Chart(ratingCtx, { type: 'bar', data: { labels: ['5', '4', '3', '2', '1'], datasets: [{ data: [], backgroundColor: ['#22c55e', '#3b82f6', '#facc15', '#f97316', '#ef4444'], borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } } });
        const sentimentDoughnutCtx = document.getElementById('sentimentDoughnutChart').getContext('2d');
        sentimentDoughnutChart = new Chart(sentimentDoughnutCtx, { type: 'doughnut', data: { labels: ['Positive', 'Neutral', 'Negative'], datasets: [{ data: [], backgroundColor: ['#22c55e', '#a1a1aa', '#ef4444'], borderColor: '#ffffff', borderWidth: 2 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } } });
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
        if (data.total_responses === 0) { dashboardContent.classList.add('hidden'); noDataMessage.classList.remove('hidden'); return; }
        startDateInput.value = data.startDate; endDateInput.value = data.endDate;

        // Populate Hero Section & KPIs
        document.getElementById('overall-satisfaction-score').textContent = data.overall_satisfaction.toFixed(1);
        document.getElementById('predicted-satisfaction-kpi').textContent = data.predicted_satisfaction_kpi ? data.predicted_satisfaction_kpi.toFixed(1) : 'N/A';
        document.getElementById('total-responses').textContent = data.total_responses.toLocaleString();
        renderComparison('overall-satisfaction-comparison', data.comparisons.overall_satisfaction_change);
        renderComparison('total-responses-comparison', data.comparisons.total_responses_change);

        // Populate Charts
        satisfactionTrendChart.data.datasets[0].data = data.satisfaction_historical.map(p => ({ x: p.x * 1000, y: p.y }));
        satisfactionTrendChart.data.datasets[1].data = data.satisfaction_forecast.map(p => ({ x: p.x, y: p.y }));
        satisfactionTrendChart.update();
        sentimentTrendChart.data.datasets[0].data = data.sentiment_historical.map(p => ({ x: p.x * 1000, y: p.y }));
        sentimentTrendChart.data.datasets[1].data = data.sentiment_forecast.map(p => ({ x: p.x, y: p.y }));
        sentimentTrendChart.update();
        ratingDistChart.data.datasets[0].data = [data.rating_distribution[5], data.rating_distribution[4], data.rating_distribution[3], data.rating_distribution[2], data.rating_distribution[1]];
        ratingDistChart.update();
        const sentiments = data.sentiment_analysis;
        sentimentDoughnutChart.data.datasets[0].data = [sentiments.Positive, sentiments.Neutral, sentiments.Negative];
        sentimentDoughnutChart.update();

        // Populate Sentiment Breakdown List
        const sentimentBreakdownList = document.getElementById('sentiment-breakdown-list');
        sentimentBreakdownList.innerHTML = '';
        const totalSentiments = sentiments.Positive + sentiments.Neutral + sentiments.Negative;
        sentimentDoughnutChart.data.labels.forEach((label, i) => {
            const value = sentimentDoughnutChart.data.datasets[0].data[i];
            const percentage = totalSentiments > 0 ? ((value / totalSentiments) * 100).toFixed(0) : 0;
            const color = sentimentDoughnutChart.data.datasets[0].backgroundColor[i];
            sentimentBreakdownList.innerHTML += `<div class="flex items-center justify-between text-sm"><div class="flex items-center"><div class="w-3 h-3 rounded-full mr-2" style="background-color: ${color};"></div><span>${label}</span></div><span class="font-semibold">${percentage}%</span></div>`;
        });

        // Populate Lists
        const perfContainer = document.getElementById('service-performance-bars'); perfContainer.innerHTML = '';
        if(data.service_performance.length > 0) { data.service_performance.forEach(item => { const p = (item.value / 5) * 100; perfContainer.innerHTML += `<div><div class="flex justify-between text-sm mb-1"><span>${item.label}</span><span class="font-medium">${item.value.toFixed(1)}/5.0</span></div><div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-jru-blue h-2 rounded-full" style="width: ${p}%"></div></div></div>`; }); }
        
        const topOfficesList = document.getElementById('top-offices-list'); topOfficesList.innerHTML = '';
        data.office_performance.top.forEach(office => { topOfficesList.innerHTML += `<div class="flex justify-between items-center text-sm"><span class="font-medium text-gray-700">${office.name}</span><span class="font-bold text-green-600">${office.score.toFixed(2)}</span></div>`; });
        
        const bottomOfficesList = document.getElementById('bottom-offices-list'); bottomOfficesList.innerHTML = '';
        data.office_performance.bottom.forEach(office => { bottomOfficesList.innerHTML += `<div class="flex justify-between items-center text-sm"><span class="font-medium text-gray-700">${office.name}</span><span class="font-bold text-red-600">${office.score.toFixed(2)}</span></div>`; });

        const concernsList = document.getElementById('common-feedback-content'); concernsList.innerHTML = '';
        if(data.common_concerns.length > 0) {
            data.common_concerns.forEach(item => {
                // THE CRITICAL FIX for readability
                const keyword = item[0].replace(/([A-Z])/g, ' $1').replace(/^./, c => c.toUpperCase()).trim();
                const count = item[1];
                concernsList.innerHTML += `<div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"><span class="text-sm font-medium text-gray-800">${keyword}</span><span class="text-xs bg-gray-200 font-bold px-2 py-1 rounded-full">${count}</span></div>`;
            });
        } else { concernsList.innerHTML = '<p class="text-center text-sm text-gray-500 py-4">No common topics found.</p>'; }
        
        const recentList = document.getElementById('recent-comments-content'); recentList.innerHTML = '';
        if(data.recent_comments.length > 0) { data.recent_comments.forEach(c => { const color = c.sentiment === 'Positive' ? 'border-green-400' : (c.sentiment === 'Negative' ? 'border-red-400' : 'border-gray-300'); recentList.innerHTML += `<div class="p-2 border-l-4 ${color}"><p class="text-sm text-gray-700 italic">"${c.text}"</p></div>`; }); } else { recentList.innerHTML = '<p class="text-center text-sm text-gray-500 py-4">No recent comments.</p>'; }
    }
    
    // --- Event Listeners & Initial Load ---
     function setupEventListeners() {
        presetFiltersContainer.addEventListener('click', e => { if (e.target.tagName === 'BUTTON' && e.target.classList.contains('filter-btn')) { document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active')); e.target.classList.add('active'); updateDashboard({ period: e.target.dataset.period }); } });
        const handleDateChange = () => { document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active')); const startDate = startDateInput.value; const endDate = endDateInput.value; if (startDate && endDate && startDate <= endDate) { updateDashboard({ startDate, endDate }); } };
        startDateInput.addEventListener('change', handleDateChange); endDateInput.addEventListener('change', handleDateChange);

        // Feedback Tabs
        const commonTab = document.getElementById('common-feedback-tab');
        const recentTab = document.getElementById('recent-comments-tab');
        const commonContent = document.getElementById('common-feedback-content');
        const recentContent = document.getElementById('recent-comments-content');
        commonTab.addEventListener('click', () => { commonTab.classList.add('border-jru-blue', 'text-jru-blue'); recentTab.classList.remove('border-jru-blue', 'text-jru-blue'); commonContent.classList.remove('hidden'); recentContent.classList.add('hidden'); });
        recentTab.addEventListener('click', () => { recentTab.classList.add('border-jru-blue', 'text-jru-blue'); commonTab.classList.remove('border-jru-blue', 'text-jru-blue'); recentContent.classList.remove('hidden'); commonContent.classList.add('hidden'); });
    }

    initializeCharts();
    setupEventListeners();
    document.querySelector('.filter-btn[data-period="this_week"]').click();
});