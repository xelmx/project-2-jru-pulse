document.addEventListener('DOMContentLoaded', function () {
    // --- === DOM Element References === ---
    const userModal = document.getElementById('userModal');
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    const closeModalBtns = document.querySelectorAll('.closeModalBtn');
    const userForm = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const usersTableBody = document.getElementById('usersTableBody');
    const searchInput = document.getElementById('searchInput');
    const roleSelect = document.getElementById('role');
    const officeSelectContainer = document.getElementById('office-select-container');
    const officeSelect = document.getElementById('office_id');

    // Modal References
    const confirmModal = document.getElementById('confirmationModal');
    const confirmTitle = document.getElementById('confirmationTitle');
    const confirmMessage = document.getElementById('confirmationMessage');
    const confirmActionBtn = document.getElementById('confirmActionBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    // --- === Main Initialization === ---
    const initializePage = async () => {
        setupEventListeners();
        await fetchOffices(); // Fetch offices first to populate the dropdown
        await fetchUsers();
    };

    // --- === API Calls & Data Handling === ---
    const fetchUsers = async (searchTerm = '') => {
        try {
            const response = await fetch(`api/users.php?search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();
            renderTable(result.success ? result.data : []);
        } catch (error) {
            console.error('Fetch users error:', error);
            usersTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-4">Error loading data.</td></tr>`;
        }
    };

    const fetchOffices = async () => {
        try {
            const response = await fetch('api/offices.php'); // Your existing offices API
            const result = await response.json();
            if (result.success) {
                officeSelect.innerHTML = '<option value="">Select an office...</option>';
                result.data.forEach(office => {
                    const option = document.createElement('option');
                    option.value = office.id;
                    option.textContent = office.name;
                    officeSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Fetch offices error:', error);
        }
    };

    const renderTable = (users) => {
        usersTableBody.innerHTML = '';
        if (users.length === 0) {
            usersTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-10">No users found.</td></tr>`;
            return;
        }
        users.forEach(user => {
            const statusClass = user.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700';
            const statusText = user.is_active == 1 ? 'Active' : 'Inactive';
            const toggleActionText = user.is_active == 1 ? 'Deactivate' : 'Reactivate';
            const toggleIcon = user.is_active == 1 ? 'fa-toggle-off' : 'fa-toggle-on';
            const officeName = user.office_name || '<span class="text-gray-400">N/A</span>';

            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">${user.first_name} ${user.last_name}</td>
                    <td class="px-6 py-4 text-gray-600">${user.email}</td>
                    <td class="px-6 py-4 text-gray-600">${user.role === 'admin' ? 'Admin' : 'Office Head'}</td>
                    <td class="px-6 py-4 text-gray-600">${officeName}</td>
                    <td class="px-6 py-4"><span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 rounded-full ${statusClass}">${statusText}</span></td>
                    <td class="px-6 py-4 text-right text-lg space-x-4">
                        <button class="text-gray-400 hover:text-orange-500 toggle-status-btn" data-id="${user.id}" title="${toggleActionText} User"><i class="fas ${toggleIcon}"></i></button>
                        <button class="text-gray-400 hover:text-jru-blue edit-btn" data-id="${user.id}" title="Edit User"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>`;
            usersTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // --- === Modal & Notification Helpers === ---
    const openUserModal = () => userModal.classList.remove('hidden');
    const closeUserModal = () => userModal.classList.add('hidden');

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
    // --- === FORM & EVENT HANDLERS === ---
    const handleUserFormSubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(userForm);
        const userData = Object.fromEntries(formData.entries());
        const isUpdating = userData.id;
        const url = isUpdating ? `api/users.php?id=${userData.id}` : 'api/users.php';
        const method = isUpdating ? 'PUT' : 'POST';
        try {
            const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(userData) });
            const result = await response.json();
            if (result.success) {
                closeUserModal();
                fetchUsers();
                showToast(isUpdating ? 'User updated successfully!' : 'User created successfully!', 'success');
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('An unexpected network error occurred.', 'error');
        }
    };

    function setupEventListeners() {
        openAddModalBtn.addEventListener('click', () => {
            userForm.reset();
            modalTitle.textContent = 'Add New User';
            document.getElementById('userId').value = '';
            document.getElementById('email').disabled = false;
            officeSelectContainer.classList.add('hidden'); // Hide by default
            openUserModal();
        });

        closeModalBtns.forEach(btn => btn.addEventListener('click', closeUserModal));
        userForm.addEventListener('submit', handleUserFormSubmit);
        searchInput.addEventListener('input', () => fetchUsers(searchInput.value.trim()));

        // The key logic for the dynamic form
        roleSelect.addEventListener('change', () => {
            if (roleSelect.value === 'office_head') {
                officeSelectContainer.classList.remove('hidden');
                officeSelect.required = true;
            } else {
                officeSelectContainer.classList.add('hidden');
                officeSelect.required = false;
            }
        });

        usersTableBody.addEventListener('click', async (event) => {
            const editBtn = event.target.closest('.edit-btn');
            const toggleBtn = event.target.closest('.toggle-status-btn');
            if (editBtn) {
                const userId = editBtn.dataset.id;
                // Fetch the full user list again to find the specific user by ID
                const usersResponse = await fetch('api/users.php');
                const usersResult = await usersResponse.json();
                const user = usersResult.data.find(u => u.id == userId);
                if (!user) { showToast('Could not find user to edit.', 'error'); return; }

                modalTitle.textContent = 'Edit User';
                userForm.elements['id'].value = user.id;
                userForm.elements['first_name'].value = user.first_name;
                userForm.elements['last_name'].value = user.last_name;
                userForm.elements['email'].value = user.email;
                userForm.elements['role'].value = user.role;
                document.getElementById('email').disabled = true; // Prevent editing email

                // Trigger the role change handler to show/hide the office dropdown
                roleSelect.dispatchEvent(new Event('change'));
                if (user.role === 'office_head') {
                    userForm.elements['office_id'].value = user.office_id;
                }
                openUserModal();
            }
            if (toggleBtn) {
                const userId = toggleBtn.dataset.id;
                const actionText = toggleBtn.title.split(' ')[0]; // "Deactivate" or "Reactivate"
                try {
                    await showConfirmation({
                        title: `${actionText} User`,
                        message: `Are you sure you want to ${actionText.toLowerCase()} this account?`,
                        actionText: actionText
                    });
                    const response = await fetch(`api/users.php?id=${userId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'toggle_status' })
                    });
                    const result = await response.json();
                    if (result.success) { fetchUsers(); showToast(`User ${actionText.toLowerCase()}d successfully!`, 'success'); } 
                    else { showToast(`Error: ${result.message}`, 'error'); }
                } catch (error) { console.log(error.message); }
            }
        });
    }

    // --- Initial Load ---
    initializePage();

    // Re-pasting helper functions to ensure they exist
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');
        clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.classList.remove('bg-red-500', 'bg-green-500');
        if (type === 'success') { toast.classList.add('bg-green-500'); toastIcon.className = 'fas fa-check-circle mr-3 text-xl'; } 
        else { toast.classList.add('bg-red-500'); toastIcon.className = 'fas fa-exclamation-circle mr-3 text-xl'; }
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.remove('opacity-0'), 10);
        toastTimer = setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.classList.add('hidden'), 300); }, 3000);
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
});