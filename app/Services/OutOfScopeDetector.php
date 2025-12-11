<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutOfScopeDetector
{
    private bool $enabled;
    private ?string $apiKey;
    private string $baseUri;
    private string $model;
    private float $timeout;
    private float $confidenceThreshold;

    private array $systemKeywords = [
        "consult",
        "consultation",
        "booking",
        "schedule",
        "sched",
        "sked",
        "professor",
        "prof ",
        "subject",
        "slot",
        "availability",
        "student",
        "status",
        "department",
        "office hour",
        "office-hour",
        "faculty",
        "calendar",
        "term",
        "semester",
        "academic",
        "resched",
        "cancel",
        "message",
        "chat",
        "class",
        "course",
        "dashboard",
        "appointment",
    ];

    private array $smallTalkKeywords = [
        "pogi",
        "gwapo",
        "ganda",
        "maganda",
        "pangit",
        "mabaho",
        "bango",
        "cute",
        "handsome",
        "pretty",
        "ugly",
        "look good",
        "look bad",
        "feel good",
        "feel bad",
    ];

    private array $questionPatterns = [
        "/\\bwho\\b/u",
        "/\\bwhat\\b/u",
        "/\\bwhen\\b/u",
        "/\\bwhere\\b/u",
        "/\\bwhy\\b/u",
        "/\\bhow\\b/u",
        "/\\bcan\\b/u",
        "/\\bwill\\b/u",
        "/\\bwould\\b/u",
        "/\\bdo\\b/u",
        "/\\bdoes\\b/u",
        "/\\bam\\b/u",
        "/\\bare\\b/u",
        "/\\bak\\s*o\\s*ba\\b/u",
        "/\\bpwede\\b/u",
        "/\\bpuwede\\b/u",
        "/\\bbakit\\b/u",
        "/\\bpaano\\b/u",
    ];

    public function __construct()
    {
        $config = config("services.out_of_scope", []);
        $this->enabled = (bool) ($config["enabled"] ?? false);
        $this->apiKey = $config["key"] ?? env("OPENAI_API_KEY");
        $this->baseUri = rtrim($config["base_uri"] ?? "https://api.openai.com", "/");
        $this->model = $config["model"] ?? "gpt-4.1-mini";
        $this->timeout = (float) ($config["timeout"] ?? 5.0);
        $this->confidenceThreshold = (float) ($config["confidence_threshold"] ?? 0.6);
    }

    public function isOutOfScope(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        if ($normalized === "") {
            return false;
        }

        $aiDecision = $this->analyzeWithOpenAi($normalized);
        if ($aiDecision !== null) {
            return $aiDecision;
        }

        return $this->fallbackHeuristic($normalized);
    }

    private function analyzeWithOpenAi(string $normalized): ?bool
    {
        if (!$this->enabled || empty($this->apiKey)) {
            return null;
        }

        $endpoint = $this->baseUri . "/v1/chat/completions";
        $payload = [
            "model" => $this->model,
            "messages" => [
                [
                    "role" => "system",
                    "content" =>
                        "You are a classifier for an academic consultation chatbot. Decide if a user message is IN_SCOPE (related to academic consultation schedules, bookings, professors, consultation policies, student status, or related ASCC system tasks) or OUT_OF_SCOPE (anything else, including personal small talk). Reply with only IN_SCOPE or OUT_OF_SCOPE. Also provide a numeric confidence between 0 and 1 in the format CONFIDENCE:0.0.",
                ],
                [
                    "role" => "user",
                    "content" => $normalized,
                ],
            ],
            "max_tokens" => 20,
            "temperature" => 0.0,
        ];

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $this->apiKey,
            ])
                ->timeout($this->timeout)
                ->post($endpoint, $payload);

            if (!$response->successful()) {
                Log::warning("OutOfScopeDetector: classification request failed", [
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            if (!is_array($data) || empty($data["choices"][0]["message"]["content"])) {
                return null;
            }

            $content = strtoupper((string) $data["choices"][0]["message"]["content"]);
            $confidence = $this->parseConfidence($content);

            if (str_contains($content, "OUT_OF_SCOPE")) {
                if ($confidence === null || $confidence >= $this->confidenceThreshold) {
                    return true;
                }

                return null; // low confidence, fall back to heuristics
            }

            if (str_contains($content, "IN_SCOPE")) {
                if ($confidence === null || $confidence >= $this->confidenceThreshold) {
                    return false;
                }

                return null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("OutOfScopeDetector: classification request error", [
                "exception" => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function parseConfidence(string $content): ?float
    {
        if (preg_match("/CONFIDENCE\s*:\s*(0?\.\d+|1(?:\.0+)?)/", $content, $matches) === 1) {
            return (float) $matches[1];
        }

        return null;
    }

    private function fallbackHeuristic(string $normalized): bool
    {
        if ($this->containsAny($normalized, $this->systemKeywords)) {
            return false;
        }

        if (str_contains($normalized, "?")) {
            return true;
        }

        foreach ($this->questionPatterns as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return $this->containsAny($normalized, $this->smallTalkKeywords);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle === "") {
                continue;
            }

            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
