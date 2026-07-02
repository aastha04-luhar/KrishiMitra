// Admin Registration - Client Side Validation
document.getElementById('adminRegisterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const passkey = document.getElementById('passkey');
    
    // Validate Name
    if (name.value.trim().length < 2) {
        alert('Name must be at least 2 characters!');
        name.focus();
        return;
    }
    
    // Validate Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
        alert('Please enter a valid email address!');
        email.focus();
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
    
    // 🔐 Validate Passkey
    if (passkey.value.trim() === '') {
        alert('Please enter the Admin Passkey!');
        passkey.focus();
        return;
    }
    
    if (passkey.value.length < 4) {
        alert('Admin Passkey must be at least 4 characters!');
        passkey.focus();
        return;
    }
    
    this.submit();
});

// Real-time passkey feedback
document.getElementById('passkey')?.addEventListener('input', function() {
    const hint = document.getElementById('passkeyHint');
    if (hint) {
        if (this.value.length > 0 && this.value.length < 4) {
            hint.innerHTML = '<i class="fas fa-times-circle" style="color:#e74c3c;"></i> Too short';
            hint.style.color = '#e74c3c';
        } else if (this.value.length >= 4) {
            hint.innerHTML = '<i class="fas fa-check-circle" style="color:#27ae60;"></i> Valid';
            hint.style.color = '#27ae60';
            this.style.borderColor = '#27ae60';
        } else {
            hint.innerHTML = '<i class="fas fa-info-circle" style="color:#999;"></i> Enter passkey';
            hint.style.color = '#999';
        }
    }
});