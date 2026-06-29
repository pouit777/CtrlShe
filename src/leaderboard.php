<?php
session_start();

// Authentication Perimeter Guard: Restrict route access strictly to logged-in user environments
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$page_title = "Leaderboard";
require_once __DIR__ . '/components/header.php';
?>

<div id="leaderboard" class="leaderboard"></div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadLeaderboard();
    });

    async function loadLeaderboard(){
        const res = await fetch("/api/leaderboard/get_leaderboard.php", {
            credentials: "include"
        });
        const json = await res.json();

        if(json.status !== "success") return;
        const leaderboard = document.getElementById("leaderboard");

        if(!leaderboard){
            console.error("Leaderboard container not found");
            return;
        }

        const currentUser = json.currentUser;
        const players = json.data;
        leaderboard.innerHTML = "";
        players.forEach((player, index) => {
            const isMe = player.id == currentUser;
            console.log(currentUser)

            let icon = index + 1;
            let className = "";

            if(index === 0){
                icon = `<span class="material-icons gold">emoji_events</span>`;
                className = "first";
            }

            if(index === 1){
                icon = `<span class="material-icons silver">emoji_events</span>`;
                className = "second";
            }

            if(index === 2){
                icon = `<span class="material-icons bronze">emoji_events</span>`;
                className = "third";
            }

            leaderboard.innerHTML += `
                <div class="leaderboard-item ${className} ${isMe ? 'me' : ''}">

                    <div class="rank">
                        ${icon}
                    </div>

                    <div class="player">
                        <img src="/public/avatars/${player.avatar}">
                        <span>
                            ${player.username}
                        </span>
                    </div>

                    <div class="score">
                        ${(player.total_points ?? 0).toLocaleString()} pts
                    </div>

                </div>
            `;
        });
    }
</script>
