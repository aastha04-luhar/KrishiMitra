// Farmer Registration - Client Side Validation
document.getElementById('farmerRegisterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('name');
    const location = document.getElementById('location');
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Validate Name
    if (name.value.trim().length < 2) {
        alert('Name must be at least 2 characters!');
        name.focus();
        return;
    }
    
    // Validate Name (only letters and spaces)
    const nameRegex = /^[a-zA-Z\s]+$/;
    if (!nameRegex.test(name.value.trim())) {
        alert('Name should only contain letters and spaces!');
        name.focus();
        return;
    }
    
    // Validate Location
    if (location.value.trim().length < 2) {
        alert('Please enter a valid location!');
        location.focus();
        return;
    }
    
    // Validate Phone
    const phoneRegex = /^[0-9]{10}$/;
    if (!phoneRegex.test(phone.value)) {
        alert('Please enter a valid 10-digit phone number!');
        phone.focus();
        return;
    }
    
    // Validate Password
    if (password.value.length < 6) {
        alert('Password must be at least 6 characters!');
        password.focus();
        return;
    }
    
    // Validate Confirm Password
    if (password.value !== confirmPassword.value) {
        alert('Passwords do not match!');
        confirmPassword.focus();
        return;
    }
    
    this.submit();
});

// Real-time phone number validation
document.getElementById('phone')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
    
    const phoneRegex = /^[0-9]{10}$/;
    if (phoneRegex.test(this.value)) {
        this.classList.remove('invalid');
        this.classList.add('valid');
    } else {
        this.classList.remove('valid');
        if (this.value.length > 0) {
            this.classList.add('invalid');
        }
    }
});

// Real-time validation feedback
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value.trim() !== '') {
            this.classList.add('valid');
        }
    });
});