
// Confirm action before executing
function confirmAction(action, name) {
    const actions = {
        approve: 'approve',
        block: 'block',
        delete: 'delete'
    };
    
    const messages = {
        approve: `Are you sure you want to approve "${name}"?`,
        block: `Are you sure you want to block "${name}"? They will lose access to the platform.`,
        delete: `Are you sure you want to delete "${name}"? This action cannot be undone.`
    };
    
    if (confirm(messages[action] || `Are you sure you want to ${action} "${name}"?`)) {
        return true;
    }
    return false;
}

// Search with Enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }
});

// Auto-submit on status filter change
document.querySelector('select[name="status"]')?.addEventListener('change', function() {
    this.closest('form').submit();
});

// Highlight search results
document.addEventListener('DOMContentLoaded', function() {
    const searchQuery = new URLSearchParams(window.location.search).get('search');
    if (searchQuery) {
        const tableRows = document.querySelectorAll('.farmers-table tbody tr');
        tableRows.forEach(row => {
            const text = row.textContent;
            if (text.toLowerCase().includes(searchQuery.toLowerCase())) {
                row.style.backgroundColor = '#fffbf0';
            }
        });
    }
});

// Export functionality (if needed)
function exportFarmers() {
    if (confirm('Export all farmers data to CSV?')) {
        window.location.href = 'export_farmers.php';
    }
}

// Toggle all checkboxes (for bulk actions)
function toggleAllCheckboxes(checkbox) {
    const checkboxes = document.querySelectorAll('input[name="farmer_ids[]"]');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}