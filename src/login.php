<?php
    include "components/header.php"
?>
    <div class="centeredDiv">
        <div class="titleBox loginModal">
            <h2 class="titleText">
                Welcome to brainSKwiz
            </h2>
            <p class="subTitle">Sign in to play or manage brainSKwiz</p>

            <div id="error-message" class="hidden mb-4 p-3 bg-red-900/30 border border-red-500/50 text-red-300 rounded-lg text-sm text-center"></div>

            <form id="login-form" class="space-y-5">
                <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div>
                    <label for="email">Email Address</label>
                    <input type="email" id="email" required placeholder="student@school.com"
                        class="inputField">
                </div>

                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" required placeholder="••••••••"
                        class="inputField">
                </div>

                <button type="submit" id="submit-btn"
                        class="btn">
                    Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        // Intercept form submission to process credential payloads asynchronously
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Stop native page reload sequence events

            // Extract working variables references
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const csrfToken = document.getElementById('csrf_token').value;
            const errorDiv = document.getElementById('error-message');
            const submitBtn = document.getElementById('submit-btn');

            // Reset dynamic message feedback structures
            errorDiv.classList.add('hidden');
            
            // UX/Control State: Disable form button interactions to lock concurrent outbound requests
            submitBtn.disabled = true;
            submitBtn.innerText = "Signing in...";

            // Send structured validation payload to the authentication processing pipeline
            fetch('/api/account/login_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password, csrf_token: csrfToken })
            })
            .then(res => {
                if (!res.ok) throw new Error(); // Throw error exception on faulty network statuses (e.g., 500 internal server errors)
                return res.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Routing Logic: Redirect target viewport location matching identity configurations
                    window.location.href = data.role === 'admin' ? '/admin_dashboard.php' : '/index.php';
                } else {
                    // Execution failure scenario: Render validation alert messages to client
                    errorDiv.textContent = data.message || "Invalid credentials.";
                    errorDiv.classList.remove('hidden');
                    
                    // Restore functional interactions states
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Sign In";
                }
            })
            .catch(() => {
                // Absolute global server disconnect / network failure fallback block
                errorDiv.textContent = "An error occurred. Please try again.";
                errorDiv.classList.remove('hidden');
                
                // Unlock access control states
                submitBtn.disabled = false;
                submitBtn.innerText = "Sign In";
            });
        });
    </script>