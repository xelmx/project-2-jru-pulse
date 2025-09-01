document.addEventListener('DOMContentLoaded', function () {
    // --- === DOM Element References === ---
    const userModal = document.getElementById('userModal');
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    const closeModalBtns = document.querySelectorAll('.closeModalBtn');
    const userForm = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const usersTableBody = document.getElementById('usersTableBody');
    const searchInput = document.getElementById('searchInput');

    // References for the modals
    const confirmModal = document.getElementById('confirmationModal');
    const confirmTitle = document.getElementById('confirmationTitle');
    const confirmMessage = document.getElementById('confirmationMessage');
    const confirmActionBtn = document.getElementById('confirmActionBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    // --- === API Calls & Data Handling === ---
    const fetchUsers = async (searchTerm = '') => {
        try {
            const response = await fetch(`api/users.php?search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();
            if (result.success) {
                renderTable(result.data);
            } else {
                usersTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4">${result.message}</td></tr>`;
            }
        } catch (error) {
            usersTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-red-500 py-4">Error loading data.</td></tr>`;
        }
    };

    const renderTable = (users) => {
        usersTableBody.innerHTML = '';
        if (users.length === 0) {
            usersTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10">No users found.</td></tr>`;
            return;
        }
        users.forEach(user => {
            const statusClass = user.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const statusText = user.is_active == 1 ? 'Active' : 'Inactive';
            const toggleActionText = user.is_active == 1 ? 'Deactivate' : 'Reactivate';
            const toggleIcon = user.is_active == 1 ? 'fa-toggle-off' : 'fa-toggle-on';

            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">${user.first_name} ${user.last_name}</td>
                    <td class="px-6 py-4">${user.email}</td>
                    <td class="px-6 py-4">${user.role === 'admin' ? 'Admin' : 'Office Head'}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 rounded-full ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button class="text-gray-500 hover:text-orange-600 toggle-status-btn" data-id="${user.id}" title="${toggleActionText} User">
                            <i class="fas ${toggleIcon}"></i>
                        </button>
                        <button class="text-jru-blue hover:text-blue-800 font-semibold ml-4 edit-btn" data-id="${user.id}">Edit</button>
                    </td>
                </tr>`;
            usersTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // Modal & Notification Helpers
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

    // FORM & EVENT HANDLERS
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

    // --- Event Listeners Setup ---
    openAddModalBtn.addEventListener('click', () => {
        userForm.reset();
        document.getElementById('userId').value = '';
        document.getElementById('email').disabled = false; // Make sure email is editable for new users
        modalTitle.textContent = 'Add New User';
        openUserModal();
    });

    closeModalBtns.forEach(btn => btn.addEventListener('click', closeUserModal));
    userForm.addEventListener('submit', handleUserFormSubmit);
    searchInput.addEventListener('input', () => fetchUsers(searchInput.value.trim()));

    usersTableBody.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('.edit-btn');
        const toggleBtn = event.target.closest('.toggle-status-btn');

        if (editBtn) {
            const userId = editBtn.dataset.id;
            const row = editBtn.closest('tr');
            modalTitle.textContent = 'Edit User';
            
            userForm.elements['id'].value = userId;
            userForm.elements['first_name'].value = row.cells[0].textContent.split(' ')[0];
            userForm.elements['last_name'].value = row.cells[0].textContent.split(' ').slice(1).join(' ');
            userForm.elements['email'].value = row.cells[1].textContent;
            userForm.elements['role'].value = row.cells[2].textContent.toLowerCase().replace(' ', '_'); // ex. Office Head -> office_head
            
            document.getElementById('email').disabled = true; // Prevent editing email
            openUserModal();
        }

        if (toggleBtn) {
            const userId = toggleBtn.dataset.id;
            const row = toggleBtn.closest('tr');
            const currentStatus = row.cells[3].textContent.trim();
            const actionText = currentStatus === 'Active' ? 'Deactivate' : 'Reactivate';

            try {
                await showConfirmation({
                    title: `${actionText} User`,
                    message: `Are you sure you want to ${actionText.toLowerCase()} this user's account?`,
                    actionText: actionText
                });

                const response = await fetch(`api/users.php?id=${userId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_status' })
                });
                const result = await response.json();

                if (result.success) {
                    fetchUsers();
                    showToast(`User ${actionText.toLowerCase()}d successfully!`, 'success');
                } else {
                    showToast(`Error: ${result.message}`, 'error');
                }
            } catch (error) {
                console.log(error.message); // Logs "Action cancelled by user"
            }
        }
    });

    // --- Initial Load ---
    fetchUsers();

    // Helper functions to ensure they exist in the file
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
});