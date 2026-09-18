console.log("FLASHCARDS ENGINE (flip-only) LOADED");

let sessionLogged = false;

document.addEventListener("DOMContentLoaded", () => {
    const cards = document.querySelectorAll(".flashcard-wrapper");
    const materialId = window.materialId;

    if (!cards.length) {
        console.warn("Flashcards: no cards found on page.");
        return;
    }

    if (!materialId) {
        console.warn("Flashcards: materialId is missing.");
        return;
    }

    cards.forEach(card => {
        card.addEventListener("click", () => {
            // NOTE: App-wide `app.js` also toggles the visual flip state.
            // To avoid double-toggle (which cancels out) this handler
            // ONLY logs the session on first click. The visual flip is
            // handled centrally in `app.js`.
            if (!sessionLogged) {
                sessionLogged = true;
                logFlashcardSession(materialId);
            }
        });
    });
});

function logFlashcardSession(materialId) {
    fetch("/api/flashcard_session.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ material_id: materialId })
    })
    .then(res => res.json())
    .then(data => {
        console.log("Flashcard session logged:", data);
    })
    .catch(err => console.error("Flashcard session error:", err));
}
