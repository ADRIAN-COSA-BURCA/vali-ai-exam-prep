console.log("MOCK EXAM ENGINE LOADED");

let currentQuestion = 0;
let timer = 90;
let timerInterval = null;
let answers = [];

function startTimer() {
    timer = 90;
    document.getElementById("timer-value").innerText = timer;
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        timer--;
        document.getElementById("timer-value").innerText = timer;
        if (timer <= 0) {
            recordAnswer(null, true);
            nextQuestion();
        }
    }, 1000);
}

function renderQuestion() {
    const q = examQuestions[currentQuestion];
    let html = `
        <h4 class="question-text">${currentQuestion + 1}. ${q.question}</h4>
        <form id="options-form" class="option-group">
    `;

    q.options.forEach((opt, index) => {
        html += `
            <label class="option-item">
                <input type="radio" name="option" value="${index}" onclick="selectOption(${index})">
                <span class="option-label">${opt}</span>
            </label>
        `;
    });

    html += `</form>`;
    document.getElementById("question-box").innerHTML = html;
    startTimer();
}

function selectOption(index) {
    recordAnswer(index, false);
    highlightSelection(index);
}

function highlightSelection(index) {
    document.querySelectorAll(".option-item").forEach((item, i) => {
        item.classList.toggle("selected", i === index);
    });
}

function recordAnswer(selected, timedOut) {
    const q = examQuestions[currentQuestion];
    answers[currentQuestion] = {
        selected: selected,
        correct: q.correct_index,
        timed_out: timedOut
    };
}

function nextQuestion() {
    if (currentQuestion < examQuestions.length - 1) {
        currentQuestion++;
        renderQuestion();
    } else {
        finishExam();
    }
}

function prevQuestion() {
    if (currentQuestion > 0) {
        currentQuestion--;
        renderQuestion();
    }
}

function finishExam() {
    clearInterval(timerInterval);
    let correct = 0, incorrect = 0;
    answers.forEach(a => {
        if (!a) return;
        if (a.selected === a.correct) correct++;
        else incorrect++;
    });
    const score = Math.round((correct / examQuestions.length) * 100);
    const passed = score >= 70;
    const payload = {
        material_id: materialId,
        answers: answers,
        score_percentage: score,
        correct_count: correct,
        incorrect_count: incorrect,
        passed: passed,
        time_taken_seconds: examQuestions.length * 90
    };
    fetch("/api/mock_exam_results.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (!data || data.status !== true) {
            throw new Error((data && data.error) ? data.error : 'Mock exam submission failed');
        }
        window.location.href = "/mock_exam_results.php?id=" + materialId;
    })
    .catch(err => console.error('Mock exam submission error:', err));
}

document.getElementById("next-btn").addEventListener("click", nextQuestion);
document.getElementById("prev-btn").addEventListener("click", prevQuestion);
document.getElementById("finish-btn").addEventListener("click", finishExam);

renderQuestion();
