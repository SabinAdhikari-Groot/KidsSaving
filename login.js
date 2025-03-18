// scripts.js
document.getElementById('loginForm').addEventListener('submit', function (e) {
    // Get form elements
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const terms = document.getElementById('terms').checked;
    const errorDiv = document.getElementById('error');

    // Clear previous error messages
    errorDiv.textContent = '';

    // Validate fields
    if (!username) {
        errorDiv.textContent = 'Username is required.';
        e.preventDefault(); // Prevent form submission
        return;
    }

    if (!password) {
        errorDiv.textContent = 'Password is required.';
        e.preventDefault(); // Prevent form submission
        return;
    }

    if (!terms) {
        errorDiv.textContent = 'You must agree to the terms and services.';
        e.preventDefault(); // Prevent form submission
        return;
    }
});
