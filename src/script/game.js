let index = 0;
let score = 0;
let timer = null;
let timeLeft = 15;

const quiz = GAME_DATA.quiz;
const questions = GAME_DATA.questions;

const answersMap = {};

const elQuestion = document.getElementById("questionText");
const elAnswers = document.getElementById("answers");
const elNext = document.getElementById("nextBtn");
const elScore = document.getElementById("score");
const elTimer = document.getElementById("timer");
const elProgress = document.getElementById("progressBar");

function start() {
    render();
    startTimer();
}

function render() {
    elAnswers.innerHTML = "";
    elNext.classList.add("hidden");

    const q = questions[index];

    elQuestion.textContent = q.question_text;

    elProgress.style.width = `${(index / questions.length) * 100}%`;

    q.answers.forEach(a => {
        const btn = document.createElement("button");

        btn.className = "p-3 bg-gray-700 rounded-lg text-white hover:bg-gray-600 transition";
        btn.textContent = a.answer_text;

        btn.onclick = () => select(a, btn);

        elAnswers.appendChild(btn);
    });
}

function select(answer, btn) {
    if (answersMap[questions[index].id]) return;

    clearInterval(timer);

    const qid = questions[index].id;
    answersMap[qid] = answer.id;

    const correct = questions[index].answers.find(a => a.is_correct);

    const buttons = elAnswers.querySelectorAll("button");
    buttons.forEach(b => b.disabled = true);

    if (answer.is_correct) {
        btn.classList.add("bg-green-600");
        score++;
        elScore.textContent = score;
    } else {
        btn.classList.add("bg-red-600");
        buttons.forEach(b => {
            if (b.textContent === correct.answer_text) {
                b.classList.add("bg-green-600");
            }
        });
    }

    elNext.classList.remove("hidden");
}

document.getElementById("nextBtn").onclick = () => {
    index++;

    if (index >= questions.length) {
        finish();
        return;
    }

    render();
    startTimer();
};

function startTimer() {
    timeLeft = 15;
    elTimer.textContent = timeLeft;

    timer = setInterval(() => {
        timeLeft--;
        elTimer.textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(timer);
            index++;
            index >= questions.length ? finish() : (render(), startTimer());
        }
    }, 1000);
}

async function finish() {
    clearInterval(timer);

    const res = await fetch("/api/games/finish.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            quiz_id: quiz.id,
            answers: answersMap
        })
    });

    const data = await res.json();

    if (data.status === "success") {

        if (data.guest) {

            localStorage.setItem("guest_result", JSON.stringify({
                score: data.score,
                total: data.total
            }));

            window.location.href = "/result.php?guest=1";
            return;
        }

        window.location.href = "/result.php?game=" + data.game_id;
    }
}

start();