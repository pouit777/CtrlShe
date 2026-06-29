let index = 0;
let score = 0;
let timer = null;
let timeLeft = 15;
let finished = false;

const quiz = GAME_DATA.quiz;
const questions = GAME_DATA.questions;

const answersMap = {};

const elQuestion = document.getElementById("questionText");
const elAnswers = document.getElementById("answers");
const elNext = document.getElementById("nextBtn");
const elScore = document.getElementById("score");
const elTimer = document.getElementById("timer");
const elProgress = document.getElementById("progressBar");
const elProgressText = document.getElementById("progressText");

start();

function start() {
    render();
}

function render() {
    clearInterval(timer);

    const q = questions[index];

    elQuestion.textContent = q.question_text;
    elProgressText.textContent = `Question ${index + 1} / ${questions.length}`;
    elProgress.style.width = `${((index + 1) / questions.length) * 100}%`;
    elAnswers.innerHTML = "";
    elNext.classList.add("hidden");

    q.answers.forEach(answer => {
        const btn = document.createElement("button");

        btn.className = "answer-btn";
        btn.textContent = answer.answer_text;
        btn.onclick = () => selectAnswer(answer, btn);
        elAnswers.appendChild(btn);
    });

    startTimer();
}

function selectAnswer(answer, btn) {

    if (answersMap[questions[index].id]) return;
    clearInterval(timer);
    answersMap[questions[index].id] = answer.id;

    const buttons = elAnswers.querySelectorAll("button");
    buttons.forEach(b => b.disabled = true);

    const correct = questions[index].answers.find(a => a.is_correct);
    if (answer.is_correct) {
        btn.classList.remove("bg-gray-700");
        btn.classList.add("correct");
        score++;
        elScore.textContent = score;

    } else {
        btn.classList.remove("bg-gray-700");
        btn.classList.add("wrong");
        buttons.forEach((b, i) => {

            if (questions[index].answers[i].is_correct) {
                b.classList.remove("bg-gray-700");
                b.classList.add("correct");
            }
        });
    }
    elNext.classList.remove("hidden");
}

elNext.addEventListener("click", async () => {
    if (finished) return;
    if (index === questions.length - 1) {
        finished = true;
        elNext.disabled = true;
        elNext.textContent = "Loading...";
        await finish();
        return;
    }
    index++;
    render();
});

function startTimer() {
    timeLeft = 15;
    elTimer.textContent = timeLeft;
    timer = setInterval(() => {
        timeLeft--;
        elTimer.textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(timer);
            if (index === questions.length - 1) {
                finish();
            } else {
                index++;
                render();
            }
        }
    }, 1000);
}

async function finish() {

    clearInterval(timer);

    try {

        const response = await fetch("/api/games/finish.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({

                quiz_id: quiz.id,
                answers: answersMap

            })

        });

        const data = await response.json();

        console.log(data);

        if (data.status !== "success") {
            alert(data.message || "Unable to finish the quiz.");
            return;
        }

        if (data.guest) {

            localStorage.setItem("guest_result", JSON.stringify({

                score: data.score,
                total: data.total,
                quiz_id: quiz.id

            }));

            window.location.href = "/result.php?guest=1&quiz=" + quiz.id;
            return;
        }

        window.location.href = "/result.php?game=" + data.game_id;

    } catch (e) {

        console.error(e);
        alert("Server error.");

    }

}