console.log("MOCK EXAM ENGINE LOADED");

let currentQuestion = 0;
let timer = 90;
let timerInterval = null;
let answers = [];
let isSubmitting = false;

function startTimer() {
    timer = 90;
    const timerEl = document.getElementById("timer-value");
    if (timerEl) {
        timerEl.innerText = timer;
    }
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        timer--;
        if (timerEl) {
            timerEl.innerText = timer;
        }
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
    answers[currentQuestion] = {
        question_index: currentQuestion,
        selected: selected,
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

function showSubmissionMessage(message, actionUrl, actionText, isError) {
    let msgContainer = document.getElementById("submission-feedback");
    if (!msgContainer) {
        msgContainer = document.createElement("div");
        msgContainer.id = "submission-feedback";
        const questionBox = document.getElementById("question-box");
        if (questionBox && questionBox.parentNode) {
            questionBox.parentNode.insertBefore(msgContainer, questionBox);
        }
    }
    const alertClass = isError ? "alert-danger" : "alert-info";
    let actionBtnHtml = "";
    if (actionUrl && actionText) {
        actionBtnHtml = `<div style="margin-top: 10px;"><a href="${actionUrl}" class="btn btn-primary btn-sm">${actionText}</a></div>`;
    }
    msgContainer.innerHTML = `
        <div class="alert ${alertClass}" style="padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; border: 1px solid ${isError ? '#f5c2c7' : '#b6effb'}; background-color: ${isError ? '#f8d7da' : '#cff4fc'}; color: ${isError ? '#842029' : '#055160'};">
            <strong>${isError ? 'Notice:' : 'Information:'}</strong> ${message}
            ${actionBtnHtml}
        </div>
    `;
    msgContainer.scrollIntoView({ behavior: 'smooth' });
}

function finishExam() {
    if (isSubmitting) return;
    isSubmitting = true;
    clearInterval(timerInterval);

    const finishBtn = document.getElementById("finish-btn");
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    if (finishBtn) {
        finishBtn.disabled = true;
        finishBtn.innerText = "Submitting...";
    }
    if (prevBtn) prevBtn.disabled = true;
    if (nextBtn) nextBtn.disabled = true;

    // Build sanitised submitted answers
    const sanitizedAnswers = [];
    for (let i = 0; i < examQuestions.length; i++) {
        const a = answers[i];
        if (a) {
            sanitizedAnswers.push({
                question_index: i,
                selected: (a.selected !== undefined) ? a.selected : null,
                timed_out: !!a.timed_out
            });
        }
    }

    const payload = {
        material_id: materialId,
        answers: sanitizedAnswers,
        time_taken_seconds: examQuestions.length * 90
    };

    fetch("/api/mock_exam_results.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload)
    })
    .then(async res => {
        const data = await res.json().catch(() => null);

        if (res.status === 409) {
            const redirectId = (data && data.material_id) ? data.material_id : materialId;
            showSubmissionMessage(
                "This exam attempt has already been submitted and completed.",
                "/mock_exam_results.php?id=" + redirectId,
                "View Results",
                false
            );
            return;
        }

        if (!res.ok || !data || data.status !== true) {
            const errMsg = (data && data.error) ? data.error : "Failed to save mock exam results. Please try again.";
            throw new Error(errMsg);
        }

        const redirectId = data.material_id || materialId;
        window.location.href = "/mock_exam_results.php?id=" + redirectId;
    })
    .catch(err => {
        console.error('Mock exam submission error:', err);
        isSubmitting = false;
        if (finishBtn) {
            finishBtn.disabled = false;
            finishBtn.innerText = "Finish";
        }
        if (prevBtn) prevBtn.disabled = false;
        if (nextBtn) nextBtn.disabled = false;
        showSubmissionMessage(err.message || "An error occurred while submitting your exam. Please try again.", null, null, true);
    });
}

document.getElementById("next-btn").addEventListener("click", nextQuestion);
document.getElementById("prev-btn").addEventListener("click", prevQuestion);
document.getElementById("finish-btn").addEventListener("click", finishExam);

renderQuestion();
