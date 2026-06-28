<?php
// Initialize session context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect authenticated users away
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "brainSKwiz - Register";
include "components/header.php";
?>

<div class="page-login">
    <div class="centeredDiv">
        <div class="titleBox loginModal">

            <h2 class="titleText">Create an Account</h2>
            <p class="subTitle">Join brainSKwiz to track your scores and compete!</p>

            <!-- Messages -->
            <div id="error-message" class="hidden mb-4 p-3 bg-red-900/30 border border-red-500/50 text-red-300 rounded-lg text-sm text-center"></div>
            <div id="success-message" class="hidden mb-4 p-3 bg-emerald-900/30 border border-emerald-500/50 text-emerald-300 rounded-lg text-sm text-center"></div>

            <form id="register-form" class="space-y-5">
                <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div>
                    <label for="username">Username (3 to 25 chars)</label>
                    <input type="text" id="username" required minlength="3" maxlength="25" placeholder="john_doe" class="inputField">
                </div>

                <div>
                    <label for="email">Email Address</label>
                    <input type="email" id="email" required maxlength="255" placeholder="student@school.com" class="inputField">
                </div>

                <div>
                    <label for="password">Password (Min 8 chars, 1 number, 1 special)</label>
                    <div class="relative w-full">
                        <input type="password" id="password" required placeholder="••••••••" class="inputField pr-10 w-full">
                        <button type="button" id="toggle-password-btn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-secondary transition focus:outline-none">
                            <span class="material-icons" id="toggle-password-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn">
                    Sign Up
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-400">
                Already have an account ?
                <a href="/login.php"
                   class="text-gray-400 hover:text-secondary transition font-semibold underline decoration-2 underline-offset-2">
                    Login here
                </a>
            </div>

        </div>
    </div>
</div>

<script>
// Gestion de l'affichage / masquage du mot de passe
const passwordInput = document.getElementById('password');
const togglePasswordBtn = document.getElementById('toggle-password-btn');
const togglePasswordIcon = document.getElementById('toggle-password-icon');

togglePasswordBtn.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePasswordIcon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
});

document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const csrfToken = document.getElementById('csrf_token').value;

    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    const submitBtn = document.getElementById('submit-btn');

    if (username.length < 3 || username.length > 25) {
        errorDiv.textContent = "Username must be between 3 and 25 characters.";
        errorDiv.classList.remove('hidden');
        return;
    }

    if (email.length > 255) {
        errorDiv.textContent = "Email is too long.";
        errorDiv.classList.remove('hidden');
        return;
    }

    // Regex : Au moins 8 caractères, 1 chiffre, 1 caractère spécial
    const passwordRegex = /^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>_\-+=]).{8,}$/;
    if (!passwordRegex.test(password)) {
        errorDiv.textContent = "Password must be at least 8 characters long and contain at least one number and one special character.";
        errorDiv.classList.remove('hidden');
        return;
    }

    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');

    submitBtn.disabled = true;
    submitBtn.innerText = "Creating account...";

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
            successDiv.textContent = "Account created successfully!";
            successDiv.classList.remove('hidden');

            setTimeout(() => {
                window.location.href = '/index.php';
            }, 1500);
        } else {
            errorDiv.textContent = data.message || "Registration failed.";
            errorDiv.classList.remove('hidden');

            submitBtn.disabled = false;
            submitBtn.innerText = "Sign Up";
        }
    })
    .catch(() => {
        errorDiv.textContent = "An error occurred. Please try again.";
        errorDiv.classList.remove('hidden');

        submitBtn.disabled = false;
        submitBtn.innerText = "Sign Up";
    });
});
</script>

<?php include "components/footer.php"; ?>