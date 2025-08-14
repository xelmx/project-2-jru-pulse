    const sidebar = document.getElementById('sidebar'); // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const logoContainer = document.getElementById('logoContainer');
    const menuTexts = document.querySelectorAll('.menu-text');
    
    let sidebarCollapsed = false;
    
    sidebarToggle.addEventListener('click', function() {
        sidebarCollapsed = !sidebarCollapsed;
        
        if (sidebarCollapsed) {
            sidebar.classList.remove('sidebar-expanded');
            sidebar.classList.add('sidebar-collapsed');
            
            menuTexts.forEach(text => {
                text.style.opacity = '0';
                setTimeout(() => {
                    text.style.display = 'none';
                }, 150);
            });
            
            logoContainer.style.opacity = '0';
            setTimeout(() => {
                logoContainer.style.display = 'none';
            }, 150);
            
        } else {
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.add('sidebar-expanded');
            
            setTimeout(() => {
                menuTexts.forEach(text => {
                    text.style.display = 'block';
                    setTimeout(() => {
                        text.style.opacity = '1';
                    }, 50);
                });
                
                logoContainer.style.display = 'flex';
                setTimeout(() => {
                    logoContainer.style.opacity = '1';
                }, 50);
            }, 150);
        }
    });


    let currentSurveyId = null;
    let surveyQuestions = [];
    let currentEditingQuestion = null;
    let offices = []; // To store office data from API
    let services = []; // To store service data from API
    let toastTimer; 

    let previewCurrentIndex = 0;

    const defaultTemplate = [ // Default template questions
        {
            id: 1,
            type: 'likert',
            text: 'How satisfied are you with the outcome of the service you received?',
            help: 'Rate your overall satisfaction with the service results',
            required: true,
            title: 'Service Outcome'
        },
        {
            id: 2,
            type: 'likert',
            text: 'How satisfied are you with the speed/timeliness of the service?',
            help: 'Rate how quickly your request was processed',
            required: true,
            title: 'Speed of Service'
        },
        {
            id: 3,
            type: 'likert',
            text: 'How clear and helpful were the instructions and processing procedures?',
            help: 'Rate the clarity of instructions provided',
            required: true,
            title: 'Processing & Instruction'
        },
        {
            id: 4,
            type: 'likert',
            text: 'How would you rate the professionalism and courtesy of the staff?',
            help: 'Rate the behavior and attitude of staff members',
            required: true,
            title: 'Staff Professionalism & Courtesy'
        },
        {
            id: 5,
            type: 'textarea',
            text: 'Please provide any suggestions for improvement or additional comments:',
            help: 'Share your thoughts on how we can improve our services',
            required: true,
            title: 'Suggestions'
        }
    ];


    const questionsListEl = document.getElementById('questionsList');
    const questionModalEl = document.getElementById('questionModal');
    const templateModalEl = document.getElementById('templateModal');
    const previewModalEl = document.getElementById('previewModal');
    const previewCloseBtnEl = document.getElementById('previewCloseBtn');
    const previewProgressEl = document.getElementById('previewProgress');
    const previewQuestionContainerEl = document.getElementById('previewQuestionContainer');
    const previewPrevBtnEl = document.getElementById('previewPrevBtn');
    const previewNextBtnEl = document.getElementById('previewNextBtn');
    const previewSubmitBtnEl = document.getElementById('previewSubmitBtn');


    document.addEventListener('DOMContentLoaded', initializeSurveyBuilder); // Initialize the application
    
    async function initializeSurveyBuilder() {
        console.log("Initializing Survey Builder...");
        setupEventListeners();
        await loadBuilderDropdowns();
        await loadCustomTemplates();
        
        const urlParams = new URLSearchParams(window.location.search);
        const surveyIdFromUrl = urlParams.get('survey_id');

        if (surveyIdFromUrl) {
            console.log("Mode: Editing Existing Survey");
            currentSurveyId = surveyIdFromUrl;
            await loadSurveyForEditing(currentSurveyId);
        } else {
            console.log("Mode: Creating New Survey");
            const title = urlParams.get('title');
            const officeId = urlParams.get('office_id');
            const serviceId = urlParams.get('service_id');
            
            if (title) document.getElementById('surveyTitle').value = title;
            if (officeId) {
                document.getElementById('surveyOffice').value = officeId;
                await handleBuilderOfficeChange();
                if (serviceId) {
                    document.getElementById('surveyService').value = serviceId;
                }
            }
            surveyQuestions = [...defaultTemplate];
            renderQuestions();
        }
        setupSortable();
    }

    function setupEventListeners() {
        // Helper to keep code clean and crash-proof
        const addListener = (id, event, handler) => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener(event, handler);
            }
        };

          // --- Main Survey Actions ---
        addListener('saveSurvey', 'click', () => saveSurvey('update_details'));
        addListener('publishSurvey', 'click', () => saveSurvey('publish'));
        addListener('previewSurvey', 'click', openPreview);

    
        addListener('surveyTemplate', 'change', loadTemplate); 
        
    
        addListener('saveAsTemplate', 'click', openTemplateModal); 
        addListener('templateForm', 'submit', saveTemplate);
        
        addListener('addQuestion', 'click', () => openQuestionModal());
        addListener('questionForm', 'submit', saveQuestion);
        addListener('closeTemplateModal', 'click', closeTemplateModal);
        addListener('cancelTemplate', 'click', closeTemplateModal);
        addListener('closeQuestionModal', 'click', () => closeQuestionModal());
        addListener('cancelQuestion', 'click', () => closeQuestionModal());
    
        // --- Live Preview Modal Buttons ---
        addListener('previewCloseBtn', 'click', closePreview);
        addListener('previewPrevBtn', 'click', () => {
            if (previewCurrentIndex > 0) {
                previewCurrentIndex--;
                renderPreviewQuestion();
            }
        });
        addListener('previewNextBtn', 'click', () => {
            if (previewCurrentIndex < surveyQuestions.length - 1) {
                previewCurrentIndex++;
                renderPreviewQuestion();
            }
        });

        // --- Form Inputs ---
        addListener('surveyOffice', 'change', handleBuilderOfficeChange);
    }

    async function loadBuilderDropdowns() {
            console.log("Loading office and service dropdowns...");
            try {
                const [officesRes, servicesRes] = await Promise.all([
                    fetch('api/offices.php'),
                    fetch('api/services.php')
                ]);
                const officesResult = await officesRes.json();
                const servicesResult = await servicesRes.json();

                if (officesResult.success) {
                    window.offices = officesResult.data;
                    const officeSelect = document.getElementById('surveyOffice');
                    officeSelect.innerHTML = '<option value="">Select Office</option>';
                    window.offices.forEach(o => {
                        officeSelect.innerHTML += `<option value="${o.id}">${o.name}</option>`;
                    });
                }
                if (servicesResult.success) {
                    window.services = servicesResult.data;
                    document.getElementById('surveyService').innerHTML = '<option value="">Select an office first</option>';
                    document.getElementById('surveyService').disabled = true;
                }
            } catch (error) {
                console.error("Failed to load dropdown data:", error);
                showToastNotification("Could not load office/service data.", "error");
            }
    }

    function handleBuilderOfficeChange() {
        const officeId = document.getElementById('surveyOffice').value;
        const serviceSelect = document.getElementById('surveyService');
        
        if (!officeId) {
            serviceSelect.innerHTML = '<option value="">Select an office first</option>';
            serviceSelect.disabled = true;
            return;
        }

        const relevantServices = window.services.filter(s => s.office_id == officeId); // Filter the services we already loaded

        serviceSelect.innerHTML = '<option value="">Select Service</option>';
        relevantServices.forEach(service => {
            const option = document.createElement('option');
            option.value = service.id;
            option.textContent = service.name;
            serviceSelect.appendChild(option);
        });
        
        serviceSelect.disabled = false;
    }


    async function loadSurveyForEditing(surveyId) {
        console.log(`Fetching data for survey ID: ${surveyId}`);
        try {
            const response = await fetch(`api/surveys.php?id=${surveyId}`);
            const result = await response.json();

            if (result.success) {
                const survey = result.data;
                document.getElementById('surveyTitle').value = survey.title;
                document.getElementById('surveyOffice').value = survey.office_id;
                
                handleBuilderOfficeChange(); // Load services for the selected offic
                setTimeout(() => {  // Use a small timeout to allow the service dropdown to populate before setting its value
                    document.getElementById('surveyService').value = survey.service_id;
                }, 100);

                if (survey.questions_json) { // The API sends a JSON string, so we must parse it, and we must check if it's null or empty first.
                    try {
                        surveyQuestions = JSON.parse(survey.questions_json);
                    } catch(e) {
                        console.error("Could not parse questions JSON from API:", e);
                        surveyQuestions = []; // Default to empty if parsing fails
                    }
                } else {
                    surveyQuestions = [];
                }
                renderQuestions();

                const saveBtn = document.getElementById('saveSurvey'); // Get references to all the buttons we need to control.
                const publishBtn = document.getElementById('publishSurvey');
                const addQuestionBtn = document.getElementById('addQuestion');
                const addQuestionBottomBtn = document.getElementById('addQuestionBottom');
                
                if (parseInt(survey.is_locked) === 1) {
                    console.log("Survey is locked. Disabling UI."); // If the survey is LOCKED, disable all editing controls.
                    if (saveBtn) saveBtn.disabled = true;
                    if (publishBtn) publishBtn.disabled = true;
                    if (addQuestionBtn) addQuestionBtn.disabled = true;
                    if (addQuestionBottomBtn) addQuestionBottomBtn.disabled = true;
                    showToastNotification("This survey is locked and cannot be edited.", "info");
                } else {
                    console.log("Survey is a draft. Enabling UI.");
                    if (saveBtn) saveBtn.disabled = false;
                    if (publishBtn) publishBtn.disabled = false;
                    if (addQuestionBtn) addQuestionBtn.disabled = false;
                    if (addQuestionBottomBtn) addQuestionBottomBtn.disabled = false;
                }
            } else {
                showToastNotification(result.message, 'error');
            }
        } catch (error) {
            console.error("Error loading survey for editing:", error);
        }
    }

    function updateButtonStates(status) {
        const saveBtn = document.getElementById('saveDraft');
        const publishBtn = document.getElementById('publishSurvey');
        const addQuestionBtn = document.getElementById('addQuestion');
        const addQuestionBottomBtn = document.getElementById('addQuestionBottom');

        if (status === 'active') {
            saveBtn.disabled = true; // --- UI State for an ACTIVE survey ---
            addQuestionBtn.disabled = true;
            addQuestionBottomBtn.disabled = true;
            
            publishBtn.innerHTML = '<i class="fas fa-eye-slash mr-2"></i> Unpublish';  // Make the button an "Unpublish" button
            publishBtn.className = 'bg-jru-orange text-white px-4 py-2 rounded-lg hover:bg-orange-600 flex items-center';
            publishBtn.onclick = () => updateSurveyStatus('draft'); // Its action is to set status to 'draft'
            publishBtn.disabled = false;

        } else { // Assumes 'draft' status
            saveBtn.disabled = false; // --- UI State for a DRAFT survey ---
            addQuestionBtn.disabled = false;
            addQuestionBottomBtn.disabled = false;

            publishBtn.innerHTML = '<i class="fas fa-rocket mr-2"></i> Publish Survey'; // Make the button a "Publish" button
            publishBtn.className = 'bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 flex items-center';
            publishBtn.onclick = () => updateSurveyStatus('active'); // Its action is to set status to 'active'
            publishBtn.disabled = false;
        }
    }

    async function saveSurvey(action) {
        console.log(`Attempting to save survey with action: ${action}`);

        const surveyTitle = document.getElementById('surveyTitle').value;
        const officeId = document.getElementById('surveyOffice').value;
        const serviceId = document.getElementById('surveyService').value;

        if (!surveyTitle || !officeId || !serviceId) {
            showToastNotification("Please provide a Title, Office, and Service.", "error");
            return;
        }
        
        if (action === 'publish') {
            try {
                await showConfirmationModal({
                    title: 'Publish Survey',
                    message: 'Once published, this survey can receive responses and will be locked if it gets a response. Are you sure?',
                    actionText: 'Yes, Publish'
                });
            } catch (error) {
                console.log("Publish cancelled by user.");
                return;
            }
        }

        const surveyData = {
            title: surveyTitle,
            office_id: officeId,
            service_id: serviceId,
            questions: surveyQuestions, // Just the array of questions
            action: action // Tell the API if we're saving a draft or publishing
        };

        let url;
        let method;

        if (currentSurveyId) {
            url = `api/surveys.php?id=${currentSurveyId}`;
            method = 'PUT';
        } else {
            url = 'api/surveys.php';
            method = 'POST';
        }
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(surveyData)
            });
            const result = await response.json();

            if (result.success) {
                showToastNotification(result.message, 'success');
            
                if (result.data && result.data.new_id) {   // If we just created the survey, store its new ID and update the URL!
                    currentSurveyId = result.data.new_id;
                    window.history.pushState({}, '', `survey-builder.php?survey_id=${currentSurveyId}`);
                }

                if (action === 'publish') {
                    setTimeout(() => window.location.href = 'survey-management.php', 1500);
                }
            } else {
                throw new Error(result.message || "An unknown error occurred.");
            }
        } catch (error) {
            console.error("Error saving survey:", error);
            showToastNotification(error.message, 'error');
        }
    }
        
    async function publishSurvey() {
        if (!currentSurveyId) {
            showToastNotification("Please save the survey as a draft before publishing.", "error");
            return;
        }
        console.log(`Preparing to publish survey with ID: ${currentSurveyId}`);

        try {
            await showConfirmationModal({
                title: 'Publish Survey',
                message: 'Are you sure? Once published, the survey structure will be locked.',
                actionText: 'Yes, Publish',
                destructive: false
            });

            const response = await fetch(`api/surveys.php?id=${currentSurveyId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'publish' })
            });

            const result = await response.json();

            if (result.success) { 
                showToastNotification('Survey published successfully! It is now live.', 'success'); // This handles a successful first-time publish.
                document.getElementById('publishSurvey').disabled = true; // Optionally disable the buttons after a successful publish.
                document.getElementById('saveDraft').disabled = true;
            } else {
                if (result.message && result.message.includes("already active")) { // This 'else' block runs if the API returns success: false.
                    showToastNotification("This survey is already published and live.", 'info'); // If it's the "already published" error, show a friendly info message.
                    document.getElementById('publishSurvey').disabled = true; // Also, disable the buttons to prevent further confusion.
                    document.getElementById('saveDraft').disabled = true;
                } else {
                    showToastNotification(result.message || "Failed to publish survey.", 'error'); // For any other unexpected error, show the red error toast.
                }
            }
        } catch (error) {
            if (error) {
                console.error("Error during publish process:", error);
            } else {
                console.log("Publish was cancelled by user.");
            }
        }
    }


    async function updateSurveyStatus(newStatus) {
        if (!currentSurveyId) { // Safety check: We can't publish/unpublish a survey that hasn't been saved at least once.
            showToastNotification("Please save the survey as a draft first.", "error");
            return;
        }
        const isPublishing = newStatus === 'active'; // Determine the text for the confirmation modal based on the action.
        const modalTitle = isPublishing ? 'Publish Survey' : 'Unpublish Survey';
        const modalMessage = isPublishing 
            ? 'The survey will become live and can accept responses. The question structure will be locked.'
            : 'The survey will be taken offline and reverted to a draft. You will be able to edit its questions again.';
        const modalActionText = isPublishing ? 'Yes, Publish' : 'Yes, Unpublish';

        try {
            await showConfirmationModal({  // Show the confirmation modal and wait for the user's choice.
                title: modalTitle,
                message: modalMessage,
                actionText: modalActionText,
                style: 'info' 
            });

            const response = await fetch(`api/surveys.php?id=${currentSurveyId}`, {  // User clicked "Yes". Proceed to call the API.
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({   // Send the correct body that the PHP API expects.
                    action: 'change_status', 
                    status: newStatus 
                })
            });

            const result = await response.json(); // Handle the API's response.
            if (result.success) {
                showToastNotification(`Survey status successfully updated to '${newStatus}'.`, 'success');
                loadSurveyForEditing(currentSurveyId); // Reload the survey's data to refresh the UI and button states.
            } else {
                showToastNotification(result.message, 'error');
            }

        } catch (error) {
            if (error) {
                console.error("Error in updateSurveyStatus:", error); // This block runs if the user clicks "Cancel" or if a network error occurs.
            } else {
                console.log("Status change was cancelled by the user.");
            }
        }
    }

    function renderQuestions() { // Render questions in the builder
        questionsList.innerHTML = '';
        
        surveyQuestions.forEach((question, index) => {
            const questionElement = createQuestionElement(question, index);
            questionsList.appendChild(questionElement);
        });
    }

    function createQuestionElement(question, index) {  // Create question element
        const div = document.createElement('div');
        div.className = 'question-item bg-gray-50 border border-gray-200 rounded-lg p-4';
        div.dataset.questionId = question.id;
        
        const typeIcon = getTypeIcon(question.type);
        const typeLabel = getTypeLabel(question.type);
        
        div.innerHTML = `
            <div class="flex items-start space-x-4">
                <div class="drag-handle text-gray-400 hover:text-gray-600 cursor-grab mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-500">${index + 1}.</span>
                            <span class="text-sm font-medium text-gray-700">${question.title || 'Question'}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="${typeIcon} mr-1"></i>
                                ${typeLabel}
                            </span>
                            ${question.required ? '<span class="text-red-500 text-xs">*</span>' : '<span class="text-gray-400 text-xs">Optional</span>'}
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="editQuestion(${question.id})" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="duplicateQuestion(${question.id})" class="text-gray-400 hover:text-green-600 transition-colors">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button onclick="deleteQuestion(${question.id})" class="text-gray-400 hover:text-red-600 transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">${question.text}</p>
                    ${question.help ? `<p class="text-xs text-gray-500 italic">${question.help}</p>` : ''}
                </div>
            </div>
        `;
        
        return div;
    }

    function getTypeIcon(type) {
        const icons = {
            'likert': 'fas fa-smile',      // Icon for Emoji Scale
            'rating': 'fas fa-star',      // Icon for Star Rating
            'textarea': 'fas fa-align-left' // Icon for Text Response
        };
        return icons[type] || 'fas fa-question-circle';
    }

    function getTypeLabel(type) {
        const labels = {
            'likert': 'Emoji Scale',
            'rating': 'Star Rating',
            'textarea': 'Text Response'
        };
        return labels[type] || 'Unknown';
    }

    function renderPreview() {  // Render preview
        const surveyTitle = document.getElementById('surveyTitle').value;
        const surveyOffice = document.getElementById('surveyOffice').selectedOptions[0].text;
        const surveyService = document.getElementById('surveyService').selectedOptions[0].text;
        
        let previewHTML = `
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="font-semibold text-blue-900 text-sm">${surveyTitle}</h3>
                <p class="text-xs text-blue-700">${surveyOffice} - ${surveyService}</p>
            </div>
        `;
        
        surveyQuestions.forEach((question, index) => {
            previewHTML += `
                <div class="mb-4 p-3 border border-gray-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <span class="text-xs font-medium text-gray-500 mr-2">${index + 1}.</span>
                        <span class="text-xs font-medium text-gray-700">${question.title || 'Question'}</span>
                        ${question.required ? '<span class="text-red-500 text-xs ml-1">*</span>' : ''}
                    </div>
                    <p class="text-xs text-gray-600 mb-2">${question.text}</p>
                    ${renderPreviewInput(question)}
                </div>
            `;
        });
        
        surveyPreview.innerHTML = previewHTML;
    }


    function renderPreviewInput(question) { // Render preview input based on question type
        switch (question.type) {
            case 'likert':
                return `
                    <div class="flex space-x-1">
                        ${[1,2,3,4,5].map(i => `
                            <div class="w-6 h-6 border border-gray-300 rounded text-xs flex items-center justify-center">${i}</div>
                        `).join('')}
                    </div>
                `;
                case 'rating':
                return `
                    <div class="flex space-x-1">
                        ${[1,2,3,4,5].map(i => `
                            <i class="fas fa-star text-gray-300 text-sm"></i>
                        `).join('')}
                    </div>
                `;
            case 'text':
                return '<div class="w-full h-6 border border-gray-300 rounded bg-gray-50"></div>';
            
            default:
                return '<div class="w-full h-6 border border-gray-300 rounded bg-gray-50"></div>';
        }
    }
    
    function setupSortable() { // Setup sortable functionality
        new Sortable(questionsList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                const movedQuestion = surveyQuestions.splice(oldIndex, 1)[0]; // Reorder questions array
                surveyQuestions.splice(newIndex, 0, movedQuestion);
                
                renderQuestions();
                renderPreview();
            }
        });
    }

    function openQuestionModal(questionId = null) {  // Question management functions
        currentEditingQuestion = questionId;
        
        if (questionId) {
            const question = surveyQuestions.find(q => q.id === questionId);  // Logic to load existing question data into the modal...
            if (question) {
                document.getElementById('questionTitle').value = question.title || '';
                document.getElementById('questionType').value = question.type;
                document.getElementById('questionText').value = question.text;
                document.getElementById('questionHelp').value = question.help || '';
                document.querySelector(`input[name="required"][value="${question.required}"]`).checked = true;
            }
        } else {
            document.getElementById('questionForm').reset(); // Logic for a new question: just reset the form.
        }
        
        questionModal.classList.remove('hidden');
    }

    function closeQuestionModal() {
        questionModal.classList.add('hidden');
        currentEditingQuestion = null;
    }

    function saveQuestion(e) {
        e.preventDefault();
        
        const questionTitle = document.getElementById('questionTitle').value; //Get or Read the values from ALL form fields ---
        const questionText = document.getElementById('questionText').value;
        const helpText = document.getElementById('questionHelp').value;

        if (!questionTitle || !questionText) {  // Basic validation to ensure the most important fields are not empty
            showToastNotification("Question Title and Full Question Text are required.", "error");
            return; // Stop the function if validation fails
        }

        const questionData = { //question Data object
            id: currentEditingQuestion || Date.now(),
            type: document.getElementById('questionType').value,
            title: questionTitle, //value from the 'questionTitle' input
            text: questionText,   // value from the 'questionText' input
            help: helpText,     // value from the 'questionHelp' input
            required: document.querySelector('input[name="required"]:checked').value === 'true'
        };
        
        if (currentEditingQuestion) { //Logic for updating vs. adding is correct.
            const index = surveyQuestions.findIndex(q => q.id === currentEditingQuestion);
            surveyQuestions[index] = questionData;
        } else {
            surveyQuestions.push(questionData);
        }
        
        renderQuestions();       //Clear user feedback AFTER the save is complete // Re-render the main question list
        closeQuestionModal();   // Close the modal window
        showToastNotification("Question saved successfully!", "success"); 
    }

    function editQuestion(questionId) {
        openQuestionModal(questionId);
    }

    function duplicateQuestion(questionId) {
        const question = surveyQuestions.find(q => q.id === questionId);
        if (question) {
            const duplicated = { ...question, id: Date.now() };
            const index = surveyQuestions.findIndex(q => q.id === questionId);
            surveyQuestions.splice(index + 1, 0, duplicated);
            renderQuestions();
            renderPreview();
        }
    }

    async function deleteQuestion(questionId) {
        try {
            await showConfirmationModal({ // 'await' the result of our modal function. The code will PAUSE here until the user clicks one of the buttons.
                title: 'Delete Question',
                message: 'Are you sure you want to remove this question from the survey?',
                actionText: 'Delete' // This will be the text on the red button
            });

            console.log("User confirmed deletion. Deleting question:", questionId); // If the user clicks "Delete", the promise resolves, and the code continues here.
            surveyQuestions = surveyQuestions.filter(q => q.id !== questionId);
            renderQuestions();
            renderPreview();
            showToastNotification("Question deleted.", "success");

        } catch (error) {
            console.log("User cancelled question deletion."); // If the user clicks "Cancel", the promise rejects, and the code jumps directly to this catch block, skipping the deletion logic.
        }
    }

    async function loadTemplate() {
        const templateSelect = document.getElementById('surveyTemplate');
        const selectedValue = templateSelect.value;

        if (!selectedValue) {
            return;
        }

        const selectedText = templateSelect.options[templateSelect.selectedIndex].text;

        try {
            await showConfirmationModal({
                title: 'Load Template',
                message: `Are you sure you want to load the "${selectedText}" template? This will replace all current questions.`,
                actionText: 'Yes, Load Template'
            });

            if (selectedValue === 'standard') {
                surveyQuestions = [...defaultTemplate];
                renderQuestions();
                showToastNotification("Standard Service Template loaded.", "success");
                return; 
            } 
            
            if (selectedValue === 'blank') {
                surveyQuestions = [];
                renderQuestions();
                showToastNotification("Blank survey loaded.", "success");
                return;
            }

            // This 'else' block now ONLY runs for custom templates with numeric IDs
            const response = await fetch(`api/templates.php?id=${selectedValue}`);
            const result = await response.json();
            if (result.success && result.data) {
                surveyQuestions = JSON.parse(result.data.questions_json);
                renderQuestions();
                showToastNotification(`Template "${result.data.template_name}" loaded.`, 'success');
            } else {
                throw new Error(result.message || "Template could not be found.");
            }

        } catch (error) {
            if (error.message) {  // runs if the user clicks "Cancel" or if a real error occurs
                console.error("Error loading template:", error);
                showToastNotification(error.message, "error");
            } else {
                console.log("Template load was cancelled by the user.");
            }
        } finally {
            templateSelect.value = "";
        }
    }


    async function loadCustomTemplates() {
        try {
            const response = await fetch('api/templates.php');
            const result = await response.json();
            if (result.success && result.data.length > 0) {
                const templateSelect = document.getElementById('surveyTemplate');
                const divider = document.createElement('option');
                divider.disabled = true;
                divider.textContent = '--- My Saved Templates ---';
                templateSelect.appendChild(divider);
                result.data.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.template_name;
                    templateSelect.appendChild(option);
                });
            }
        } catch (error) { console.error("Could not load custom templates:", error); }
    }


       function openTemplateModal() {
            document.getElementById('templateForm').reset();
            document.getElementById('templateModal').classList.remove('hidden');
        }
        function closeTemplateModal() {
            document.getElementById('templateModal').classList.add('hidden');
        }

    async function saveTemplate(e) {
        e.preventDefault();
        const templateName = document.getElementById('templateName').value;
        const templateDescription = document.getElementById('templateDescription').value;
        if (!templateName || surveyQuestions.length === 0) {
            showToastNotification("Template Name is required and survey cannot be empty.", "error");
            return;
        }
        const templateData = { template_name: templateName, description: templateDescription, questions: surveyQuestions };
        try {
            const response = await fetch('api/templates.php', { method: 'POST', body: JSON.stringify(templateData), headers: { 'Content-Type': 'application/json' } });
            const result = await response.json();
            if (result.success) {
                showToastNotification("Template saved successfully!", "success");
                closeTemplateModal();
                // Refresh dropdown
                document.getElementById('surveyTemplate').innerHTML = '<option value="">Select a template...</option><option value="default">Standard Template</option><option value="blank">Blank Survey</option>';
                loadCustomTemplates();
            } else {
                throw new Error(result.message);
            }
        } catch (error) { showToastNotification(error.message, "error"); }
    }

    function openPreview() {
        if (surveyQuestions.length === 0) {
            showToastNotification("Add at least one question to preview the survey.", "info");
            return;
        }
        previewCurrentIndex = 0;
        renderPreviewQuestion();
        if (previewModalEl) {
            previewModalEl.classList.remove('hidden');
        }
    }

    function closePreview() {
        if (previewModalEl) {
            previewModalEl.classList.add('hidden');
        }
    }

    function renderPreviewQuestion() {
        const question = surveyQuestions[previewCurrentIndex];
        if (!question) return;

        if (previewProgressEl) {  // Update progress text
            previewProgressEl.textContent = `Question ${previewCurrentIndex + 1} of ${surveyQuestions.length}`;
        }

        let inputHtml = '';
        switch (question.type) {
            case 'likert':
                inputHtml = `
                    <div class="flex justify-around items-center pt-8">
                    <span class="preview-emoji" title="Excellent">😄</span>
                     <span class="preview-emoji" title="Very Good">😊</span>
                     <span class="preview-emoji" title="Good">😐</span>
                      <span class="preview-emoji" title="Fair">🙁</span>
                     <span class="preview-emoji" title="Poor">😞</span>
                    </div>`;
                break;
            case 'rating':
                inputHtml = `
                    <div class="flex justify-center items-center pt-8">
                        <span class="preview-star" title="Excellent">★★★★★</span>
                        <span class="preview-star" title="Very Good">★★★★</span>
                        <span class="preview-star" title="Good">★★★</span>
                        <span class="preview-star" title="Poor">★★</span>
                        <span class="preview-star" title="Fair">★</span>
                    </div>`;
                break;
            case 'textarea':
                inputHtml = `<textarea class="w-full h-32 p-2 border border-gray-300 rounded-md mt-4" placeholder="Type your response here..."></textarea>`;
                break;
            default:
                inputHtml = `<p class="text-center text-gray-500 mt-8">Unsupported question type for preview.</p>`;
        }

        if (previewQuestionContainerEl) { // Render the full question content
            previewQuestionContainerEl.innerHTML = `
                <p class="text-center text-gray-500 text-sm font-medium mb-2">${question.title || 'Question'}</p>
                <p class="text-center text-xl text-gray-800">${question.text}</p>
                ${inputHtml}
            `;
        }

        // Update button visibility and state
        if (previewPrevBtnEl) previewPrevBtnEl.disabled = (previewCurrentIndex === 0);
        
        if (previewNextBtnEl && previewSubmitBtnEl) {
            if (previewCurrentIndex === surveyQuestions.length - 1) {
                // Last question
                previewNextBtnEl.classList.add('hidden');
                previewSubmitBtnEl.classList.remove('hidden');
            } else {
                // Not the last question
                previewNextBtnEl.classList.remove('hidden');
                previewSubmitBtnEl.classList.add('hidden');
            }
        }
    }


    function showToastNotification(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        if (!toast) {
            alert(`${type.toUpperCase()}: ${message}`);
            return;
        }
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');

        clearTimeout(toastTimer);
        toastMessage.textContent = message;

        toast.classList.remove('bg-green-500', 'bg-red-500', 'bg-blue-500');

        if (type === 'success') {
            toast.classList.add('bg-green-500');
            toastIcon.className = 'fas fa-check-circle mr-3 text-xl';
        } else if (type === 'info') { 
            toast.classList.add('bg-blue-500');
            toastIcon.className = 'fas fa-info-circle mr-3 text-xl';
        } else { // 'error'
            toast.classList.add('bg-red-500');
            toastIcon.className = 'fas fa-exclamation-circle mr-3 text-xl';
        }
        
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.remove('opacity-0'), 10);

        const duration = type === 'success' ? 3000 : 5000;
        
        toastTimer = setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, duration);
    }
    
    function showConfirmationModal({ title, message, actionText = 'Confirm' }) { // Returns a Promise that resolves or rejects based on user action
        const confirmationModal = document.getElementById('confirmationModal');
        const confirmTitle = document.getElementById('confirmationTitle');
        const confirmMessage = document.getElementById('confirmationMessage');
        const confirmActionBtn = document.getElementById('confirmActionBtn');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');

        if (!confirmationModal) {
            if (window.confirm(`${title}\n\n${message}`)) { // If the modal HTML isn't on the page, fall back to the basic browser confirm
                return Promise.resolve(); // User clicked "OK"
            } else {
                return Promise.reject();  // User clicked "Cancel"
            }
        }
        return new Promise((resolve, reject) => { // This returns a Promise, which lets us use 'await'
            confirmTitle.textContent = title; // Set the text for this specific confirmation
            confirmMessage.textContent = message;
            confirmActionBtn.textContent = actionText;
            confirmationModal.classList.remove('hidden');

            confirmActionBtn.onclick = () => {  // When the main action button is clicked, close the modal and resolve the promise (succeed)
                confirmationModal.classList.add('hidden');
                resolve();
            };

            confirmCancelBtn.onclick = () => {
                confirmationModal.classList.add('hidden'); // When the cancel button is clicked, close the modal and reject the promise (fail/cancel)
                reject(); 
            };
        });
    }

        
        