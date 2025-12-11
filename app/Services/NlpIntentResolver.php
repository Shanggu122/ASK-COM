<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NlpIntentResolver
{
    private bool $enabled;
    private ?string $apiKey;
    private string $baseUri;
    private string $model;
    private float $timeout;
    private float $confidenceThreshold;

    public function __construct()
    {
        $config = config("services.openai_intents", []);
        $this->enabled = (bool) ($config["enabled"] ?? false);
        $this->apiKey = $config["key"] ?? env("OPENAI_API_KEY");
        $this->baseUri = rtrim($config["base_uri"] ?? "https://api.openai.com", "/");
        $this->model = $config["model"] ?? "gpt-4.1-mini";
        $this->timeout = (float) ($config["timeout"] ?? 5.0);
        $this->confidenceThreshold = (float) ($config["confidence_threshold"] ?? 0.6);
    }

    public function resolveStudent(string $message): ?array
    {
        return $this->resolve("student", $message);
    }

    public function resolveProfessor(string $message): ?array
    {
        return $this->resolve("professor", $message);
    }

    private function resolve(string $role, string $message): ?array
    {
        if (!$this->enabled || empty($this->apiKey)) {
            return null;
        }

        $payload = [
            "model" => $this->model,
            "messages" => [
                [
                    "role" => "system",
                    "content" => $this->buildSystemPrompt($role),
                ],
                [
                    "role" => "user",
                    "content" => trim($message),
                ],
            ],
            "temperature" => 0.0,
            "max_tokens" => 200,
        ];

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $this->apiKey,
            ])
                ->timeout($this->timeout)
                ->post($this->baseUri . "/v1/chat/completions", $payload);

            if (!$response->successful()) {
                Log::warning("NlpIntentResolver: classification request failed", [
                    "role" => $role,
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $content = $data["choices"][0]["message"]["content"] ?? null;
            if (!is_string($content) || trim($content) === "") {
                return null;
            }

            $decoded = $this->decodeJson($content);
            if (!is_array($decoded)) {
                return null;
            }

            $intentId = $decoded["intent_id"] ?? null;
            if (!is_string($intentId) || trim($intentId) === "") {
                return null;
            }
            $intentId = strtolower(trim($intentId));
            if ($intentId === "none") {
                return null;
            }

            $confidence = $decoded["confidence"] ?? null;
            if (!is_numeric($confidence)) {
                return null;
            }
            $confidence = (float) $confidence;
            if ($confidence < $this->confidenceThreshold) {
                return null;
            }

            $decoded["intent_id"] = $intentId;
            $decoded["confidence"] = $confidence;

            return $decoded;
        } catch (\Throwable $e) {
            Log::warning("NlpIntentResolver: classification request error", [
                "role" => $role,
                "exception" => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildSystemPrompt(string $role): string
    {
        if ($role === "student") {
            return <<<'PROMPT'
            You are an intent classifier for the ASCC academic consultation chatbot handling student questions.
            Respond with STRICT JSON (no commentary) matching this schema:
            {"intent_id":"...","confidence":0.0,"timeframe":null,"statuses":[],"professor":null,"subject":null,"date":null,"accepted_only":false}
            Supported intent_id values (case-insensitive):
            - student_consultation_summary
            - student_professors_for_subject
            - student_status_with_professor
            If no intent fits, respond with {"intent_id":"NONE","confidence":0}.
            Rules:
            - confidence must be between 0 and 1.
            - timeframe values: NEXT, TODAY, TOMORROW, THIS_WEEK, NEXT_WEEK, DATE, UNSPECIFIED.
            - statuses must be lower-case consultation status words like "pending", "approved", "accepted", "rescheduled", "completed".
            - When timeframe is DATE, include the ISO date (YYYY-MM-DD) in "date" when the message specifies a calendar day; otherwise use null.
            - Set accepted_only to true when the student clearly wants only accepted/approved consultations.
            - Preserve professor or subject names verbatim in their fields.
            PROMPT;
        }

        return <<<'PROMPT'
        You are an intent classifier for the ASCC academic consultation chatbot handling professor questions.
        Respond with STRICT JSON (no commentary) matching this schema:
        {"intent_id":"...","confidence":0.0,"timeframe":null,"date":null,"students_only":false,"metric":null,"boundary":null}
        Supported intent_id values (case-insensitive):
        - professor_consultation_summary
        - professor_available_slots
        - professor_schedule_summary
        - professor_subjects_summary
        - professor_completed_count
        - professor_semester_boundary
        If nothing fits, respond with {"intent_id":"NONE","confidence":0}.
        Rules:
        - confidence must be between 0 and 1.
        - timeframe values: TODAY, THIS_WEEK, NEXT_WEEK, DATE, MONTH, SEMESTER, UNSPECIFIED.
        - For professor_available_slots include the ISO date (YYYY-MM-DD) in "date" when known.
        - students_only true when explicitly asking for the list of students for a day.
        - For professor_completed_count set timeframe appropriately.
        - For professor_semester_boundary set boundary to START or END.
        PROMPT;
    }

    private function decodeJson(string $raw): mixed
    {
        $trimmed = trim($raw);
        if (str_starts_with($trimmed, "```")) {
            $trimmed = preg_replace('/^```[a-zA-Z]*\n?|\n?```$/', "", $trimmed);
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match("/\{.*\}/s", $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
