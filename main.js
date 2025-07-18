// main.js untuk Kuis Al-Qur'an Interaktif

let questions = [];
let currentQuestion = 0;
let score = 0;
let wrongAnswers = [];
let selectedOption = null;

// Fetch questions from API
async function fetchQuestions() {
    try {
        const timestamp = new Date().getTime();
        const response = await fetch(`api/get-questions.php?limit=10&t=${timestamp}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching questions:', error);
        return [];
    }
}
async function startQuiz() {
    // Show loading indicator
    document.getElementById('landing-page').classList.add('hidden');
    document.getElementById('quiz-page').classList.remove('hidden');
    document.getElementById('question-text').textContent = 'Loading questions...';
    
    // Fetch questions
    questions = await fetchQuestions();
    
    if (questions.length === 0) {
        document.getElementById('question-text').textContent = 
            'Error loading questions. Please try again later.';
        return;
    }
    
    currentQuestion = 0;
    score = 0;
    wrongAnswers = [];
    selectedOption = null;
    showQuestion();
}

function showQuestion() {
    const question = questions[currentQuestion];
    document.getElementById('question-counter').textContent = `Soal ${currentQuestion + 1} dari ${questions.length}`;
    const progress = ((currentQuestion + 1) / questions.length) * 100;
    document.getElementById('progress-bar').style.width = progress + '%';
    document.getElementById('question-text').textContent = question.text;
    document.getElementById('arabic-text').textContent = question.arabic_text || '';
    document.getElementById('feedback').classList.add('hidden');
    document.getElementById('submit-btn').classList.remove('hidden');
    document.getElementById('next-btn').classList.add('hidden');
    const optionsContainer = document.getElementById('options-container');
    const textInput = document.getElementById('text-input');
    if (question.type === 'multiple') {
        optionsContainer.innerHTML = '';
        textInput.classList.add('hidden');
        question.options.forEach((option, index) => {
            const optionBtn = document.createElement('button');
            optionBtn.className = 'option-btn';
            optionBtn.textContent = option;
            optionBtn.onclick = () => selectOption(optionBtn, option);
            optionsContainer.appendChild(optionBtn);
        });
    } else {
        optionsContainer.innerHTML = '';
        textInput.classList.remove('hidden');
        textInput.value = '';
        selectedOption = null;
    }
}

function selectOption(btn, option) {
    document.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedOption = option;
}

function submitAnswer() {
    const question = questions[currentQuestion];
    let userAnswer = selectedOption;
    if (question.type === 'text') {
        userAnswer = document.getElementById('text-input').value.trim();
    }
    if (!userAnswer) {
        alert('Silakan pilih atau masukkan jawaban!');
        return;
    }
    const isCorrect = userAnswer.toLowerCase() === question.answer.toLowerCase();
    if (isCorrect) {
        score++;
        showFeedback(true, `Benar! Jawaban yang tepat adalah: ${question.answer}`);
        if (question.type === 'multiple') {
            document.querySelector('.option-btn.selected').classList.add('correct');
        }
    } else {
        showFeedback(false, `Salah! Jawaban yang benar adalah: ${question.answer}`);
        wrongAnswers.push({
            question: question.text,
            arabicText: question.arabic_text,
            userAnswer: userAnswer,
            correctAnswer: question.answer
        });
        if (question.type === 'multiple') {
            document.querySelector('.option-btn.selected').classList.add('incorrect');
            document.querySelectorAll('.option-btn').forEach(btn => {
                if (btn.textContent === question.answer) {
                    btn.classList.add('correct');
                }
            });
        }
    }
    document.querySelectorAll('.option-btn').forEach(btn => btn.disabled = true);
    document.getElementById('text-input').disabled = true;
    document.getElementById('submit-btn').classList.add('hidden');
    document.getElementById('next-btn').classList.remove('hidden');
}

function showFeedback(isCorrect, message) {
    const feedback = document.getElementById('feedback');
    feedback.textContent = message;
    feedback.className = `feedback ${isCorrect ? 'correct' : 'incorrect'}`;
    feedback.classList.remove('hidden');
}

function nextQuestion() {
    currentQuestion++;
    if (currentQuestion < questions.length) {
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.disabled = false;
            btn.className = 'option-btn';
        });
        document.getElementById('text-input').disabled = false;
        selectedOption = null;
        showQuestion();
    } else {
        showResults();
    }
}

async function showResults() {
    document.getElementById('quiz-page').classList.add('hidden');
    document.getElementById('result-page').classList.remove('hidden');
    const percentage = Math.round((score / questions.length) * 100);
    document.getElementById('final-score').textContent = `${score}/${questions.length}`;
    document.getElementById('score-percentage').textContent = `${percentage}%`;
    document.getElementById('correct-answers').textContent = score;
    document.getElementById('incorrect-answers').textContent = questions.length - score;
    const wrongSection = document.getElementById('wrong-answers-section');
    const wrongList = document.getElementById('wrong-answers-list');
    if (wrongAnswers.length > 0) {
        wrongList.innerHTML = '';
        wrongAnswers.forEach(wrong => {
            const wrongItem = document.createElement('div');
            wrongItem.className = 'wrong-item';
            wrongItem.innerHTML = `
                <strong>Pertanyaan:</strong> ${wrong.question}<br>
                ${wrong.arabicText ? `<strong>Ayat:</strong> ${wrong.arabicText}<br>` : ''}
                <strong>Jawaban Anda:</strong> ${wrong.userAnswer}<br>
                <strong>Jawaban Benar:</strong> ${wrong.correctAnswer}
            `;
            wrongList.appendChild(wrongItem);
        });
    } else {
        wrongSection.innerHTML = '<div style="text-align: center; color: #27ae60; font-size: 1.2rem;">🎉 Sempurna! Semua jawaban benar!</div>';
    }
    
    // Save results to database
    try {
        const response = await fetch('api/save-results.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                score: score,
                totalQuestions: questions.length,
                percentage: percentage
            }),
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Result saved:', result);
    } catch (error) {
        console.error('Error saving results:', error);
    }
}

function restartQuiz() {
    currentQuestion = 0;
    score = 0;
    wrongAnswers = [];
    selectedOption = null;
    document.getElementById('result-page').classList.add('hidden');
    startQuiz();
}

function backToHome() {
    currentQuestion = 0;
    score = 0;
    wrongAnswers = [];
    selectedOption = null;
    document.getElementById('result-page').classList.add('hidden');
    document.getElementById('landing-page').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('landing-page').classList.remove('hidden');
});