import type {
    API,
    BlockTool,
    BlockToolConstructorOptions,
    BlockToolData,
} from '@editorjs/editorjs';

interface QuizQuestion {
    id: string;
    question: string;
    imageUrl?: string;
    answers: {
        id: string;
        text: string;
        isCorrect: boolean;
    }[];
}

interface QuizBlockData extends BlockToolData {
    questions: QuizQuestion[];
}

interface QuizBlockConfig {
    placeholder?: string;
}

export default class QuizBlock implements BlockTool {
    private api: API;
    private data: QuizBlockData;
    private readOnly: boolean;
    private config: QuizBlockConfig;
    private wrapper: HTMLDivElement | null = null;

    constructor({
        api,
        data,
        readOnly,
        config = {},
    }: BlockToolConstructorOptions<QuizBlockData, QuizBlockConfig>) {
        this.api = api;
        this.readOnly = Boolean(readOnly);
        this.config = config;

        this.data = {
            questions: data?.questions ?? [this.createEmptyQuestion()],
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Quiz',
            icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.6"/><path d="M3 9h18M9 21V9" stroke="currentColor" stroke-width="1.6"/></svg>',
        };
    }

    private generateId(): string {
        return typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
            ? crypto.randomUUID()
            : `id-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
    }

    private createEmptyQuestion(): QuizQuestion {
        return {
            id: this.generateId(),
            question: '',
            imageUrl: undefined,
            answers: [
                { id: this.generateId(), text: '', isCorrect: true },
                { id: this.generateId(), text: '', isCorrect: false },
            ],
        };
    }

    private createEmptyAnswer(isCorrect = false): { id: string; text: string; isCorrect: boolean } {
        return {
            id: this.generateId(),
            text: '',
            isCorrect,
        };
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'quiz-block';

        this.renderQuestions();

        return this.wrapper;
    }

    private renderQuestions() {
        if (!this.wrapper) return;
        this.wrapper.innerHTML = '';

        // Header
        const header = document.createElement('div');
        header.className = 'quiz-block__header';
        header.innerHTML = `
            <div class="quiz-block__title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                    <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                <span>Quiz (${this.data.questions.length} Frage${this.data.questions.length !== 1 ? 'n' : ''})</span>
            </div>
        `;

        if (!this.readOnly) {
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'quiz-block__add-question';
            addBtn.innerHTML = `
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Frage hinzufügen
            `;
            addBtn.addEventListener('click', () => {
                this.data.questions.push(this.createEmptyQuestion());
                this.renderQuestions();
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            });
            header.appendChild(addBtn);
        }

        this.wrapper.appendChild(header);

        // Questions container
        const questionsContainer = document.createElement('div');
        questionsContainer.className = 'quiz-block__questions';

        this.data.questions.forEach((question, qIndex) => {
            const questionEl = this.renderQuestion(question, qIndex);
            questionsContainer.appendChild(questionEl);
        });

        this.wrapper.appendChild(questionsContainer);
    }

    private renderQuestion(question: QuizQuestion, qIndex: number): HTMLElement {
        const questionWrapper = document.createElement('div');
        questionWrapper.className = 'quiz-block__question';
        questionWrapper.dataset.questionId = question.id;

        // Question header with number and delete button
        const questionHeader = document.createElement('div');
        questionHeader.className = 'quiz-block__question-header';

        const questionNumber = document.createElement('span');
        questionNumber.className = 'quiz-block__question-number';
        questionNumber.textContent = `Frage ${qIndex + 1}`;
        questionHeader.appendChild(questionNumber);

        if (!this.readOnly && this.data.questions.length > 1) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'quiz-block__delete-question';
            deleteBtn.innerHTML = `
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                    <path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            `;
            deleteBtn.title = 'Frage löschen';
            deleteBtn.addEventListener('click', () => {
                this.data.questions.splice(qIndex, 1);
                this.renderQuestions();
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            });
            questionHeader.appendChild(deleteBtn);
        }

        questionWrapper.appendChild(questionHeader);

        // Image upload area
        const imageArea = document.createElement('div');
        imageArea.className = 'quiz-block__image-area';

        if (question.imageUrl) {
            const imgPreview = document.createElement('div');
            imgPreview.className = 'quiz-block__image-preview';
            imgPreview.innerHTML = `
                <img src="${question.imageUrl}" alt="Frage-Bild" />
            `;

            if (!this.readOnly) {
                const removeImgBtn = document.createElement('button');
                removeImgBtn.type = 'button';
                removeImgBtn.className = 'quiz-block__remove-image';
                removeImgBtn.innerHTML = `
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                        <path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                `;
                removeImgBtn.title = 'Bild entfernen';
                removeImgBtn.addEventListener('click', () => {
                    question.imageUrl = undefined;
                    this.renderQuestions();
                    this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
                });
                imgPreview.appendChild(removeImgBtn);
            }

            imageArea.appendChild(imgPreview);
        } else if (!this.readOnly) {
            const uploadArea = document.createElement('div');
            uploadArea.className = 'quiz-block__upload-area';
            uploadArea.innerHTML = `
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Bild hochladen (optional)</span>
            `;

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp';
            fileInput.className = 'quiz-block__file-input';
            fileInput.addEventListener('change', async (e) => {
                const target = e.target as HTMLInputElement;
                const file = target.files?.[0];
                if (file) {
                    await this.uploadImage(file, question);
                }
            });

            uploadArea.appendChild(fileInput);
            uploadArea.addEventListener('click', () => fileInput.click());

            imageArea.appendChild(uploadArea);
        }

        questionWrapper.appendChild(imageArea);

        // Question text input
        const questionInput = document.createElement('input');
        questionInput.type = 'text';
        questionInput.className = 'quiz-block__question-input';
        questionInput.placeholder = 'Frage eingeben…';
        questionInput.value = question.question;
        questionInput.disabled = this.readOnly;
        questionInput.addEventListener('input', (e) => {
            question.question = (e.target as HTMLInputElement).value;
        });
        questionInput.addEventListener('blur', () => {
            this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
        });

        questionWrapper.appendChild(questionInput);

        // Answers
        const answersContainer = document.createElement('div');
        answersContainer.className = 'quiz-block__answers';

        question.answers.forEach((answer, aIndex) => {
            const answerEl = this.renderAnswer(answer, aIndex, question);
            answersContainer.appendChild(answerEl);
        });

        questionWrapper.appendChild(answersContainer);

        // Add answer button
        if (!this.readOnly) {
            const addAnswerBtn = document.createElement('button');
            addAnswerBtn.type = 'button';
            addAnswerBtn.className = 'quiz-block__add-answer';
            addAnswerBtn.innerHTML = `
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Antwort hinzufügen
            `;
            addAnswerBtn.addEventListener('click', () => {
                question.answers.push(this.createEmptyAnswer());
                this.renderQuestions();
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            });
            questionWrapper.appendChild(addAnswerBtn);
        }

        return questionWrapper;
    }

    private renderAnswer(
        answer: { id: string; text: string; isCorrect: boolean },
        aIndex: number,
        question: QuizQuestion
    ): HTMLElement {
        const answerWrapper = document.createElement('div');
        answerWrapper.className = `quiz-block__answer ${answer.isCorrect ? 'quiz-block__answer--correct' : ''}`;

        // Radio button for correct answer
        const radioWrapper = document.createElement('label');
        radioWrapper.className = 'quiz-block__radio-wrapper';

        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = `quiz-correct-${question.id}`;
        radio.checked = answer.isCorrect;
        radio.disabled = this.readOnly;
        radio.addEventListener('change', () => {
            question.answers.forEach((a) => (a.isCorrect = false));
            answer.isCorrect = true;
            this.renderQuestions();
            this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
        });

        const radioIcon = document.createElement('span');
        radioIcon.className = 'quiz-block__radio-icon';
        radioIcon.innerHTML = answer.isCorrect
            ? `<svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M8 12l3 3 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
               </svg>`
            : `<svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
               </svg>`;

        radioWrapper.appendChild(radio);
        radioWrapper.appendChild(radioIcon);
        answerWrapper.appendChild(radioWrapper);

        // Answer letter
        const letterSpan = document.createElement('span');
        letterSpan.className = 'quiz-block__answer-letter';
        letterSpan.textContent = String.fromCharCode(65 + aIndex); // A, B, C, D
        answerWrapper.appendChild(letterSpan);

        // Answer text input
        const answerInput = document.createElement('input');
        answerInput.type = 'text';
        answerInput.className = 'quiz-block__answer-input';
        answerInput.placeholder = `Antwort ${String.fromCharCode(65 + aIndex)}…`;
        answerInput.value = answer.text;
        answerInput.disabled = this.readOnly;
        answerInput.addEventListener('input', (e) => {
            answer.text = (e.target as HTMLInputElement).value;
        });
        answerInput.addEventListener('blur', () => {
            this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
        });

        answerWrapper.appendChild(answerInput);

        // Delete answer button (only if more than 2 answers)
        if (!this.readOnly && question.answers.length > 2) {
            const deleteAnswerBtn = document.createElement('button');
            deleteAnswerBtn.type = 'button';
            deleteAnswerBtn.className = 'quiz-block__delete-answer';
            deleteAnswerBtn.innerHTML = `
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                    <path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            `;
            deleteAnswerBtn.title = 'Antwort löschen';
            deleteAnswerBtn.addEventListener('click', () => {
                const idx = question.answers.findIndex((a) => a.id === answer.id);
                if (idx !== -1) {
                    // If deleting the correct answer, make the first remaining one correct
                    const wasCorrect = question.answers[idx].isCorrect;
                    question.answers.splice(idx, 1);
                    if (wasCorrect && question.answers.length > 0) {
                        question.answers[0].isCorrect = true;
                    }
                    this.renderQuestions();
                    this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
                }
            });
            answerWrapper.appendChild(deleteAnswerBtn);
        }

        return answerWrapper;
    }

    private async uploadImage(file: File, question: QuizQuestion): Promise<void> {
        const formData = new FormData();
        formData.append('image', file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        try {
            const response = await fetch('/upload/image', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const result = await response.json();

            if (result.success === 1) {
                question.imageUrl = result.file.url;
                this.renderQuestions();
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            } else {
                console.error('Image upload failed:', result.message);
                alert('Bild konnte nicht hochgeladen werden. Bitte versuche es erneut.');
            }
        } catch (error) {
            console.error('Image upload error:', error);
            alert('Fehler beim Hochladen des Bildes.');
        }
    }

    async save(): Promise<QuizBlockData> {
        return {
            questions: this.data.questions,
        };
    }

    validate(data: QuizBlockData): boolean {
        // At least one question with question text and at least one answer
        return data.questions.some(
            (q) => q.question.trim() !== '' && q.answers.some((a) => a.text.trim() !== '')
        );
    }
}

