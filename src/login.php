<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>brainSKwiz - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-gray-800 p-8 rounded-2xl border border-gray-700 shadow-2xl">
        <h2 class="text-3xl font-extrabold text-center mb-2 text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">
            Welcome to brainSKwiz
        </h2>
        <p class="text-gray-400 text-center text-sm mb-8">Sign in to play or manage brainSKwiz</p>

        <div id="error-message" class="hidden mb-4 p-3 bg-red-900/30 border border-red-500/50 text-red-300 rounded-lg text-sm text-center"></div>

        <form id="login-form" class="space-y-5">
            <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email Address</label>
                <input type="email" id="email" required placeholder="student@school.com"
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 transition">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                <input type="password" id="password" required placeholder="••••••••"
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 transition">
            </div>

            <button type="submit" id="submit-btn"
                    class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-gray-900 font-bold py-2.5 rounded-lg transition duration-200 shadow-lg transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                Sign In
            </button>
        </form>
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
            fetch('/api/login_process.php', {
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
</body>
</html>