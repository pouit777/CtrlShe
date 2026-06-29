<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Génération d'un token CSRF sécurisé s'il n'existe pas déjà
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
// Fonction essentielle pour bloquer les failles XSS (injection de scripts dans le nom des quiz)
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

async function loadHistory() {
    try {
        // On passe le token CSRF dans les headers de la requête fetch
        const response = await fetch("/api/games/get_history.php", {
            method: "GET",
            headers: {
                "X-CSRF-Token": "<?= $_SESSION['csrf_token']; ?>"
            }
        });
        
        const data = await response.json();
        const container = document.getElementById("history-container");

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

        if (data.data.length === 0) {
            container.innerHTML = `
                <div class="quizCard">
                    <p class="subTitle">No games played yet.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = data.data.map(game => {
            const date = new Date(game.played_at + " UTC").toLocaleString("fr-FR", {
                timeZone: "Europe/Paris"
            });

            const percent = game.total_questions
                ? Math.round((game.score / game.total_questions) * 100)
                : 0;

            let color = "text-green-400";
            if (percent < 50) {
                color = "text-red-400";
            } else if (percent < 80) {
                color = "text-amber-400";
            }

            // Utilisation de escapeHTML() pour sécuriser l'affichage
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
        document.getElementById("history-container").innerHTML = `
            <div class="quizCard">
                <p class="subTitle text-red-400">Server error.</p>
            </div>
        `;
        console.error(error);
    }
}

loadHistory();
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>