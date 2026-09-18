document.addEventListener('DOMContentLoaded', () => {
    // Toggle Upload File Field based on Selected Mode
    const uploadMode = document.getElementById('mode-upload');
    const onlineMode = document.getElementById('mode-online');
    const uploadContainer = document.getElementById('upload-container');
    const studyDoc = document.getElementById('study_doc');

    if (uploadMode && onlineMode && uploadContainer) {
        const toggleUpload = () => {
            if (uploadMode.checked) {
                uploadContainer.style.display = 'block';
                if (studyDoc) studyDoc.setAttribute('required', 'required');
            } else {
                uploadContainer.style.display = 'none';
                if (studyDoc) studyDoc.removeAttribute('required');
            }
        };
        uploadMode.addEventListener('change', toggleUpload);
        onlineMode.addEventListener('change', toggleUpload);
        toggleUpload(); // Initialize
    }

    // Flashcard Flip Interaction
    document.querySelectorAll('.flashcard-wrapper').forEach(card => {
        card.addEventListener('click', () => {
            const inner = card.querySelector('.flashcard');
            if (inner) inner.classList.toggle('flipped');
        });
    });
});

console.log("APP.JS LOADED");


document.addEventListener('DOMContentLoaded', function () {
    const methodSelect = document.getElementById('content_type');
    const uploadRadio = document.getElementById('mode-upload');
    const onlineRadio = document.getElementById('mode-online');

    const uploadContainer = document.getElementById('upload-container');
    const flashcardGroup = document.getElementById('flashcard-count-group');
    const questionGroup = document.getElementById('question-count-group');
    const difficultyGroup = document.getElementById('difficulty-group');

    // Only run this block if these elements exist on the page
    if (methodSelect && uploadRadio && onlineRadio && uploadContainer) {

        const updateUpload = () => {
            uploadContainer.style.display = uploadRadio.checked ? 'block' : 'none';
        };

        uploadRadio.addEventListener('change', updateUpload);
        onlineRadio.addEventListener('change', updateUpload);
        updateUpload();

        const updateMethod = () => {
            const method = methodSelect.value;

            flashcardGroup.style.display = 'none';
            questionGroup.style.display = 'none';
            difficultyGroup.style.display = 'none';

            if (method === 'flashcards') {
                flashcardGroup.style.display = 'block';
            }

            if (method === 'questions') {
                questionGroup.style.display = 'block';
                difficultyGroup.style.display = 'block';
            }
        };

        methodSelect.addEventListener('change', updateMethod);
        updateMethod();
    }
});



