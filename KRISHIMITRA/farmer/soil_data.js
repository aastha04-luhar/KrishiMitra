
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this soil data entry? This action cannot be undone.')) {
        window.location.href = '?delete=' + id;
    }
}

// Set today's date as default
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name="test_date"]');
    if (dateInput && !dateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
    }
});

// Real-time validation for form inputs
document.querySelectorAll('.soil-form input').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value && !isNaN(this.value) && this.value >= 0) {
            this.classList.remove('invalid');
            this.classList.add('valid');
        } else if (this.value) {
            this.classList.remove('valid');
            this.classList.add('invalid');
        } else {
            this.classList.remove('valid', 'invalid');
        }
    });
    
    input.addEventListener('blur', function() {
        if (this.value && this.value.trim() !== '') {
            if (this.min && parseFloat(this.value) < parseFloat(this.min)) {
                this.classList.add('invalid');
            } else if (this.max && parseFloat(this.value) > parseFloat(this.max)) {
                this.classList.add('invalid');
            } else {
                this.classList.remove('invalid');
                this.classList.add('valid');
            }
        }
    });
});

// Form validation before submit
document.getElementById('soilForm')?.addEventListener('submit', function(e) {
    let hasError = false;
    const inputs = this.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        if (!input.value || input.value.trim() === '') {
            input.classList.add('invalid');
            hasError = true;
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert('Please fill in all required fields with valid values.');
    }
});

// Keyboard shortcut: Escape to reset form
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const form = document.getElementById('soilForm');
        if (form && document.activeElement?.closest('.soil-form')) {
            if (confirm('Reset form? All entered data will be cleared.')) {
                form.reset();
                document.querySelectorAll('.soil-form input').forEach(input => {
                    input.classList.remove('valid', 'invalid');
                });
            }
        }
    }
});