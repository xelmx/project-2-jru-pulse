document.addEventListener('DOMContentLoaded', function () {

    // --- === DOM Element References === ---
    const studentModal = document.getElementById('studentModal');
    const studentModalContent = document.getElementById('studentModalContent');
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    const closeModalBtns = document.querySelectorAll('.closeModalBtn');
    const studentForm = document.getElementById('studentForm');
    const modalTitle = document.getElementById('modalTitle');
    const importModal = document.getElementById('importModal');
    const importModalContent = document.getElementById('importModalContent');
    const openImportModalBtn = document.getElementById('openImportModalBtn');
    const closeImportModalBtns = document.querySelectorAll('.closeImportModalBtn');
    const importForm = document.getElementById('importForm');
    const importSubmitBtn = document.getElementById('importSubmitBtn');
    const importResultsArea = document.getElementById('importResultsArea');
    const csvFileInput = document.getElementById('csv_file');
    const confirmModal = document.getElementById('confirmationModal');
    const confirmTitle = document.getElementById('confirmationTitle');
    const confirmMessage = document.getElementById('confirmationMessage');
    const confirmActionBtn = document.getElementById('confirmActionBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    let studentIdToDelete = null;
    const studentsTableBody = document.getElementById('studentsTableBody');
    const searchInput = document.getElementById('searchInput');

    // References for the dependent dropdowns
    const divisionSelect = document.getElementById('division');
    const courseOrStrandSelect = document.getElementById('course_or_strand');
    let academicData = {}; // Global variable to hold our division/course data

    // --- === Main Initialization Function === ---
    const initializePage = async () => {
        setupEventListeners();
        await fetchAcademicData();
        await fetchStudents();
    };

    // --- === API Calls & Data Handling === ---
    const fetchAcademicData = async () => {
        try {
            const response = await fetch('api/academic-data.php');
            const result = await response.json();
            if (result.success) {
                academicData = result.data;
                populateDivisionDropdown();
            }
        } catch (error) {
            console.error("Failed to load academic data:", error);
        }
    };

    const fetchStudents = async (searchTerm = '') => {
        try {
            const response = await fetch(`api/students.php?search=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            const result = await response.json();
            if (result.success) {
                renderTable(result.data);
            } else {
                studentsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-4">${result.message}</td></tr>`;
            }
        } catch (error) {
            studentsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-4">Error loading data. Please check connection.</td></tr>`;
        }
    };

    const renderTable = (students) => {
        studentsTableBody.innerHTML = '';
        if (students.length === 0) {
            studentsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-10">No students found.</td></tr>`;
            return;
        }
        students.forEach(student => {
            const row = `
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${student.student_number}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${student.first_name} ${student.last_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${student.email}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${student.division}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${student.course_or_strand}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-jru-blue hover:text-blue-800 font-semibold edit-btn" data-id="${student.id}">Edit</button>
                        <button class="text-red-600 hover:text-red-800 font-semibold ml-4 delete-btn" data-id="${student.id}">Delete</button>
                    </td>
                </tr>`;
            studentsTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // --- === Dropdown Logic === ---
    const populateDivisionDropdown = () => {
        divisionSelect.innerHTML = '<option value="">Select a Division</option>';
        for (const division in academicData) {
            const option = document.createElement('option');
            option.value = division;
            option.textContent = division;
            divisionSelect.appendChild(option);
        }
    };

    const handleDivisionChange = () => {
        const selectedDivision = divisionSelect.value;
        courseOrStrandSelect.innerHTML = '';
        if (selectedDivision && academicData[selectedDivision]) {
            courseOrStrandSelect.disabled = false;
            courseOrStrandSelect.innerHTML = '<option value="">Select a Course/Strand</option>';
            academicData[selectedDivision].forEach(course => {
                const option = document.createElement('option');
                option.value = course;
                option.textContent = course;
                courseOrStrandSelect.appendChild(option);
            });
        } else {
            courseOrStrandSelect.disabled = true;
            courseOrStrandSelect.innerHTML = '<option value="">Select a division first</option>';
        }
    };

    // --- === Modal & Form Handlers === ---
    const openStudentModal = () => { studentModal.classList.remove('hidden'); setTimeout(() => studentModalContent.classList.remove('opacity-0', '-translate-y-4'), 10); };
    const closeStudentModal = () => { studentModalContent.classList.add('opacity-0', '-translate-y-4'); setTimeout(() => studentModal.classList.add('hidden'), 300); };
    const openImportModal = () => { importForm.reset(); importResultsArea.innerHTML = ''; importResultsArea.classList.add('hidden'); importSubmitBtn.disabled = false; importSubmitBtn.querySelector('.btn-text').textContent = 'Upload & Import'; importSubmitBtn.querySelector('i').classList.add('hidden'); importModal.classList.remove('hidden'); setTimeout(() => importModalContent.classList.remove('opacity-0', '-translate-y-4'), 10); };
    const closeImportModal = () => { importModalContent.classList.add('opacity-0', '-translate-y-4'); setTimeout(() => importModal.classList.add('hidden'), 300); };

    let toastTimer;
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');
        clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.classList.remove('bg-red-500', 'bg-green-500');
        if (type === 'success') {
            toast.classList.add('bg-green-500');
            toastIcon.className = 'fas fa-check-circle mr-3 text-xl';
        } else {
            toast.classList.add('bg-red-500');
            toastIcon.className = 'fas fa-exclamation-circle mr-3 text-xl';
        }
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.remove('opacity-0'), 10);
        toastTimer = setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3000);
    }

    function showConfirmation({ title, message, actionText = 'Confirm' }) {
        return new Promise((resolve, reject) => {
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            confirmActionBtn.textContent = actionText;
            confirmModal.classList.remove('hidden');
            confirmActionBtn.onclick = () => { confirmModal.classList.add('hidden'); resolve(); };
            confirmCancelBtn.onclick = () => { confirmModal.classList.add('hidden'); reject(new Error("Action cancelled by user")); };
        });
    }

    const handleStudentFormSubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(studentForm);
        const studentData = Object.fromEntries(formData.entries());
        const isUpdating = studentData.id;
        const url = isUpdating ? `api/students.php?id=${studentData.id}` : 'api/students.php';
        const method = isUpdating ? 'PUT' : 'POST';
        try {
            const response = await fetch(url, { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(studentData) });
            const result = await response.json();
            if (result.success) {
                closeStudentModal();
                fetchStudents();
                showToast(isUpdating ? 'Student updated successfully!' : 'Student added successfully!', 'success');
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('An unexpected network error occurred.', 'error');
        }
    };

    const handleImportFormSubmit = async (event) => {
        event.preventDefault();
        if (csvFileInput.files.length === 0) {
            showToast('Please select a CSV file to upload.', 'error');
            return;
        }

        importSubmitBtn.disabled = true;
        importSubmitBtn.querySelector('.btn-text').textContent = 'Importing...';
        importSubmitBtn.querySelector('i').classList.remove('hidden');

        try {
            const response = await fetch('api/import-csv.php', {
                method: 'POST',
                body: new FormData(importForm)
            });
            const result = await response.json();
            displayImportResults(result); // Display detailed results
            if (response.ok && result.success) {
                fetchStudents(); // Refresh the main student table
            }
        } catch (error) {
            console.error('Import error:', error);
            displayImportResults({ success: false, message: 'A client-side error occurred. Check the console.' });
        } finally {
            importSubmitBtn.disabled = false;
            importSubmitBtn.querySelector('.btn-text').textContent = 'Upload & Import';
            importSubmitBtn.querySelector('i').classList.add('hidden');
        }
    };

    const displayImportResults = (result) => {
        importResultsArea.innerHTML = '';
        importResultsArea.classList.remove('hidden');
        if (!result) { importResultsArea.innerHTML = `<div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700"><p>An unknown error occurred.</p></div>`; return; }
        let html = '';
        if (result.success) {
            html += `<div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700"><h4 class="font-bold">Import Complete!</h4><p>${result.message}</p>${result.errorCount > 0 ? `<p class="mt-1">${result.errorCount} record(s) were skipped.</p>` : ''}</div>`;
        } else {
            html += `<div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700"><h4 class="font-bold">Import Failed</h4><p>${result.message || 'An error occurred.'}</p></div>`;
        }
        if (result.errors && result.errors.length > 0) {
            html += `<div class="mt-4"><h5 class="font-semibold text-gray-700">Error Details:</h5><ul class="list-disc list-inside bg-gray-100 p-3 rounded-md mt-2 text-sm text-red-800 max-h-32 overflow-y-auto">${result.errors.map(e => `<li>${e}</li>`).join('')}</ul></div>`;
        }
        importResultsArea.innerHTML = html;
    };

    // --- === Event Listeners Setup === ---
    function setupEventListeners() {
        openAddModalBtn.addEventListener('click', () => {
            studentForm.reset();
            modalTitle.textContent = 'Add New Student';
            document.getElementById('studentId').value = '';
            handleDivisionChange(); // Reset course dropdown to its initial state
            openStudentModal();
        });

        openImportModalBtn.addEventListener('click', openImportModal);
        closeModalBtns.forEach(btn => btn.addEventListener('click', closeStudentModal));
        closeImportModalBtns.forEach(btn => btn.addEventListener('click', closeImportModal));
        studentForm.addEventListener('submit', handleStudentFormSubmit);
        importForm.addEventListener('submit', handleImportFormSubmit);
        searchInput.addEventListener('input', () => fetchStudents(searchInput.value.trim()));
        divisionSelect.addEventListener('change', handleDivisionChange);
        studentsTableBody.addEventListener('click', async (event) => {
            const editBtn = event.target.closest('.edit-btn');
            const deleteBtn = event.target.closest('.delete-btn');
            if (editBtn) {
                const studentId = editBtn.dataset.id;
                const row = editBtn.closest('tr');
                modalTitle.textContent = 'Edit Student';
                studentForm.reset();
                openStudentModal();
                
                // --- THIS IS THE CORRECTED LOGIC ---
                const division = row.cells[3].textContent;
                const course = row.cells[4].textContent;

                // Populate text fields
                studentForm.elements['id'].value = studentId;
                studentForm.elements['student_number'].value = row.cells[0].textContent;
                studentForm.elements['first_name'].value = row.cells[1].textContent.split(' ')[0];
                studentForm.elements['last_name'].value = row.cells[1].textContent.split(' ').slice(1).join(' ');
                studentForm.elements['email'].value = row.cells[2].textContent;
                
                // Set the division dropdown's value
                divisionSelect.value = division;
                
                // Manually trigger the change event to populate the course dropdown
                divisionSelect.dispatchEvent(new Event('change'));
                
                // Use a minimal timeout to ensure the DOM has updated with the new options
                // This guarantees that the course value can be set correctly.
                setTimeout(() => {
                    courseOrStrandSelect.value = course;
                }, 0);
            }
            if (deleteBtn) {
                studentIdToDelete = deleteBtn.dataset.id;
                try {
                    await showConfirmation({
                        title: 'Confirm Deletion',
                        message: 'Are you sure you want to delete this student? This action cannot be undone.',
                        actionText: 'Delete'
                    });
                    const response = await fetch(`api/students.php?id=${studentIdToDelete}`, { method: 'DELETE' });
                    const result = await response.json();
                    if (result.success) {
                        fetchStudents(searchInput.value.trim());
                        showToast('Student deleted successfully!', 'success');
                    } else {
                        showToast(`Error: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.log(error.message); // Will log "Action cancelled by user"
                } finally {
                    studentIdToDelete = null;
                }
            }
        });
    }
    
    // --- Initial Load ---
    initializePage();
});