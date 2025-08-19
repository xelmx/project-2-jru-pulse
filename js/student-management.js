document.addEventListener('DOMContentLoaded', function () {
    // --- DOM Element References ---
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
    const confirmModal = document.getElementById('confirmModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let studentIdToDelete = null;
    const studentsTableBody = document.getElementById('studentsTableBody');
    const searchInput = document.getElementById('searchInput');

    // --- API Calls & Data Handling ---
    const fetchStudents = async (searchTerm = '') => {
        try {
            // UPDATED: Calls the new unified API endpoint
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
            studentsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-10">No students found. Try adding one or changing your search.</td></tr>`;
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
     
    // --- Modals ---
    const openStudentModal = () => { studentModal.classList.remove('hidden'); setTimeout(() => studentModalContent.classList.remove('opacity-0', '-translate-y-4'), 10); };
    const closeStudentModal = () => { studentModalContent.classList.add('opacity-0', '-translate-y-4'); setTimeout(() => studentModal.classList.add('hidden'), 300); };
    const openImportModal = () => { importForm.reset(); importResultsArea.innerHTML = ''; importResultsArea.classList.add('hidden'); importSubmitBtn.disabled = false; importSubmitBtn.querySelector('.btn-text').textContent = 'Upload & Import'; importSubmitBtn.querySelector('i').classList.add('hidden'); importModal.classList.remove('hidden'); setTimeout(() => importModalContent.classList.remove('opacity-0', '-translate-y-4'), 10); };
    const closeImportModal = () => { importModalContent.classList.add('opacity-0', '-translate-y-4'); setTimeout(() => importModal.classList.add('hidden'), 300); };

    // --- Form Handlers ---
    const handleStudentFormSubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(studentForm);
        const studentData = Object.fromEntries(formData.entries());
        
        // UPDATED: Determine URL and Method for RESTful API
        const isUpdating = studentData.id;
        const url = isUpdating ? `api/students.php?id=${studentData.id}` : 'api/students.php';
        const method = isUpdating ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(studentData),
            });
            const result = await response.json();
            if (result.success) {
                closeStudentModal();
                fetchStudents();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            alert('An unexpected error occurred.');
        }
    };

    const handleImportFormSubmit = async (event) => {
        event.preventDefault();
        if (csvFileInput.files.length === 0) { alert('Please select a CSV file to upload.'); return; }
        importSubmitBtn.disabled = true; importSubmitBtn.querySelector('.btn-text').textContent = 'Importing...'; importSubmitBtn.querySelector('i').classList.remove('hidden');
        try {
            const response = await fetch('api/import_csv.php', { method: 'POST', body: new FormData(importForm) });
            const result = await response.json();
            displayImportResults(result);
            if (response.ok && result.success) { fetchStudents(); }
        } catch (error) {
            displayImportResults({ success: false, message: 'A client-side error occurred. Check the console.' });
        } finally {
            importSubmitBtn.disabled = false; importSubmitBtn.querySelector('.btn-text').textContent = 'Upload & Import'; importSubmitBtn.querySelector('i').classList.add('hidden');
        }
    };

    const displayImportResults = (result) => {
        importResultsArea.innerHTML = ''; importResultsArea.classList.remove('hidden');
        if (!result) { importResultsArea.innerHTML = `<div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700"><p>An unknown error occurred.</p></div>`; return; }
        let html = '';
        if (result.success) {
            html += `<div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700"><h4 class="font-bold">Import Complete!</h4><p>${result.successCount} record(s) imported successfully.</p>${result.errorCount > 0 ? `<p class="mt-1">${result.errorCount} record(s) failed.</p>` : ''}</div>`;
        } else {
            html += `<div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700"><h4 class="font-bold">Import Failed</h4><p>${result.message || 'An error occurred.'}</p></div>`;
        }
        if (result.errors && result.errors.length > 0) {
            html += `<div class="mt-4"><h5 class="font-semibold text-gray-700">Error Details:</h5><ul class="list-disc list-inside bg-gray-100 p-3 rounded-md mt-2 text-sm text-red-800 max-h-32 overflow-y-auto">${result.errors.map(e => `<li>${e}</li>`).join('')}</ul></div>`;
        }
        importResultsArea.innerHTML = html;
    };
    
    // --- Event Listeners ---
    openAddModalBtn.addEventListener('click', () => { studentForm.reset(); document.getElementById('studentId').value = ''; modalTitle.textContent = 'Add New Student'; openStudentModal(); });
    openImportModalBtn.addEventListener('click', openImportModal);
    closeModalBtns.forEach(btn => btn.addEventListener('click', closeStudentModal));
    closeImportModalBtns.forEach(btn => btn.addEventListener('click', closeImportModal));
    studentForm.addEventListener('submit', handleStudentFormSubmit);
    importForm.addEventListener('submit', handleImportFormSubmit);
    searchInput.addEventListener('input', () => fetchStudents(searchInput.value.trim()));
    studentsTableBody.addEventListener('click', (event) => {
        const editBtn = event.target.closest('.edit-btn');
        const deleteBtn = event.target.closest('.delete-btn');
        if (editBtn) {
            const studentId = editBtn.dataset.id;
            const row = editBtn.closest('tr');
            const student = { id: studentId, student_number: row.cells[0].textContent, first_name: row.cells[1].textContent.split(' ')[0], last_name: row.cells[1].textContent.split(' ').slice(1).join(' '), email: row.cells[2].textContent, division: row.cells[3].textContent, course_or_strand: row.cells[4].textContent };
            modalTitle.textContent = 'Edit Student';
            for (const key in student) { if (studentForm.elements[key]) studentForm.elements[key].value = student[key]; }
            openStudentModal();
        }
        if (deleteBtn) { studentIdToDelete = deleteBtn.dataset.id; confirmModal.classList.remove('hidden'); }
    });
    cancelDeleteBtn.addEventListener('click', () => { confirmModal.classList.add('hidden'); studentIdToDelete = null; });
    confirmDeleteBtn.addEventListener('click', async () => {
        if (!studentIdToDelete) return;
        try {
            // UPDATED: Calls the new unified API endpoint with DELETE method
            const response = await fetch(`api/students.php?id=${studentIdToDelete}`, 
                { method: 'DELETE' });
                
            const result = await response.json();
            if (result.success) { fetchStudents(searchInput.value.trim()); } 
            else { alert('Error deleting student.'); }
        } catch (error) { console.error('Delete error:', error); } 
        finally { confirmModal.classList.add('hidden'); studentIdToDelete = null; }
    });
    fetchStudents();
});