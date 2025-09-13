

document.addEventListener('DOMContentLoaded', () => {
    // --- Global DOM References ---
    const periodFilters = document.querySelectorAll('#preset-filters .filter-btn');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const officeFilter = document.getElementById('office-filter');
    const serviceFilter = document.getElementById('service-filter');
    
    const analyticsContent = document.getElementById('analytics-content');
    const loadingMessage = document.getElementById('loading-message');
    const noDataMessage = document.getElementById('no-data-message');

    // Chart.js instances
    let performanceByDayChart, satisfactionByDivisionChart, satisfactionByTypeChart, ratingDistChart, serviceBreakdownChart;

    // --- CHART INITIALIZATION ---
    function initializeCharts() {
        const createChartConfig = (type, labels, data, options = {}) => ({ type, data: { labels, datasets: [{ data, ...options.datasetOptions }] }, options: { responsive: true, maintainAspectRatio: false, ...options.chartOptions } });

        performanceByDayChart = new Chart(document.getElementById('performanceByDayChart').getContext('2d'), createChartConfig('bar', [], [], { datasetOptions: { label: 'Avg. Score', backgroundColor: '#003366' }, chartOptions: { scales: { y: { min: 1, max: 5 } } } }));
        satisfactionByDivisionChart = new Chart(document.getElementById('satisfactionByDivisionChart').getContext('2d'), createChartConfig('bar', [], [], { datasetOptions: { label: 'Avg. Score', backgroundColor: '#4ade80' }, chartOptions: { scales: { y: { min: 1, max: 5 } } } }));
        satisfactionByTypeChart = new Chart(document.getElementById('satisfactionByTypeChart').getContext('2d'), createChartConfig('bar', [], [], { datasetOptions: { label: 'Avg. Score', backgroundColor: '#facc15' }, chartOptions: { indexAxis: 'y', scales: { x: { min: 1, max: 5 } } } }));
        ratingDistChart = new Chart(document.getElementById('ratingDistChart').getContext('2d'), createChartConfig('bar', ['5', '4', '3', '2', '1'], [], { datasetOptions: { label: 'Count', backgroundColor: ['#22c55e', '#3b82f6', '#facc15', '#f97316', '#ef4444'], borderRadius: 4 }, chartOptions: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } } }));
        serviceBreakdownChart = new Chart(document.getElementById('serviceBreakdownChart').getContext('2d'), createChartConfig('bar', [], [], { datasetOptions: { label: 'Avg. Score', backgroundColor: '#059669' }, chartOptions: { indexAxis: 'y', scales: { x: { min: 1, max: 5 } } } }));
    }

    // --- DATA FETCHING & UI CONTROL ---
    async function fetchAndRenderData() {
        loadingMessage.classList.remove('hidden');
        noDataMessage.classList.add('hidden');
        analyticsContent.classList.add('hidden');

        const params = new URLSearchParams({
            period: document.querySelector('#preset-filters .filter-btn.active')?.dataset.period || 'custom',
            startDate: startDateInput.value,
            endDate: endDateInput.value,
            office_id: officeFilter.value,
            service_id: serviceFilter.value
        });

        try {
            const response = await fetch(`api/analytics.php?${params.toString()}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            updateUI(result.data);
        } catch (error) {
            console.error('Failed to update analytics:', error);
            noDataMessage.classList.remove('hidden');
        } finally {
            loadingMessage.classList.add('hidden');
        }
    }

    // --- UI UPDATING ---
    function updateUI(data) {
        if (data.total_responses === 0) {
            noDataMessage.classList.remove('hidden');
            return;
        }
        analyticsContent.classList.remove('hidden');

        // Update KPIs
        document.getElementById('kpi-overall-satisfaction').textContent = data.overall_satisfaction.toFixed(2);
        document.getElementById('kpi-total-responses').textContent = data.total_responses.toLocaleString();
        document.getElementById('kpi-nps').textContent = data.nps;
        document.getElementById('kpi-excellent-pct').textContent = (data.rating_distribution[5] / data.total_responses * 100).toFixed(1) + '%';
        
        // Update Charts
        performanceByDayChart.data.labels = data.performance_by_day.map(d => d.day);
        performanceByDayChart.data.datasets[0].data = data.performance_by_day.map(d => d.score);
        performanceByDayChart.update();

        satisfactionByDivisionChart.data.labels = data.satisfaction_by_division.map(d => d.division);
        satisfactionByDivisionChart.data.datasets[0].data = data.satisfaction_by_division.map(d => d.score);
        satisfactionByDivisionChart.update();
        
        satisfactionByTypeChart.data.labels = data.satisfaction_by_type.map(d => d.type);
        satisfactionByTypeChart.data.datasets[0].data = data.satisfaction_by_type.map(d => d.score);
        satisfactionByTypeChart.update();
        
        ratingDistChart.data.datasets[0].data = Object.values(data.rating_distribution);
        ratingDistChart.update();
        
        // Contextual Chart: Service Breakdown
        const serviceBreakdownCard = document.getElementById('service-breakdown-card');
        if(data.service_breakdown && data.service_breakdown.length > 0) {
            serviceBreakdownCard.classList.remove('hidden');
            serviceBreakdownChart.data.labels = data.service_breakdown.map(s => s.service);
            serviceBreakdownChart.data.datasets[0].data = data.service_breakdown.map(s => s.score);
            serviceBreakdownChart.update();
        } else {
            serviceBreakdownCard.classList.add('hidden');
        }
    }

    // --- FILTER & EVENT LISTENER SETUP ---
    async function populateOffices() {
        try {
            const response = await fetch('api/offices.php');
            const result = await response.json();
            if (result.success) {
                result.data.forEach(office => officeFilter.add(new Option(office.name, office.id)));
            }
        } catch (error) { console.error('Failed to populate offices:', error); }
    }

    async function populateServices(officeId) {
        serviceFilter.innerHTML = '<option value="all">All Services</option>';
        if (officeId === 'all') { serviceFilter.disabled = true; return; }
        serviceFilter.disabled = false;
        try {
            const response = await fetch(`api/services.php?office_id=${officeId}`);
            const result = await response.json();
            if (result.success) {
                result.data.forEach(service => serviceFilter.add(new Option(service.name, service.id)));
            }
        } catch (error) { console.error('Failed to populate services:', error); }
    }

    function setupEventListeners() {
        periodFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                periodFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                startDateInput.value = ''; // Clear custom dates
                endDateInput.value = '';
                fetchAndRenderData();
            });
        });
        
        const handleCustomDateChange = () => {
            periodFilters.forEach(b => b.classList.remove('active'));
            if (startDateInput.value && endDateInput.value) {
                fetchAndRenderData();
            }
        };
        startDateInput.addEventListener('change', handleCustomDateChange);
        endDateInput.addEventListener('change', handleCustomDateChange);

        officeFilter.addEventListener('change', () => {
            populateServices(officeFilter.value).then(fetchAndRenderData);
        });
        serviceFilter.addEventListener('change', fetchAndRenderData);
    }

    // --- INITIAL LOAD ---
    async function initialLoad() {
        initializeCharts();
        setupEventListeners();
        await populateOffices();
        document.querySelector('#preset-filters .filter-btn[data-period="this_month"]').classList.add('active');
        await fetchAndRenderData();
    }
    
    initialLoad();
});