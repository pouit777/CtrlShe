let currentIndex = 0;
let score = 0;
let selectedAnswer = null;
let timer = null;
let timeLeft = 15;

const questionText = document.getElementById("questionText");
const answersBox = document.getElementById("answers");
const nextBtn = document.getElementById("nextBtn");
const scoreEl = document.getElementById("score");
const progressText = document.getElementById("progressText");
const timerEl = document.getElementById("timer");
const progressBar = document.getElementById("progressBar");

const quiz = GAME_DATA.quiz;
const questions = GAME_DATA.questions;

function startGame() {
    renderQuestion();
    startTimer();
}

function renderQuestion() {
    resetState();

    const q = questions[currentIndex];

    questionText.textContent = q.question_text;

    progressText.textContent = `Question ${currentIndex + 1} / ${questions.length}`;

    progressBar.style.width = `${(currentIndex / questions.length) * 100}%`;

    q.answers.forEach(answer => {
        const btn = document.createElement("button");

        btn.className =
            "w-full text-left p-3 rounded-lg bg-gray-700 hover:bg-gray-600 text-white transition";

        btn.textContent = answer.answer_text;

        btn.onclick = () => selectAnswer(answer, btn);

        answersBox.appendChild(btn);
    });
}

function selectAnswer(answer, btn) {
    if (selectedAnswer) return;

    selectedAnswer = answer;

    clearInterval(timer);

    const buttons = answersBox.querySelectorAll("button");

    buttons.forEach(b => (b.disabled = true));

    if (answer.is_correct) {
        btn.classList.add("bg-green-600");
        score++;
        scoreEl.textContent = score;
    } else {
        btn.classList.add("bg-red-600");

        buttons.forEach(b => {
            if (b.textContent === getCorrectAnswerText()) {
                b.classList.add("bg-green-600");
            }
        });
    }

    nextBtn.classList.remove("hidden");
}

function getCorrectAnswerText() {
    const q = questions[currentIndex];
    const correct = q.answers.find(a => a.is_correct);
    return correct ? correct.answer_text : "";
}

nextBtn.addEventListener("click", nextQuestion);

function nextQuestion() {
    currentIndex++;

    if (currentIndex >= questions.length) {
        finishGame();
        return;
    }

    renderQuestion();
    startTimer();

    nextBtn.classList.add("hidden");
}

function startTimer() {
    timeLeft = 15;
    timerEl.textContent = timeLeft;

    timer = setInterval(() => {
        timeLeft--;

        timerEl.textContent = timeLeft;

        const percent = (timeLeft / 15) * 100;
        progressBar.style.width = `${(currentIndex / questions.length) * 100 + percent / questions.length}%`;

        if (timeLeft <= 0) {
            clearInterval(timer);
            autoNext();
        }
    }, 1000);
}

function autoNext() {
    // si aucune réponse → on passe direct
    selectedAnswer = null;
    nextQuestion();
}

function resetState() {
    selectedAnswer = null;
    answersBox.innerHTML = "";
}

async function finishGame() {
    clearInterval(timer);

    const payload = {
        quiz_id: quiz.id,
        score: score,
        total_questions: questions.length
    };

    try {
        const res = await fetch("/api/game/finish.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.status === "success") {
            window.location.href = "/result.php?game=" + data.game_id;
        } else {
            alert(data.message || "Error finishing game");
        }
    } catch (e) {
        console.error(e);
        alert("Server error");
    }
}

startGame();