        // Holds all information for the user's entire session.
    const surveyState = {
        surveyId: null,
        respondent: { id: null, type: null, identifier: null, first_name: null, last_name: null, role: null },
        surveyData: null,
        answers: {},
        currentQuestionIndex: 0,
    };

    let isRegistering = false;

     document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        surveyState.surveyId = urlParams.get('id');
        if (!surveyState.surveyId) {
            document.getElementById('mainContainer').innerHTML = `<div class="p-8 text-center text-red-600">Error: No Survey ID was provided.</div>`;
            return;
        }
        initializeTakeSurveyFlow();
    });

    function showScreen(screenName, message = '') {
        const screens = {
            identity: document.getElementById('identityScreen'),
            student: document.getElementById('studentScreen'),
            guest: document.getElementById('guestScreen'),
            survey: document.getElementById('surveyScreen'),
            loading: document.getElementById('loadingScreen'),
            thankYou: document.getElementById('thankYouScreen')
        };
        Object.values(screens).forEach(screen => {
            if (screen) screen.classList.add('hidden');
        });
        if (screens[screenName]) {
            if (screenName === 'loading') {
                const loadingMessageEl = document.getElementById('loadingMessage');
                if (loadingMessageEl) loadingMessageEl.textContent = message;
            }
            screens[screenName].classList.remove('hidden');
        }
    }


    function showInlineError(screenName, message) {
        // First, hide all error messages to clear any old ones
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
            el.innerHTML = '';
        });

        // Find the specific error container for the current screen
        const errorContainer = document.getElementById(`${screenName}Error`);
        if (errorContainer) {
            errorContainer.innerHTML = `<p>${message}</p>`;
            errorContainer.classList.remove('hidden');
        }
    }

    function onGoogleLibraryLoad() {
        console.log("Google GSI library has loaded.");
        // Check if the student screen is already visible. If so, render the button.
        if (!document.getElementById('studentScreen').classList.contains('hidden')) {
            const studentConsentCheck = document.getElementById('studentConsentCheck');
            if (studentConsentCheck) {
                // Triggering a 'change' event will call our renderGoogleButton function.
                studentConsentCheck.dispatchEvent(new Event('change'));
            }
        }
    }

    function initializeTakeSurveyFlow() {
        // Get references to all elements
        const studentBtn = document.getElementById('studentBtn');
        const guestBtn = document.getElementById('guestBtn');
        const goBackBtns = document.querySelectorAll('.go-back-btn');
        const studentConsentCheck = document.getElementById('studentConsentCheck');
        const googleSignInContainer = document.getElementById('googleSignInButton');
        const guestConsentCheck = document.getElementById('guestConsentCheck');
        const guestContinueBtn = document.getElementById('guestContinueBtn');
        const guestForm = document.getElementById('guestForm');
        const termsModal = document.getElementById('termsModal');
        const viewTermsLinks = document.querySelectorAll('#viewTermsStudent, #viewTermsGuest');
        const closeTermsModalBtns = document.querySelectorAll('#closeTermsModal, #closeTermsModalFooter'); // Fixed

        // Event Listeners
        studentBtn.addEventListener('click', () => {
            showScreen('student');
            renderGoogleButton();
        });
        
        guestBtn.addEventListener('click', () => showScreen('guest'));
        goBackBtns.forEach(btn => btn.addEventListener('click', () => showScreen('identity')));
        studentConsentCheck.addEventListener('change', renderGoogleButton);
        guestConsentCheck.addEventListener('change', () => {
            guestContinueBtn.disabled = !guestConsentCheck.checked;
        });

            // --- GUEST FORM SUBMISSION ---
        guestForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // This now correctly includes the 'role' from the dropdown.
            const dataToSend = {
                type: 'guest',
                identifier: document.getElementById('guestEmail').value,
                first_name: document.getElementById('guestFirstName').value,
                last_name: document.getElementById('guestLastName').value,
                role: document.getElementById('guestRole').value
            };
            registerAndProceed(dataToSend);
        });

        viewTermsLinks.forEach(link => link.addEventListener('click', (e) => { e.preventDefault(); termsModal?.classList.remove('hidden'); }));
        closeTermsModalBtns.forEach(btn => btn.addEventListener('click', () => termsModal?.classList.add('hidden')));

        showScreen('identity');
    }

    // --- Google Sign-In Logic ---
    function renderGoogleButton() {
        const googleSignInContainer = document.getElementById('googleSignInButton');
        if (!document.getElementById('studentConsentCheck').checked) {
            googleSignInContainer.innerHTML = `<div class="w-full text-center py-3 px-4 border border-gray-200 bg-gray-50 rounded-lg"><span class="font-medium text-gray-500">Please agree to the terms to enable sign-in.</span></div>`;
            return;
        }
        try {
            if (typeof google === 'undefined' || typeof google.accounts === 'undefined') {
                googleSignInContainer.innerHTML = `<div class="w-full text-center py-3 px-4 border border-gray-200 bg-gray-50 rounded-lg"><i class="fas fa-spinner fa-spin"></i><span class="font-medium text-gray-500 ml-2">Loading Sign-In...</span></div>`;
                return;
            }
            google.accounts.id.initialize({
                client_id: "913799866499-p05hvm7muoaiqogtp85d0s95jiuavfuv.apps.googleusercontent.com",
                callback: handleGoogleSignIn
            });
            google.accounts.id.renderButton(googleSignInContainer, { theme: "outline", size: "large", width: "380", text: "signin_with" });
        } catch (error) {
            googleSignInContainer.innerHTML = `<p class="text-center text-red-500">Could not load Google Sign-In.</p>`;
        }
    }

    function handleGoogleSignIn(googleResponse) {
        const userInfo = parseJWT(googleResponse.credential);
        if (!userInfo || !isJruEmail(userInfo.email)) {
            showInlineError('student', "Sign-in failed. Please use a valid JRU email account (@my.jru.edu or @jru.edu).");
            return;
        }
        const dataToSend = {
            type: 'student',
            identifier: userInfo.email,
            first_name: userInfo.given_name,
            last_name: userInfo.family_name
        };
        registerAndProceed(dataToSend);
    }

    // --- Core API and Survey Flow ---
    async function registerAndProceed(dataToSend) {
        if (isRegistering) return;
        isRegistering = true;
        showScreen('loading', 'Verifying your session...');
        try {
            const response = await fetch('api/register-respondent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                surveyState.respondent = { ...dataToSend, id: result.data.respondent_id };
                fetchAndPrepareSurvey();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            const errorScreen = dataToSend.type === 'student' ? 'student' : 'guest';
            showScreen(errorScreen);
            showInlineError(errorScreen, `Verification Failed: ${error.message}`);
        } finally {
            isRegistering = false;
        }
    }

    async function fetchAndPrepareSurvey() {
        document.getElementById('surveyHeader').classList.remove('hidden');
        showScreen('loading', 'Loading survey questions...');
        try {
            const response = await fetch(`api/surveys.php?id=${surveyState.surveyId}`);
            const result = await response.json();
            if (result.success) {
                surveyState.surveyData = result.data;
                surveyState.surveyData.questions = JSON.parse(result.data.questions_json);
                document.getElementById('surveyTitle').textContent = surveyState.surveyData.title;
                document.getElementById('surveyDescription').textContent = surveyState.surveyData.description;
                if (surveyState.surveyData.questions?.length > 0) {
                    surveyState.currentQuestionIndex = 0;
                    renderQuestionStage();
                    showScreen('survey');
                } else {
                    showScreen('identity');
                    alert("This survey currently has no questions.");
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            showScreen('identity');
            alert(`Error: ${error.message}`);
        }
    }

    function renderQuestionStage() {
        const surveyScreen = document.getElementById('surveyScreen');
        const questions = surveyState.surveyData.questions;
        const currentIndex = surveyState.currentQuestionIndex;
        const currentQuestion = questions[currentIndex];
        
        surveyScreen.innerHTML = `
            <div class="mb-4">
                <p class="text-sm font-bold text-jru-blue">Question ${currentIndex + 1} of ${questions.length}</p>
                <div class="w-full bg-gray-200 rounded-full mt-1"><div class="bg-jru-blue h-2 rounded-full" style="width: ${((currentIndex + 1) / questions.length) * 100}%"></div></div>
            </div>
            <div class="py-4">
                <label class="block text-lg font-semibold text-gray-800 mb-2">${currentQuestion.text} ${currentQuestion.required ? '<span class="text-red-500 ml-1">*</span>' : ''}</label>
                <p class="text-sm text-gray-500 mb-4">${currentQuestion.help || ''}</p>
                ${renderInputForQuestion(currentQuestion)}
            </div>
            <div id="warning-spot" class="mt-4"></div>
            <div class="mt-8 flex justify-between items-center">
                <button id="backBtn" class="${currentIndex === 0 ? 'invisible' : ''} bg-gray-600 text-white py-2 px-6 rounded-lg font-semibold">Back</button>
                <button id="nextBtn" class="bg-jru-blue text-white py-2 px-6 rounded-lg font-semibold">${currentIndex === questions.length - 1 ? 'Finish & Submit' : 'Next'}</button>
            </div>
        `;
        document.getElementById('backBtn').onclick = handleBack;
        document.getElementById('nextBtn').onclick = handleNext;
        setupQuestionInteractivity();
    }

    function handleBack() {
        if (surveyState.currentQuestionIndex > 0) {
            surveyState.currentQuestionIndex--;
            renderQuestionStage();
        }
    }

    function handleNext() {
        const currentQuestion = surveyState.surveyData.questions[surveyState.currentQuestionIndex];
        const inputName = `q_${currentQuestion.id}`;
        const inputWrapper = document.getElementById('question-input-wrapper');
        let inputValue = null;
        const inputElement = inputWrapper.querySelector(`[name="${inputName}"]`);
        if (inputElement) {
            if (inputElement.type === 'radio') {
                const checkedRadio = inputWrapper.querySelector(`[name="${inputName}"]:checked`);
                if (checkedRadio) inputValue = checkedRadio.value;
            } else {
                inputValue = inputElement.value;
            }
        }
        const warningSpot = document.getElementById('warning-spot');
        if (currentQuestion.required && (!inputValue || inputValue.trim() === '')) {
            warningSpot.innerHTML = `<div class="text-red-600 font-semibold text-sm p-3 bg-red-50 rounded-lg"><i class="fas fa-exclamation-circle mr-2"></i>This question is required.</div>`;
            setTimeout(() => { warningSpot.innerHTML = ''; }, 3000);
            return;
        }
        warningSpot.innerHTML = '';
        surveyState.answers[inputName] = inputValue;
        if (surveyState.currentQuestionIndex < surveyState.surveyData.questions.length - 1) {
            surveyState.currentQuestionIndex++;
            renderQuestionStage();
        } else {
            submitSurveyResponse();
        }
    }

    async function submitSurveyResponse() {
        showScreen('loading', 'Submitting your feedback...');
        const finalAnswers = Object.entries(surveyState.answers).map(([key, value]) => {
            const qId = key.split('_')[1];
            const question = surveyState.surveyData.questions.find(q => q.id == qId);
            return { question_id: qId, text: question ? question.text : 'Unknown', answer: value };
        });
        const finalSubmissionData = { survey_id: surveyState.surveyId, respondent: surveyState.respondent, answers: finalAnswers };
        try {
            const response = await fetch('api/submit-response.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(finalSubmissionData) });
            const result = await response.json();
            if (result.success) {
                document.getElementById('surveyHeader').classList.add('hidden');
                document.getElementById('thankYouMessage').textContent = result.message;
                showScreen('thankYou');
            } else {
                alert(`Submission Error: ${result.message}`);
                showScreen('survey'); // Go back to the survey screen
            }
        } catch (error) {
            alert("A network error occurred. Please try submitting again.");
            showScreen('survey');
        }
    }

    function isJruEmail(email) {
        if (!email || typeof email !== 'string') {
            return false; // Return false for invalid input
        }
        // Convert the email to lowercase for a case-insensitive check
        const lowerCaseEmail = email.toLowerCase();
        // Check if the email ends with either of the valid domains
        return lowerCaseEmail.endsWith('@my.jru.edu') || lowerCaseEmail.endsWith('@jru.edu');
    }

    function parseJWT(token) {  // --- Helper Functions (including custom JWT parser) ---
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)).join(''));
            return JSON.parse(jsonPayload);
        } catch (e) { return null; }
    }

     function renderError(message) {
        surveyContainer.innerHTML = `<div class="text-center py-8 bg-red-50 p-6 rounded-lg"><i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i><h1 class="text-2xl font-bold text-red-600">An Error Occurred</h1><p class="text-gray-700 mt-2">${message}</p></div>`;
    }

    function renderLoading(message) {
        surveyContainer.innerHTML = `
            <div class="text-center py-8">
                <h1 class="text-2xl font-bold text-gray-900">${message}</h1>
                <!-- Optional: You could add a spinning icon here for a better visual effect -->
                <i class="fas fa-spinner fa-spin text-jru-blue text-4xl mt-4"></i>
            </div>
        `;
    }

   function renderInputForQuestion(question) { // --- UTILITY & HELPER FUNCTIONS ---
        const name = `q_${question.id}`;
        const savedAnswer = surveyState.answers[name] || '';

        let content = `<div id="question-input-wrapper">`;   // Use a simple DIV as a wrapper for the inputs. give it a unique ID so we can easily find it later.

        switch (question.type) {
        case 'likert': // Emoji Scale  
        const emojis = [
            { emoji: '😄', value: 5, label: 'Excellent' },
            { emoji: '😊', value: 4, label: 'Very Good' },
            { emoji: '😐', value: 3, label: 'Good' },
            { emoji: '🙁', value: 2, label: 'Fair' },
            { emoji: '😞', value: 1, label: 'Poor' }
        ];

        content += `
            <div class="flex justify-center space-x-2 md:space-x-4">
                ${emojis.map(item => `
                    <label class="emoji-label relative flex flex-col items-center cursor-pointer text-center p-2 transition-transform duration-200 ease-in-out">
                        
                        <!-- The Emoji -->
                        <span class="text-4xl md:text-5xl">${item.emoji}</span>
                        
                        <!-- The Text Label (starts hidden) -->
                        <span class="emoji-text-popup mt-2 text-xs text-gray-600 font-semibold opacity-0 transition-opacity">
                            ${item.label}
                        </span>
                        
                        <!-- The Radio Button (hidden) -->
                        <input type="radio" name="${name}" value="${item.value}" class="sr-only peer" ${savedAnswer == item.value ? 'checked' : ''}>
                        
                        <!-- The Checkmark Indicator -->
                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 mt-2 peer-checked:bg-jru-blue peer-checked:border-jru-blue"></div>
                    </label>
                `).join('')}
            </div>`;
        break;
            
            case 'rating':
                content += `<div class="flex justify-center items-center text-4xl text-gray-300 star-rating" data-selected-value="${savedAnswer}">
                    ${[5,4,3,2,1].map(i => `<i class="${i <= savedAnswer ? 'fas text-yellow-400' : 'far'} fa-star cursor-pointer p-1" data-value="${i}"></i>`).join('')}
                </div>
                <input type="hidden" name="${name}" value="${savedAnswer}">`;
                break;

            case 'textarea':
                content += `<textarea name="${name}" rows="4" class="w-full p-3 border border-gray-300 rounded-lg">${savedAnswer}</textarea>`;
                break;
            
            default:
                content += `<p class="text-red-500 italic">This question type is not supported.</p>`;
        }
        content += '</div>'; // Close the DIV tag instead of the FORM tag.
        return content;
    }

     function setupQuestionInteractivity() {
        
        // EMOJI (LIKERT) QUESTIONS ---
        const emojiLabels = document.querySelectorAll('.emoji-label');
        
        emojiLabels.forEach(label => {
            // Add hover effects for better UX
            const textPopup = label.querySelector('.emoji-text-popup');
            label.addEventListener('mouseenter', () => {
                label.style.transform = 'scale(1.15)';
                if (textPopup) textPopup.style.opacity = '1';
            });
            label.addEventListener('mouseleave', () => {
                label.style.transform = 'scale(1)';
                if (textPopup) textPopup.style.opacity = '0';
            });

            const radio = label.querySelector('input[type="radio"]');
            if (radio) {
                
                label.addEventListener('click', () => {
                    // Manually check the radio button inside this label
                    radio.checked = true;

                    //  Find ALL emoji labels within this same question
                    const allLabelsInGroup = radio.closest('.flex').querySelectorAll('.emoji-label');

                    // Loop through ALL of them to update their visual state
                    allLabelsInGroup.forEach(siblingLabel => {
                        const siblingRadio = siblingLabel.querySelector('input[type="radio"]');
                        const checkmarkDiv = siblingLabel.querySelector('.checkmark-indicator'); // Added a class for easy targeting

                        if (siblingRadio.checked) {
                            checkmarkDiv.classList.add('bg-jru-blue', 'border-jru-blue');
                        } else {
                            checkmarkDiv.classList.remove('bg-jru-blue', 'border-jru-blue');
                        }
                    });
                });
            }
        });

    const starRatingContainer = document.querySelector('.star-rating');
    if (starRatingContainer) {
        const stars = starRatingContainer.querySelectorAll('.fa-star');
        const hiddenInput = starRatingContainer.nextElementSibling; // The hidden input that stores the value

        stars.forEach(star => {
            // Handle click event to set the rating
            star.addEventListener('click', () => {
                const value = star.dataset.value;
                hiddenInput.value = value; // Set the actual value
                
                // Update the visual state of all stars in the group
                stars.forEach(s => {
                    if (parseInt(s.dataset.value) <= parseInt(value)) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'text-yellow-400');
                    } else {
                        s.classList.remove('fas', 'text-yellow-400');
                        s.classList.add('far');
                    }
                });
            });

            // Handle hover effect to show potential rating
            star.addEventListener('mouseenter', () => {
                const hoverValue = star.dataset.value;
                stars.forEach(s => {
                    if (parseInt(s.dataset.value) <= parseInt(hoverValue)) {
                        s.classList.add('text-yellow-300'); // Use a slightly different color for hover
                    }
                });

            });

            star.addEventListener('mouseleave', () => {
                 stars.forEach(s => {
                    s.classList.remove('text-yellow-300'); // Remove hover effect
                });
            });
        });
    }
}