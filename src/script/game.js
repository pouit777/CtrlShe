// Game state metrics tracking parameters
let index = 0;
let score = 0;
let timer = null;
let timeLeft = 15;
let finished = false;

// Global game asset bindings fetched from injected metadata config
const quiz = GAME_DATA.quiz;
const questions = GAME_DATA.questions;

// Key-value store mapping question IDs to the player's selected answer IDs
const answersMap = {};

// Core DOM element references tracking for dynamic interface updating
const elQuestion = document.getElementById("questionText");
const elAnswers = document.getElementById("answers");
const elNext = document.getElementById("nextBtn");
const elScore = document.getElementById("score");
const elTimer = document.getElementById("timer");
const elProgress = document.getElementById("progressBar");
const elProgressText = document.getElementById("progressText");

// Initialize game cycle sequence
start();

function start() {
    render();
}

/**
 * Renders the active question view state, updates tracking progress bars,
 * dynamically generates selection options, and resets countdown routines.
 */
function render() {
    // Clear any existing active background intervals to prevent multi-firing bugs
    clearInterval(timer);

    const q = questions[index];

    // Populate interface layout containers with relative dataset parameters
    elQuestion.textContent = q.question_text;
    elProgressText.textContent = `Question ${index + 1} / ${questions.length}`;
    elProgress.style.width = `${((index + 1) / questions.length) * 100}%`;
    
    // Wipe clean historical multiple-choice option nodes
    elAnswers.innerHTML = "";
    elNext.classList.add("hidden");

    // Dynamic generation of interactive option buttons for the active question
    q.answers.forEach(answer => {
        const btn = document.createElement("button");

        btn.className = "answer-btn";
        btn.textContent = answer.answer_text;
        btn.onclick = () => selectAnswer(answer, btn);
        elAnswers.appendChild(btn);
    });

    // Fire background countdown timer sequence for the newly rendered question
    startTimer();
}

/**
 * Handles the interaction loop when a player selects an answer option.
 * Freezes interface states, updates point metrics, and reveals validation visual feedback.
 */
function selectAnswer(answer, btn) {
    // Safety guard clause: blocks updates if the active index has already been processed
    if (answersMap[questions[index].id]) return;
    
    clearInterval(timer);
    
    // Log user choice targeting inside state tracking dictionary
    answersMap[questions[index].id] = answer.id;

    // Isolate active controls to shield loop tracking from post-submission spam clicks
    const buttons = elAnswers.querySelectorAll("button");
    buttons.forEach(b => b.disabled = true);

    const correct = questions[index].answers.find(a => a.is_correct);
    
    // Check answer accuracy status and apply structural CSS outcome feedback indicators
    if (answer.is_correct) {
        btn.classList.remove("bg-gray-700");
        btn.classList.add("correct");
        score++;
        elScore.textContent = score;

    } else {
        btn.classList.remove("bg-gray-700");
        btn.classList.add("wrong");

        // Loop fallback: reveals the correct answer path when user inputs a wrong selection
        buttons.forEach((b, i) => {

            if (questions[index].answers[i].is_correct) {
                b.classList.remove("bg-gray-700");
                b.classList.add("correct");
            }
        });
    }

    // Reveal navigation controls to allow progression to the next stage
    elNext.classList.remove("hidden");
}

// Global click event hook managing linear screen progression loops
elNext.addEventListener("click", async () => {
    if (finished) return;
    // Intercept sequence workflows if the current scene index targets the final slide
    if (index === questions.length - 1) {
        finished = true;
        elNext.disabled = true;
        elNext.textContent = "Loading...";
        await finish();
        return;
    }

    // Advance internal tracking variables and repaint layout canvas views
    index++;
    render();
});

/**
 * Orchestrates countdown sequences per individual question view cycle,
 * triggers automated progression timeouts when metrics lapse.
 */
function startTimer() {
    timeLeft = 15;
    elTimer.textContent = timeLeft;
    
    timer = setInterval(() => {
        timeLeft--;
        elTimer.textContent = timeLeft;

        // Automation loop handler evaluating complete parameter exhaustion conditions
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

/**
 * Asynchronously posts aggregated user response maps back to system APIs,
 * routes workflow redirection targets depending on active session state profiles.
 */
async function finish() {

    clearInterval(timer);

    try {
        // Dispatches analytical scoring datasets to network endpoints via HTTP payload structures
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

        // Fallback alert notifications responding to generic processing failures
        if (data.status !== "success") {
            alert(data.message || "Unable to finish the quiz.");
            return;
        }

        // Branching condition: handle state synchronization configurations for Guest roles explicitly
        if (data.guest) {
            localStorage.setItem("guest_result", JSON.stringify({
                score: data.score,
                total: data.total,
                quiz_id: quiz.id
            }));

            window.location.href = "/result.php?guest=1&quiz=" + quiz.id;
            return;
        }

        // Standard operational routing profile path redirect targeting registered account indices
        window.location.href = "/result.php?game=" + data.game_id;

    } catch (e) {

        console.error(e);
        alert("Server error.");

    }

}