<?php
// MockDataHelper.php - Offline Realistic Revision Data Provider
class MockDataHelper {
    public static function get($examName, $type, $diff) {
        if ($type === 'summary') {
            return json_encode([
                'type' => 'summary',
                'summary' => [
                    "Study Summary: {$examName} ({$diff} Level)",
                    "Overview: this guide outlines the critical domains required to pass the {$examName} exam.",
                    "Core Pillars: foundations, active practice, and compliance/security.",
                    "Key Takeaway: focus on relationships between components and practice scenario-based questions daily."
                ]
            ]);
        }
        if ($type === 'flashcards') {
            return json_encode([
                'type' => 'flashcards',
                'flashcards' => [
                    ['question' => "What is the primary objective of {$examName}?", 'answer' => "To establish standardized verification, operational security, and validation of core competency standards."],
                    ['question' => "Name a core component frequently tested in {$examName}.", 'answer' => "The centralized management framework, resource allocations, and validation pipelines."],
                    ['question' => "Define the recommended study strategy for {$diff} level.", 'answer' => "Review core architecture definitions, practice hands-on scenarios, and complete mock tests daily."],
                    ['question' => "What is a common pitfall to avoid during {$examName}?", 'answer' => "Rushing through scenario descriptions without evaluating all constraints and answer choices."]
                ]
            ]);
        }
        return json_encode([
            'type' => 'questions',
            'questions' => [
                [
                    'question' => "Which of the following describes the primary requirement of {$examName} at {$diff} difficulty?",
                    'options' => [
                        "A fully manual and unmonitored architecture model",
                        "A highly secure, automated, and standardized validation framework",
                        "A legacy database system without index configuration",
                        "An unencrypted document upload directory"
                    ],
                    'correct_index' => 1,
                    'explanation' => "Standard operational guidelines for {$examName} prioritize secure, automated, and structured setups to achieve efficiency."
                ],
                [
                    'question' => "Under {$diff} conditions, how should you respond to system anomalies?",
                    'options' => [
                        "Ignore them until the scheduled weekly maintenance window",
                        "Trigger automated diagnostic logging, load balancing, and admin alerts",
                        "Shut down all network traffic gateways without saving system states",
                        "Delete all system data files and database logs"
                    ],
                    'correct_index' => 1,
                    'explanation' => "Proactive alerting, diagnostic logging, and balanced traffic handling are critical to ensuring high availability."
                ]
            ]
        ]);
    }
}
