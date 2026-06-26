let currentIndex = 0;
let score = 0;
let timer = null;
let timeLeft = 15;

const quiz = GAME_DATA.quiz;
const questions = GAME_DATA.questions;

const answersMap = {};

const questionText = document.getElementById("questionText");
const answersBox = document.getElementById("answers");
const nextBtn = document.getElementById("nextBtn");
const scoreEl = document.getElementById("score");
const progressText = document.getElementById("progressText");
const timerEl = document.getElementById("timer");
const progressBar = document.getElementById("progressBar");

let selected = false;

function startGame() {
    renderQuestion();
    startTimer();
}

function renderQuestion() {
    selected = false;
    answersBox.innerHTML = "";
    nextBtn.classList.add("hidden");

    const q = questions[currentIndex];

    questionText.textContent = q.question_text;
    progressText.textContent = `Question ${currentIndex + 1} / ${questions.length}`;

    progressBar.style.width = `${(currentIndex / questions.length) * 100}%`;

    q.answers.forEach(a => {
        const btn = document.createElement("button");

        btn.className = "w-full text-left p-3 rounded-lg bg-gray-700 hover:bg-gray-600 text-white";
        btn.textContent = a.answer_text;

        btn.onclick = () => selectAnswer(a, btn);

        answersBox.appendChild(btn);
    });
}

function selectAnswer(answer, btn) {
    if (selected) return;
    selected = true;

    clearInterval(timer);

    const questionId = questions[currentIndex].id;

    answersMap[questionId] = answer.id;

    const buttons = answersBox.querySelectorAll("button");
    buttons.forEach(b => b.disabled = true);

    const correctAnswer = questions[currentIndex].answers.find(a => a.is_correct);

    if (answer.is_correct) {
        btn.classList.add("bg-green-600");
        score++;
        scoreEl.textContent = score;
    } else {
        btn.classList.add("bg-red-600");

        buttons.forEach(b => {
            if (b.textContent === correctAnswer.answer_text) {
                b.classList.add("bg-green-600");
            }
        });
    }

    nextBtn.classList.remove("hidden");
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
}

function startTimer() {
    timeLeft = 15;
    timerEl.textContent = timeLeft;

    timer = setInterval(() => {
        timeLeft--;
        timerEl.textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(timer);
            nextQuestion();
        }
    }, 1000);
}

async function finishGame() {
    clearInterval(timer);

    try {
        const res = await fetch("/api/games/finish.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                quiz_id: quiz.id,
                answers: answersMap
            })
        });

        const data = await res.json();

        if (data.status === "success") {
            window.location.href = "/result.php?game=" + data.game_id;
        } else {
            alert(data.message);
        }

    } catch (e) {
        console.error(e);
        alert("Server error");
    }
}

startGame();