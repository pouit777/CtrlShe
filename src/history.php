<?php
session_start();

// Authentication Perimeter Guard: Restrict route access strictly to logged-in user environments
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Security Checkpoint: Generate a cryptographically secure pseudo-random anti-CSRF token if none exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "History";
require_once __DIR__ . '/components/header.php';
?>

<div class="page-index">
    <div class="titleBoxAdmin">
        <h1 class="bigTitle">Game History</h1>
        <p class="subTitle">Review your past quiz performances</p>
    </div>

    <div id="history-container" class="quizzes"></div>
</div>

<script>
/**
 * Sanitizes raw user-generated string sequences to mitigate Stored XSS 
 * (Cross-Site Scripting) vulnerabilities by escaping active HTML syntax characters.
 */
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(match) {
        const chars = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return chars[match];
    });
}

/**
 * Asynchronously dispatches a secure request payload fetching aggregated game performance statistics.
 */
async function loadHistory() {
    try {
        // Anti-CSRF Token Enforcement: Pass the active token string directly inside custom HTTP header properties
        const response = await fetch("/api/games/get_history.php", {
            method: "GET",
            headers: {
                "X-CSRF-Token": "<?= $_SESSION['csrf_token']; ?>"
            }
        });
        
        const data = await response.json();
        const container = document.getElementById("history-container");

        // Handle operational API response failures gracefully within the UI layout
        if (data.status !== "success") {
            container.innerHTML = `
                <div class="quizCard">
                    <p class="subTitle text-red-400">
                        ${escapeHTML(data.message || "Error loading history.")}
                    </p>
                </div>
            `;
            return;
        }

        // Empty set handling condition: fallback display configuration when no records exist
        if (data.data.length === 0) {
            container.innerHTML = `
                <div class="quizCard">
                    <p class="subTitle">No games played yet.</p>
                </div>
            `;
            return;
        }

        // Map over response arrays and compile structural UI template segments dynamically
        container.innerHTML = data.data.map(game => {
            
            // Normalize database UTC time indicators to local European/Paris regional zone parameters
            const date = new Date(game.played_at + " UTC").toLocaleString("fr-FR", {
                timeZone: "Europe/Paris"
            });

            // Calculate percentage ratings to establish adaptive color score models
            const percent = game.total_questions
                ? Math.round((game.score / game.total_questions) * 100)
                : 0;

            // Determine appropriate style color tokens matching specific threshold boundaries
            let color = "text-green-400";
            if (percent < 50) {
                color = "text-red-400";
            } else if (percent < 80) {
                color = "text-amber-400";
            }

            // Append sanitized attributes safely within structural layout strings
            return `
                <div class="quizCard">
                    <h2 class="titleCard mb-2">
                        ${escapeHTML(game.quiz_name ?? "Unknown Quiz")}
                    </h2>

                    <p class="subTitle">Played on ${date}</p>

                    <p class="subTitle">
                        Difficulty: ${escapeHTML(game.difficulty ?? "Unknown")}
                    </p>

                    <div class="mt-3 text-center">
                        <p class="text-xl font-bold ${color}">
                            ${parseInt(game.score)} / ${parseInt(game.total_questions)}
                        </p>
                        <p class="subTitle">${percent}%</p>
                    </div>

                    <div class="m-auto flex justify-center text-center">
                        <a href="/result.php?game=${parseInt(game.id)}" class="btndark mt-4">
                            View Result
                        </a>
                    </div>
                </div>
            `;
        }).join("");

    } catch (error) {
        // Shield operational infrastructure maps by substituting detailed crash strings with generic errors
        document.getElementById("history-container").innerHTML = `
            <div class="quizCard">
                <p class="subTitle text-red-400">Server error.</p>
            </div>
        `;
        console.error(error);
    }
}

// Trigger history payload acquisition loop sequence automatically upon file compilation
loadHistory();
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>