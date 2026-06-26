<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$page_title = "History";
require_once __DIR__ . '/components/header.php';
?>

<div class="max-w-5xl mx-auto mt-10 p-6 bg-gray-900 rounded-xl border border-gray-700">

    <h1 class="text-3xl font-bold text-cyan-400 mb-6">
        Game History
    </h1>

    <div id="history-container" class="space-y-4">

        <!-- History loaded with JavaScript -->

    </div>

</div>

<script>

async function loadHistory() {

    try {

        const response = await fetch("/api/games/get_history.php");
        const data = await response.json();

        const container = document.getElementById("history-container");

        if (data.status !== "success") {
            container.innerHTML = `
                <p class="text-red-400">
                    Error loading history.
                </p>
            `;
            return;
        }

        if (data.data.length === 0) {
            container.innerHTML = `
                <p class="text-gray-400">
                    No games played yet.
                </p>
            `;
            return;
        }

        container.innerHTML = data.data.map(game => {

            const date = new Date(game.played_at).toLocaleString();

            const percent = game.total_questions
                ? Math.round((game.score / game.total_questions) * 100)
                : 0;

            let color = "text-green-400";

            if (percent < 50) {
                color = "text-red-400";
            } else if (percent < 80) {
                color = "text-yellow-400";
            }

            return `
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 flex justify-between items-center">

                    <div>

                        <h2 class="text-xl font-bold text-white">
                            ${game.quiz_name ?? "Unknown Quiz"}
                        </h2>

                        <p class="text-sm text-gray-400 mt-1">
                            Played on ${date}
                        </p>

                        <p class="text-sm text-gray-500">
                            Difficulty : ${game.difficulty ?? "Unknown"}
                        </p>

                    </div>

                    <div class="text-right">

                        <p class="text-2xl font-bold ${color}">
                            ${game.score} / ${game.total_questions}
                        </p>

                        <p class="text-gray-400">
                            ${percent}%
                        </p>

                        <a
                            href="/result.php?game=${game.id}"
                            class="inline-block mt-3 px-4 py-2 bg-cyan-500 hover:bg-cyan-600 rounded-lg text-black font-semibold transition"
                        >
                            View Result
                        </a>

                    </div>

                </div>
            `;

        }).join("");

    } catch (error) {

        document.getElementById("history-container").innerHTML = `
            <p class="text-red-400">
                Server error.
            </p>
        `;

        console.error(error);

    }

}

loadHistory();

</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>