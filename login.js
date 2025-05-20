// scripts.js
document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

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
        return;
    }

    if (!password) {
        errorDiv.textContent = 'Password is required.';
        return;
    }

    if (!terms) {
        errorDiv.textContent = 'You must agree to the terms and services.';
        return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('terms', terms ? '1' : '0');

    // Send AJAX request
    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to the appropriate dashboard
            window.location.href = data.redirect;
        } else {
            // Display error message
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.style.display = 'block';
    });
});
