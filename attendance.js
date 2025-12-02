function openFilterModal() {
    document.getElementById('filterModal').style.display = 'flex';
}

function closeFilterModal() {
    document.getElementById('filterModal').style.display = 'none';
}

function applyFilters() {
    document.getElementById('filterForm').submit();
}

function clearFilters() {
    const filterForm = document.getElementById('filterForm');
    filterForm.reset(); // Reset all form inputs
    filterForm.submit(); // Submit with cleared values
}
document.getElementById('filterForm').addEventListener('submit', function (event) {
    // Prevent default submit for debug, if needed
    console.log("Form submitted!");
});
// Get the close button
document.addEventListener('DOMContentLoaded', function () {
    const closeBtn = document.querySelector('.modal-content .close');
    const modal = document.getElementById('filterModal');

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }
});


