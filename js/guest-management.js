document.addEventListener('DOMContentLoaded', function () {
    //  DOM Element References  
    const guestModal = document.getElementById('guestModal');
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    const closeModalBtns = document.querySelectorAll('.closeModalBtn');
    const guestForm = document.getElementById('guestForm');
    const modalTitle = document.getElementById('modalTitle');
    const guestsTableBody = document.getElementById('guestsTableBody');
    const searchInput = document.getElementById('searchInput');

    // References for the modals
    const confirmModal = document.getElementById('confirmationModal');
    const confirmTitle = document.getElementById('confirmationTitle');
    const confirmMessage = document.getElementById('confirmationMessage');
    const confirmActionBtn = document.getElementById('confirmActionBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    //   API Calls & Data Handling 
    const fetchGuests = async (searchTerm = '') => {
        try {
            const response = await fetch(`api/guests.php?search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();
            if (result.success) {
                renderTable(result.data);
            } else {
                guestsTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4">${result.message}</td></tr>`;
            }
        } catch (error) {
            guestsTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-4">Error loading data.</td></tr>`;
        }
    };

    const renderTable = (guests) => {
        guestsTableBody.innerHTML = '';
        if (guests.length === 0) {
            guestsTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-10">No guests found.</td></tr>`;
            return;
        }
        guests.forEach(guest => {
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">${guest.first_name} ${guest.last_name}</td>
                    <td class="px-6 py-4">${guest.email}</td>
                    <td class="px-6 py-4">${guest.role}</td>
                    <td class="px-6 py-4 text-right">
                        <button class="text-jru-blue hover:text-blue-800 font-semibold edit-btn" data-id="${guest.id}">Edit</button>
                        <button class="text-red-600 hover:text-red-800 font-semibold ml-4 delete-btn" data-id="${guest.id}">Delete</button>
                    </td>
                </tr>`;
            guestsTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // MODAL & NOTIFICATION HELPERS
    const openGuestModal = () => guestModal.classList.remove('hidden');
    const closeGuestModal = () => guestModal.classList.add('hidden');

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

    //  FORM & EVENT HANDLERS
    const handleGuestFormSubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(guestForm);
        const guestData = Object.fromEntries(formData.entries());
        
        // This line checks if we are updating. If the hidden 'id' field has a value, isUpdating is true.
        const isUpdating = guestData.id; 
        
        const url = isUpdating ? `api/guests.php?id=${guestData.id}` : 'api/guests.php';
        const method = isUpdating ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(guestData) });
            const result = await response.json();
            
            if (result.success) {
                closeGuestModal();
                fetchGuests();
                
                // ternary operator to show a different message based on whether you are updating or adding.
                showToast(isUpdating ? 'Guest updated successfully!' : 'Guest added successfully!', 'success');
                
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('An unexpected network error occurred.', 'error');
        }
    };

    // --- Event Listeners Setup ---
    openAddModalBtn.addEventListener('click', () => {
        guestForm.reset();
        document.getElementById('guestId').value = '';
        modalTitle.textContent = 'Add New Guest';
        openGuestModal();
    });

    closeModalBtns.forEach(btn => btn.addEventListener('click', closeGuestModal));
    guestForm.addEventListener('submit', handleGuestFormSubmit);
    searchInput.addEventListener('input', () => fetchGuests(searchInput.value.trim()));

    guestsTableBody.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('.edit-btn');
        const deleteBtn = event.target.closest('.delete-btn');

        if (editBtn) {
            const guestId = editBtn.dataset.id;
            const row = editBtn.closest('tr');
            modalTitle.textContent = 'Edit Guest';
            guestForm.elements['id'].value = guestId;
            guestForm.elements['first_name'].value = row.cells[0].textContent.split(' ')[0];
            guestForm.elements['last_name'].value = row.cells[0].textContent.split(' ').slice(1).join(' ');
            guestForm.elements['email'].value = row.cells[1].textContent;
            guestForm.elements['role'].value = row.cells[2].textContent;
            openGuestModal();
        }

        if (deleteBtn) {
            // MODIFIED: Replaced browser confirm with the custom confirmation modal
            const guestId = deleteBtn.dataset.id;
            try {
                await showConfirmation({
                    title: 'Confirm Deletion',
                    message: 'Are you sure you want to permanently delete this guest? This action cannot be undone.',
                    actionText: 'Delete'
                });

                // User clicked "Delete", proceed with API call
                const response = await fetch(`api/guests.php?id=${guestId}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.success) {
                    fetchGuests();
                    showToast('Guest deleted successfully!', 'success');
                } else {
                    showToast(`Error: ${result.message}`, 'error');
                }
            } catch (error) {
                // This block runs if the user clicks "Cancel"
                console.log(error.message); 
            }
        }
    });

    // --- Initial Load ---
    fetchGuests();
});