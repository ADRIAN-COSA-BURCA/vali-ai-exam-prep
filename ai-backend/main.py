from fastapi import FastAPI
from pydantic import BaseModel
import os
import base64
import json
import random
import io
from openai import OpenAI
import pdfplumber
from docx import Document

app = FastAPI()

client = OpenAI(api_key=os.getenv("OPENAI_API_KEY"))


class AIRequest(BaseModel):
    mode: str
    exam_name: str
    content_type: str
    difficulty: str
    flashcard_count: int | None = None
    question_count: int | None = None
    file_content: str | None = None
    file_type: str | None = None


@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "ai-backend"
    }


@app.post("/generate")
async def generate(req: AIRequest):

    if req.mode == "online":
        content = f"Exam: {req.exam_name}. Difficulty: {req.difficulty}."
    else:
        content = extract_text_from_upload(req.file_content, req.file_type)

    prompt = build_prompt(
        content,
        req.exam_name,
        req.content_type,
        req.difficulty,
        req.flashcard_count or 10,
        req.question_count or 10,
    )

    try:
        response = client.chat.completions.create(
            model=os.getenv("OPENAI_MODEL", "gpt-4o-mini"),
            messages=[
                {
                    "role": "system",
                    "content": (
                        "You are an AI tutor that ALWAYS returns valid JSON only. "
                        "No markdown, no explanations outside JSON."
                    )
                },
                {"role": "user", "content": prompt},
            ],
            temperature=0.9,
            top_p=0.9,
            max_tokens=4000,
        )

        content = response.choices[0].message.content
        data = json.loads(content)

        validation_error = validate_ai_response(data, req.content_type)

        if validation_error:
            return {
                "type": "error",
                "error": "Generated content failed structural validation",
                "details": validation_error
            }

        return data

    except json.JSONDecodeError:
        return {
            "type": "error",
            "error": "The AI response was not valid JSON"
        }

    except Exception as e:
        return {
            "type": "error",
            "error": str(e)
        }


def validate_ai_response(data: dict, content_type: str) -> str | None:
    if not isinstance(data, dict):
        return "Response must be a JSON object"

    if data.get("type") != content_type:
        return f"Expected response type '{content_type}'"

    if content_type == "summary":
        return validate_summary(data)

    if content_type == "flashcards":
        return validate_flashcards(data)

    if content_type == "questions":
        return validate_questions(data)

    return "Unsupported content type"


def validate_summary(data: dict) -> str | None:
    summary = data.get("summary")

    if not isinstance(summary, list) or not summary:
        return "Summary must be a non-empty list"

    for index, topic in enumerate(summary):
        if not isinstance(topic, dict):
            return f"Summary topic {index + 1} must be an object"

        if not isinstance(topic.get("topic"), str) or not topic["topic"].strip():
            return f"Summary topic {index + 1} has no valid topic name"

        points = topic.get("points")

        if not isinstance(points, list) or len(points) < 5:
            return f"Summary topic {index + 1} must contain at least five points"

        if any(not isinstance(point, str) or not point.strip() for point in points):
            return f"Summary topic {index + 1} contains an empty point"

    return None


def validate_flashcards(data: dict) -> str | None:
    flashcards = data.get("flashcards")

    if not isinstance(flashcards, list) or not flashcards:
        return "Flashcards must be a non-empty list"

    for index, card in enumerate(flashcards):
        if not isinstance(card, dict):
            return f"Flashcard {index + 1} must be an object"

        question = card.get("question")
        answer = card.get("answer")

        if not isinstance(question, str) or not question.strip():
            return f"Flashcard {index + 1} has no valid question"

        if not isinstance(answer, str) or not answer.strip():
            return f"Flashcard {index + 1} has no valid answer"

    return None


def validate_questions(data: dict) -> str | None:
    questions = data.get("questions")

    if not isinstance(questions, list) or not questions:
        return "Questions must be a non-empty list"

    for index, question in enumerate(questions):
        if not isinstance(question, dict):
            return f"Question {index + 1} must be an object"

        question_text = question.get("question")
        options = question.get("options")
        correct_index = question.get("correct_index")
        explanation = question.get("explanation")

        if not isinstance(question_text, str) or not question_text.strip():
            return f"Question {index + 1} has no valid question text"

        if not isinstance(options, list) or len(options) != 4:
            return f"Question {index + 1} must contain exactly four options"

        if any(not isinstance(option, str) or not option.strip() for option in options):
            return f"Question {index + 1} contains an empty option"

        if not isinstance(correct_index, int) or correct_index < 0 or correct_index > 3:
            return f"Question {index + 1} has an invalid correct_index"

        if not isinstance(explanation, str) or not explanation.strip():
            return f"Question {index + 1} has no valid explanation"

    return None


def extract_text_from_upload(
    file_content_b64: str | None,
    file_type: str | None
) -> str:
    if not file_content_b64:
        return ""

    try:
        raw_bytes = base64.b64decode(file_content_b64)
    except Exception:
        return ""

    file_type = (file_type or "").lower().strip()

    try:
        if file_type == "pdf":
            text = extract_text_from_pdf(raw_bytes)
        elif file_type == "docx":
            text = extract_text_from_docx(raw_bytes)
        else:
            text = raw_bytes.decode("utf-8", errors="ignore")

        if not text or not text.strip():
            return ""

        lines = [line for line in text.splitlines() if line.strip()]
        combined = "\n".join(lines)

        max_chars = 15000

        if len(combined) > max_chars:
            combined = combined[:max_chars]

        return combined

    except Exception:
        return ""


def extract_text_from_pdf(raw_bytes: bytes) -> str:
    text_parts = []

    with pdfplumber.open(io.BytesIO(raw_bytes)) as pdf:
        for page in pdf.pages:
            page_text = page.extract_text()

            if page_text:
                text_parts.append(page_text)

    return "\n".join(text_parts)


def extract_text_from_docx(raw_bytes: bytes) -> str:
    doc = Document(io.BytesIO(raw_bytes))
    paragraphs = [
        paragraph.text
        for paragraph in doc.paragraphs
        if paragraph.text.strip()
    ]

    return "\n".join(paragraphs)


def build_prompt(
    content: str,
    exam_name: str,
    content_type: str,
    difficulty: str,
    flashcard_count: int,
    question_count: int
) -> str:

    random_seed = random.randint(10000, 99999)

    base = f"""
You are an AI tutor generating study material.

Exam: {exam_name}
Difficulty: {difficulty}

Random seed: {random_seed}

Source content:
\"\"\"{content}\"\"\"

IMPORTANT:
- ALWAYS vary wording, examples, and structure based on the exam context.
- NEVER repeat previous generations.
- ALWAYS produce unique questions and flashcards relevant to {exam_name}.
- Focus on the core topics and domains specific to the {exam_name} exam.
- If the source content above contains real exam material, PRIORITIZE and BASE your output on it.
"""

    if content_type == "summary":
        depth_guidance = {
            "easy": (
                "Keep explanations simple and beginner-friendly, "
                "avoiding jargon where possible."
            ),
            "medium": (
                "Provide moderate technical detail suitable for someone "
                "with basic familiarity."
            ),
            "hard": (
                "Provide precise, technical, exam-level detail including "
                "edge cases and comparisons between similar concepts."
            )
        }

        depth_note = depth_guidance.get(
            difficulty.lower(),
            depth_guidance["medium"]
        )

        return base + f"""
Task: Generate a comprehensive, structured revision study guide for the
{exam_name} exam.

CRITICAL RULES:
- DO NOT describe what the exam is, its format, timing, or scoring.
- DO NOT write meta-commentary.
- Generate the actual subject-matter knowledge a candidate must learn.
- Break the content into 5 to 7 topic or domain sections.
- Each section must contain at least 5 points.
- Explain why each point matters or how it may be tested.
- Difficulty level: {difficulty}. {depth_note}
- Points must be specific and exam-relevant.

Return ONLY valid JSON:
{{
  "type": "summary",
  "summary": [
    {{
      "topic": "Topic or Domain Name",
      "points": [
        "Specific factual point 1",
        "Specific factual point 2",
        "Specific factual point 3",
        "Specific factual point 4",
        "Specific factual point 5"
      ]
    }}
  ]
}}
"""

    if content_type == "flashcards":
        return base + f"""
Task: Generate {flashcard_count} unique flashcards.

Rules:
- Each flashcard must be different.
- Use varied examples and wording.
- Cover different subtopics.
- Each flashcard must contain a question and answer.

Return ONLY valid JSON:
{{
  "type": "flashcards",
  "flashcards": [
    {{
      "question": "Question",
      "answer": "Answer"
    }}
  ]
}}
"""

    if content_type == "questions":
        return base + f"""
Task: Generate {question_count} unique single-choice questions.

Rules:
- Each question must have exactly four options.
- Only one option must be correct.
- Include an explanation for the correct answer.
- Match the selected difficulty.
- Return no markdown or text outside JSON.

Return ONLY valid JSON:
{{
  "type": "questions",
  "questions": [
    {{
      "question": "Question text",
      "options": [
        "Option A",
        "Option B",
        "Option C",
        "Option D"
      ],
      "correct_index": 0,
      "explanation": "Explanation"
    }}
  ]
}}
"""

    return base + """
Task: Provide a structured study guide.

Return ONLY valid JSON:
{
  "type": "summary",
  "summary": [
    {
      "topic": "General Overview",
      "points": [
        "Specific factual point 1",
        "Specific factual point 2",
        "Specific factual point 3",
        "Specific factual point 4",
        "Specific factual point 5"
      ]
    }
  ]
}
"""