<?php
// MockExamScorer.php - Pure scoring and validation unit for mock examinations

class MockExamScorer {

    /**
     * Validates stored questions and submitted answers, and calculates authoritative score.
     *
     * @param mixed $storedQuestions Array of questions from raw_results_json['questions']
     * @param mixed $submittedAnswers Array of answer objects from client submission
     * @return array
     */
    public static function validateAndScore($storedQuestions, $submittedAnswers) {
        // 1. Validate Stored Examination Questions
        if (!is_array($storedQuestions) || count($storedQuestions) === 0) {
            return [
                'valid' => false,
                'error' => 'Stored examination questions are invalid or empty',
                'http_code' => 422
            ];
        }

        $totalQuestions = count($storedQuestions);

        for ($i = 0; $i < $totalQuestions; $i++) {
            $q = $storedQuestions[$i];
            if (!is_array($q) || !isset($q['question']) || !isset($q['options']) || !array_key_exists('correct_index', $q)) {
                return [
                    'valid' => false,
                    'error' => "Stored question at index {$i} is missing required fields",
                    'http_code' => 422
                ];
            }

            if (!is_array($q['options']) || count($q['options']) < 2) {
                return [
                    'valid' => false,
                    'error' => "Stored question at index {$i} has invalid options",
                    'http_code' => 422
                ];
            }

            if (!is_int($q['correct_index']) || $q['correct_index'] < 0 || $q['correct_index'] >= count($q['options'])) {
                return [
                    'valid' => false,
                    'error' => "Stored question at index {$i} has invalid correct_index",
                    'http_code' => 422
                ];
            }
        }

        // 2. Validate Submitted Answers Structure
        if (!is_array($submittedAnswers)) {
            return [
                'valid' => false,
                'error' => 'Submitted answers must be an array',
                'http_code' => 400
            ];
        }

        $seenIndexes = [];
        $submittedMap = [];

        foreach ($submittedAnswers as $entryIdx => $ans) {
            if ($ans === null) {
                continue; // sparse array gap
            }

            if (!is_array($ans)) {
                return [
                    'valid' => false,
                    'error' => "Submitted answer entry at index {$entryIdx} must be an object",
                    'http_code' => 400
                ];
            }

            if (!array_key_exists('question_index', $ans) || !is_int($ans['question_index'])) {
                return [
                    'valid' => false,
                    'error' => "Submitted answer at entry {$entryIdx} has invalid or missing question_index",
                    'http_code' => 400
                ];
            }

            $qIdx = $ans['question_index'];
            if ($qIdx < 0 || $qIdx >= $totalQuestions) {
                return [
                    'valid' => false,
                    'error' => "Submitted question_index {$qIdx} is out of bounds (0 to " . ($totalQuestions - 1) . ")",
                    'http_code' => 400
                ];
            }

            if (isset($seenIndexes[$qIdx])) {
                return [
                    'valid' => false,
                    'error' => "Duplicate answer submitted for question_index {$qIdx}",
                    'http_code' => 400
                ];
            }
            $seenIndexes[$qIdx] = true;

            if (!array_key_exists('selected', $ans)) {
                return [
                    'valid' => false,
                    'error' => "Submitted answer for question_index {$qIdx} is missing 'selected' field",
                    'http_code' => 400
                ];
            }

            $selected = $ans['selected'];
            if ($selected !== null) {
                if (!is_int($selected)) {
                    return [
                        'valid' => false,
                        'error' => "Selected option for question_index {$qIdx} must be an integer or null",
                        'http_code' => 400
                    ];
                }

                $optionCount = count($storedQuestions[$qIdx]['options']);
                if ($selected < 0 || $selected >= $optionCount) {
                    return [
                        'valid' => false,
                        'error' => "Selected option {$selected} for question_index {$qIdx} is out of range",
                        'http_code' => 400
                    ];
                }
            }

            $timedOut = false;
            if (array_key_exists('timed_out', $ans)) {
                if (!is_bool($ans['timed_out'])) {
                    return [
                        'valid' => false,
                        'error' => "Field 'timed_out' for question_index {$qIdx} must be a boolean",
                        'http_code' => 400
                    ];
                }
                $timedOut = $ans['timed_out'];
            }

            $submittedMap[$qIdx] = [
                'selected' => $selected,
                'timed_out' => $timedOut
            ];
        }

        // 3. Authoritative Scoring & Canonical Structure
        $correctCount = 0;
        $canonicalAnswers = [];
        $answerRows = [];

        for ($i = 0; $i < $totalQuestions; $i++) {
            $expectedCorrect = $storedQuestions[$i]['correct_index'];

            if (isset($submittedMap[$i])) {
                $sel = $submittedMap[$i]['selected'];
                $timedOut = $submittedMap[$i]['timed_out'];
            } else {
                $sel = null;
                $timedOut = false;
            }

            $isCorrect = (!$timedOut && $sel !== null && $sel === $expectedCorrect);
            if ($isCorrect) {
                $correctCount++;
            }

            $canonicalAnswers[] = [
                'question_index' => $i,
                'selected' => $sel,
                'correct' => $expectedCorrect,
                'timed_out' => $timedOut
            ];

            $answerRows[] = [
                'question_index' => $i,
                'selected_option' => ($sel !== null) ? (string)$sel : null,
                'correct_option' => (string)$expectedCorrect,
                'timed_out' => $timedOut ? 1 : 0
            ];
        }

        $incorrectCount = $totalQuestions - $correctCount;
        $scorePercentage = (int) round(($correctCount / $totalQuestions) * 100);
        $passed = ($scorePercentage >= 70);

        return [
            'valid' => true,
            'error' => null,
            'http_code' => 200,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'score_percentage' => $scorePercentage,
            'passed' => $passed,
            'canonical_answers' => $canonicalAnswers,
            'answer_rows' => $answerRows
        ];
    }
}
