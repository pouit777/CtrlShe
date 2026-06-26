<?php
// Initialize session context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security safeguard: Redirect authenticated users away from subscription routes
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// Generate an initial fallback CSRF protection token if missing from state context
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "brainSKwiz - Register";
include "components/header.php";
?>
    <div class="centeredDiv">
        <div class="titleBox loginModal">
            <h2 class="titleText">
                Create an Account
            </h2>
            <p class="subTitle">Join brainSKwiz to track your scores and compete !</p>

            <div id="error-message" class="hidden mb-4 p-3 bg-red-900/30 border border-red-500/50 text-red-300 rounded-lg text-sm text-center"></div>
            <div id="success-message" class="hidden mb-4 p-3 bg-emerald-900/30 border border-emerald-500/50 text-emerald-300 rounded-lg text-sm text-center"></div>

            <form id="register-form" class="space-y-5">
                <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div>
                    <label for="username">Username</label>
                    <input type="text" id="username" required placeholder="john_doe"
                        class="inputField">
                </div>

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

                <button type="submit" id="submit-btn" class="btn">
                    Sign Up
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-400">
                Do you have  already an account ? 
                <a href="/login.php" class="text-gray-400 hover:text-secondary transition font-semibold underline decoration-2 underline-offset-2">
                    Login here
                </a>
            </div>
        </div>
    </div>

    <script>
        // Bind dynamic interface interceptor onto the registration submission stream
        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Lock default event delegation sequences

            // Gather elements and contextual payloads
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const csrfToken = document.getElementById('csrf_token').value;
            const errorDiv = document.getElementById('error-message');
            const successDiv = document.getElementById('success-message');
            const submitBtn = document.getElementById('submit-btn');

            // Structural reset of informational modal nodes
            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');
            
            // Interface flow locking constraint
            submitBtn.disabled = true;
            submitBtn.innerText = "Creating account...";

            // Send registration parameter data to processing stream
            fetch('/api/account/register_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, email, password, csrf_token: csrfToken })
            })
            .then(res => {
                if (!res.ok) throw new Error();
                return res.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Update layout mapping messaging parameters to state context
                    successDiv.textContent = "Account successfully created! Welcoming you to brainSKwiz...";
                    successDiv.classList.remove('hidden');
                    
                    // Route directly to home mapping automatic background session login state
                    setTimeout(() => {
                        window.location.href = '/index.php';
                    }, 2000);
                } else {
                    // Process backend failure notification messages cleanly
                    errorDiv.textContent = data.message || "Registration failed.";
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Sign Up";
                }
            })
            .catch(() => {
                // Network pipeline structural disconnect interceptor boundary
                errorDiv.textContent = "An error occurred. Please try again.";
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerText = "Sign Up";
            });
        });
    </script>
<?php include "components/footer.php"; ?>