<?php
// MockDataHelper.php - Offline Realistic Revision Data Provider
class MockDataHelper {
    public static function get($examName, $type, $diff) {
        if ($type === 'summary') {
            return json_encode([
                'type' => 'summary',
                'summary' => [
                    [
                        'topic' => "Foundations of {$examName}",
                        'points' => [
                            "Core concepts: understand the primary terminology, architectural models, and core services tested in {$examName}.",
                            "Scope of evaluation: revision should prioritize the foundational domains and weighting criteria designated for the {$diff} level.",
                            "System principles: establish a clear understanding of standardized validation, security governance, and operational requirements.",
                            "Domain relationships: identify how individual functional components interact and depend upon one another across scenarios.",
                            "Study strategy: combine conceptual definitions with hands-on scenario analysis to build applied comprehension."
                        ]
                    ],
                    [
                        'topic' => "Key Practice Standards ({$diff} Level)",
                        'points' => [
                            "Requirement evaluation: carefully review scenario constraints, security baselines, and performance goals before selecting an approach.",
                            "Best practices: prioritize automated, structured, and auditable setups over ad-hoc manual procedures.",
                            "Anomaly response: diagnostic logging, monitoring telemetry, and automated alerts are critical components of operational stability.",
                            "Exam readiness: practice eliminating distractors by verifying technical prerequisites and constraint boundaries.",
                            "Revision review: revisit incorrect mock examination items daily to reinforce weak topic areas and concepts."
                        ]
                    ]
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
