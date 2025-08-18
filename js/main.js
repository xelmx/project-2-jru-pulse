
document.addEventListener('DOMContentLoaded', () => {

    // --- SIDEBAR LOGIC ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');
        });
    }

    // --- USER MENU LOGIC ---
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', (event) => {
            event.stopPropagation(); 
            userMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', () => {
            if (!userMenu.classList.contains('hidden')) {
                userMenu.classList.add('hidden');
            }
        });
    }

    // --- LOGOUT CONFIRMATION MODAL LOGIC ---
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutConfirmationModal = document.getElementById('logoutConfirmationModal');
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');

    if (logoutBtn && logoutConfirmationModal) {
        logoutBtn.addEventListener('click', (event) => {
            event.preventDefault(); 
            logoutConfirmationModal.classList.remove('hidden');
        });

        cancelLogoutBtn.addEventListener('click', () => {
            logoutConfirmationModal.classList.add('hidden');
        });

        confirmLogoutBtn.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });
    }
});