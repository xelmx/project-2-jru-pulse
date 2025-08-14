    document.addEventListener('DOMContentLoaded', () => {
        initializeApp();
    });

    let currentSurveyData = null; // Store survey data globally on this page

    function initializeApp() {
        const params = new URLSearchParams(window.location.search);  // Get the survey ID from the URL
        const surveyId = params.get('id');

        if (!surveyId) {
            showErrorState("No survey ID was provided in the URL.");
            return;
        }

        loadSurveyDetails(surveyId); //Load the survey data

        const duplicateBtn = document.getElementById('duplicateSurveyBtn'); //Setup event listeners
        if (duplicateBtn) {
            duplicateBtn.addEventListener('click', () => {
                if (currentSurveyData) {
                    handleDuplicateSurvey(currentSurveyData.id);
                }
            });
        }

        //sidebarToggle to add
    }

    async function loadSurveyDetails(surveyId) {
        try {
            const response = await fetch(`api/surveys.php?id=${surveyId}`);
            if (!response.ok) {
                throw new Error(`API request failed with status ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                currentSurveyData = result.data;
                displaySurveyDetails(currentSurveyData);
            } else {
                throw new Error(result.message || "Failed to load survey data.");
            }
        } catch (error) {
            console.error("Error loading survey details:", error);
            showErrorState(error.message);
        }
    }

    function displaySurveyDetails(survey) {
        // Hide loading, show content
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('surveyContent').classList.remove('hidden');

        // Populate header
        document.getElementById('surveyTitleHeader').textContent = survey.title;

        // Populate metadata card
        document.getElementById('metaOffice').textContent = survey.office_name || 'N/A';
        document.getElementById('metaService').textContent = survey.service_name || 'N/A';
        document.getElementById('metaResponses').textContent = survey.response_count || 0;
        document.getElementById('metaCreated').textContent = formatDate(survey.created_at);

        // Populate status with badge
        const statusEl = document.getElementById('metaStatus');
        statusEl.innerHTML = `<span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 rounded-full ${getStatusClass(survey.status)}">
            ${survey.status.charAt(0).toUpperCase() + survey.status.slice(1)}
        </span>`;

        // Populate locked status with icon
        const lockedEl = document.getElementById('metaLocked');
        if (parseInt(survey.is_locked) === 1) {
            lockedEl.innerHTML = `<i class="fas fa-lock text-red-500 mr-2"></i> Locked (Cannot be edited)`;
        } else {
            lockedEl.innerHTML = `<i class="fas fa-lock-open text-green-500 mr-2"></i> Unlocked (Editable)`;
        }

        // Render questions
        renderQuestions(survey.questions_json);
    }

    function renderQuestions(questionsJson) {
        const container = document.getElementById('questionsContainer');
        if (!questionsJson) {
            container.innerHTML = `<p class="text-gray-500">This survey has no questions yet.</p>`;
            return;
        }

        let questions;
        try {
            questions = JSON.parse(questionsJson);
        } catch (e) {
            console.error("Failed to parse questions JSON:", e);
            container.innerHTML = `<p class="text-red-500">Error: Could not display questions due to a data format issue.</p>`;
            return;
        }

        if (!questions || questions.length === 0) {
            container.innerHTML = `<p class="text-gray-500">This survey has no questions yet.</p>`;
            return;
        }

        let html = '';
        questions.forEach((question, index) => {
            html += `
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-sm font-medium text-gray-600">Question ${index + 1} - Type: ${question.type}</p>
                    <p class="text-lg text-gray-800 mt-1">${question.text}</p>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    async function handleDuplicateSurvey(sourceId) {
        // We will use a proper confirmation modal later if you have one
        const isConfirmed = confirm("Are you sure you want to create a new, editable draft from this survey?");
        
        if (!isConfirmed) {
            return;
        }

        try {
            // This is the new API action we need to implement
            const response = await fetch(`api/surveys.php?action=duplicate&source_id=${sourceId}`, {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success && result.new_id) {
                // Success! Redirect to the survey builder with the new draft's ID
                alert("Duplication successful! You will now be taken to the survey builder to edit the new draft.");
                window.location.href = `survey-builder.php?survey_id=${result.new_id}`;
            } else {
                throw new Error(result.message || 'Duplication failed for an unknown reason.');
            }
        } catch (error) {
            console.error("Duplication Error:", error);
            alert(`Error: ${error.message}`);
        }
    }


    function showErrorState(message) {
        const loadingEl = document.getElementById('loadingState');
        const contentEl = document.getElementById('surveyContent');
        
        contentEl.classList.add('hidden');
        loadingEl.classList.remove('hidden');

        loadingEl.innerHTML = `
            <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
            <p class="mt-4 text-gray-800 font-semibold">An Error Occurred</p>
            <p class="mt-2 text-gray-600">${message}</p>
            <a href="survey-management.php" class="mt-4 inline-block bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800">
                ← Back to Survey Management
            </a>
        `;
    }

    function formatDate(dateString) {// Helper functions (can be moved to a shared file)
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function getStatusClass(status) {
        const classes = {
            'draft': 'bg-yellow-100 text-yellow-800',
            'active': 'bg-green-100 text-green-800',
            'archived': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }