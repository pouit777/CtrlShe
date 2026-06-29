<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pre-Authentication Boundary Guard: Redirect already authenticated sessions away from login routes
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// Anti-CSRF Token generation for state verification procedures
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "brainSKwiz - Login";
include "components/header.php";
?>

<div class="page-login">
    <div class="centeredDiv">
        <div class="titleBox loginModal">

            <h2 class="titleText">Welcome to brainSKwiz</h2>
            <p class="subTitle">Sign in to play or manage brainSKwiz</p>

            <div id="error-message" class="hidden mb-4 p-3 bg-red-900/30 border border-red-500/50 text-red-300 rounded-lg text-sm text-center"></div>

            <form id="login-form" class="space-y-5">
                <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div>
                    <label for="email" class="inputLogin">Email Address</label>
                    <input type="email" id="email" required placeholder="student@school.com" class="inputField">
                </div>

                <div>
                    <label for="password" class="inputLogin">Password</label>
                    <div class="relative w-full">
                        <input type="password" id="password" required placeholder="••••••••" class="inputField pr-10 w-full">
                        <button type="button" id="toggle-password-btn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-secondary transition focus:outline-none">
                            <span class="material-icons" id="toggle-password-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-400">
                Don't have an account yet ?
                <a href="/register.php" class="text-gray-400 hover:text-secondary transition font-semibold underline decoration-2 underline-offset-2">
                    Sign up here
                </a>
            </div>

        </div>
    </div>
</div>

<script>
const passwordInput = document.getElementById('password');
const togglePasswordBtn = document.getElementById('toggle-password-btn');
const togglePasswordIcon = document.getElementById('toggle-password-icon');

// Event listener managing interactive hidden password visibility mutations
togglePasswordBtn.addEventListener('click', function () {
    // Permute input stream types dynamically between standard password masks and readable text fields
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    
    // Toggle corresponding visual font icons matching visibility state requirements
    togglePasswordIcon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
});

// Capture authentication lifecycle form submissions asynchronously
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const csrfToken = document.getElementById('csrf_token').value;
    const errorDiv = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');

    // Reset visual error state notification bounds
    errorDiv.classList.add('hidden');

    // Freeze structural interface control bounds to intercept submission duplicate requests
    submitBtn.disabled = true;
    submitBtn.innerText = "Signing in...";

    // Dispatch credentials securely encoded within an asynchronous JSON stream
    fetch('/api/account/login_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, csrf_token: csrfToken })
    })
    .then(res => res.json())
    .then(data => {
        // Evaluate API verification response states
        if (data.status === 'success') {
            // Enforce conditional route mapping targeting authorization privileges
            window.location.href = data.role === 'admin' ? '/admin_dashboard.php' : '/index.php';
        } else {
            // Map validation errors back inside the allocated error container alert boxes
            errorDiv.textContent = data.message || "Invalid credentials.";
            errorDiv.classList.remove('hidden');

            // Restore form control interactive capabilities
            submitBtn.disabled = false;
            submitBtn.innerText = "Sign In";
        }
    })
    .catch(() => {
        // Fallback catch boundary shielding systems from network connectivity failures
        errorDiv.textContent = "An error occurred. Please try again.";
        errorDiv.classList.remove('hidden');

        submitBtn.disabled = false;
        submitBtn.innerText = "Sign In";
    });
});
</script>

<?php include "components/footer.php"; ?>