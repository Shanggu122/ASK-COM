<?php

namespace App\Services;

use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Illuminate\Support\Facades\Log;

class DialogflowService
{
    private ?string $projectId;
    private string $languageCode;
    private ?string $credentialsPath;

    public function __construct()
    {
        $config = (array) config("services.dialogflow");

        $this->projectId = $config["project_id"] ?? env("DIALOGFLOW_PROJECT_ID");
        $this->languageCode = $config["language"] ?? env("DIALOGFLOW_LANGUAGE", "en-US");
        $this->credentialsPath = $this->resolveCredentialsPath(
            $config["credentials_path"] ?? env("DIALOGFLOW_CREDENTIALS"),
            $config["key_b64"] ?? null,
            $config["json_key"] ?? null,
        );
    }

    public function detectIntent(string $text, string $sessionId): string
    {
        if (empty($this->projectId)) {
            throw new \RuntimeException("Dialogflow project id is not configured.");
        }

        if (empty($this->credentialsPath) || !is_readable($this->credentialsPath)) {
            throw new \RuntimeException(
                "Dialogflow credentials file is missing: " . (string) $this->credentialsPath,
            );
        }

        $client = null;

        try {
            $client = new SessionsClient($this->clientOptions());
            $session = $client->sessionName($this->projectId, $sessionId);

            $textInput = new TextInput();
            $textInput->setText($text);
            $textInput->setLanguageCode($this->languageCode);

            $queryInput = new QueryInput();
            $queryInput->setText($textInput);

            $response = $client->detectIntent($session, $queryInput);
            $fulfillment = trim((string) $response->getQueryResult()->getFulfillmentText());

            if ($fulfillment === "") {
                Log::warning("Dialogflow returned an empty fulfillment text.");
                return "Sorry, I could not find an answer right now.";
            }

            return $fulfillment;
        } catch (\Throwable $e) {
            Log::error("Dialogflow detectIntent failed", [
                "error" => $e->getMessage(),
            ]);

            throw new \RuntimeException("Dialogflow request failed.", 0, $e);
        } finally {
            if ($client !== null) {
                try {
                    $client->close();
                } catch (\Throwable $closeError) {
                    Log::warning("Dialogflow client close failed", [
                        "error" => $closeError->getMessage(),
                    ]);
                }
            }
        }
    }

    private function clientOptions(): array
    {
        $options = [
            "credentials" => $this->credentialsPath,
        ];

        $transport = config("services.dialogflow.transport") ?? env("DIALOGFLOW_TRANSPORT", "rest");
        if ($transport === "rest") {
            $options["transport"] = "rest";
        }

        return $options;
    }

    private function resolveCredentialsPath(
        ?string $configuredPath,
        ?string $encodedKey,
        ?string $jsonKey,
    ): ?string {
        if (!empty($configuredPath)) {
            $absolutePath = $this->normalizePath($configuredPath);
            if ($absolutePath !== null && file_exists($absolutePath)) {
                return $absolutePath;
            }
        }

        if (!empty($jsonKey)) {
            $persisted = $this->persistJsonCredentials($jsonKey);
            if ($persisted !== null) {
                return $persisted;
            }
        }

        $encoded = $encodedKey ?? env("DIALOGFLOW_KEY_B64");
        if (!empty($encoded)) {
            $decoded = base64_decode($encoded, true);
            if ($decoded === false) {
                Log::error("Dialogflow: failed to decode base64 credentials.");
            } else {
                $persisted = $this->persistJsonCredentials($decoded);
                if ($persisted !== null) {
                    return $persisted;
                }
            }
        }

        $defaultPath = storage_path("app/ascc-itbot-dpkw-4d5a4dbcae6c.json");
        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        return null;
    }

    private function persistJsonCredentials(string $jsonPayload): ?string
    {
        $trimmed = trim($jsonPayload);
        if ($trimmed === "") {
            return null;
        }

        json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Dialogflow: provided JSON credentials are invalid.", [
                "error" => json_last_error_msg(),
            ]);
            return null;
        }

        $target = storage_path("app/dialogflow-runtime-key.json");
        if (file_put_contents($target, $trimmed) === false) {
            Log::error("Dialogflow: unable to persist JSON credentials to file.");
            return null;
        }

        @chmod($target, 0600);

        return $target;
    }

    private function normalizePath(string $path): ?string
    {
        $trimmed = trim($path);
        if ($trimmed === "") {
            return null;
        }

        if ($trimmed[0] === "~") {
            $home = rtrim((string) getenv("HOME"), DIRECTORY_SEPARATOR);
            if ($home !== "") {
                return $home .
                    DIRECTORY_SEPARATOR .
                    ltrim(substr($trimmed, 1), DIRECTORY_SEPARATOR);
            }
        }

        if (
            !str_starts_with($trimmed, DIRECTORY_SEPARATOR) &&
            !preg_match("/^[A-Za-z]:\\\\/", $trimmed)
        ) {
            return base_path($trimmed);
        }

        return $trimmed;
    }
}
