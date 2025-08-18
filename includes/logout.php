<div id="logoutConfirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-start">
                    <!-- Icon -->
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4 text-left">
                        <!-- Title -->
                        <h3 class="text-lg leading-6 font-bold text-gray-900">
                            Confirm Logout
                        </h3>
                        <!-- Message Body -->
                        <div class="mt-2">
                            <p class="text-sm text-gray-600">
                                Are you sure you want to log out of your session?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-4 rounded-b-xl">
                <button id="cancelLogoutBtn" type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button id="confirmLogoutBtn" type="button" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700">
                    Logout
                </button>
            </div>
        </div>
    </div>