<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    "postmark" => [
        "token" => env("POSTMARK_TOKEN"),
    ],
    // Agora service options
    "agora" => [
        // default token lifetime in seconds for calls
        "ttl_seconds" => env("AGORA_TTL_SECONDS", 86400),
    ],

    "ses" => [
        "key" => env("AWS_ACCESS_KEY_ID"),
        "secret" => env("AWS_SECRET_ACCESS_KEY"),
        "region" => env("AWS_DEFAULT_REGION", "us-east-1"),
    ],

    "resend" => [
        "key" => env("RESEND_KEY"),
    ],

    "slack" => [
        "notifications" => [
            "bot_user_oauth_token" => env("SLACK_BOT_USER_OAUTH_TOKEN"),
            "channel" => env("SLACK_BOT_USER_DEFAULT_CHANNEL"),
        ],
    ],

    "openai_moderation" => [
        "enabled" => env("OPENAI_MODERATION_ENABLED", false),
        "key" => env("OPENAI_MODERATION_KEY", env("OPENAI_API_KEY")),
        "base_uri" => env("OPENAI_MODERATION_BASE_URI", "https://api.openai.com"),
        "model" => env("OPENAI_MODERATION_MODEL", "omni-moderation-latest"),
        "timeout" => env("OPENAI_MODERATION_TIMEOUT", 5),
        "flag_threshold" => env("OPENAI_MODERATION_FLAG_THRESHOLD", 0.5),
    ],

    "out_of_scope" => [
        "enabled" => env("OPENAI_OUT_OF_SCOPE_ENABLED", false),
        "key" => env("OPENAI_OUT_OF_SCOPE_KEY", env("OPENAI_API_KEY")),
        "base_uri" => env("OPENAI_OUT_OF_SCOPE_BASE_URI", "https://api.openai.com"),
        "model" => env("OPENAI_OUT_OF_SCOPE_MODEL", "gpt-4.1-mini"),
        "timeout" => env("OPENAI_OUT_OF_SCOPE_TIMEOUT", 5),
        "confidence_threshold" => env("OPENAI_OUT_OF_SCOPE_CONFIDENCE_THRESHOLD", 0.6),
    ],

    "openai_intents" => [
        "enabled" => env("OPENAI_INTENT_ENABLED", false),
        "key" => env("OPENAI_INTENT_KEY", env("OPENAI_API_KEY")),
        "base_uri" => env("OPENAI_INTENT_BASE_URI", "https://api.openai.com"),
        "model" => env("OPENAI_INTENT_MODEL", "gpt-4.1-mini"),
        "timeout" => env("OPENAI_INTENT_TIMEOUT", 5),
        "confidence_threshold" => env("OPENAI_INTENT_CONFIDENCE_THRESHOLD", 0.6),
    ],

    "dialogflow" => [
        "project_id" => env("DIALOGFLOW_PROJECT_ID"),
        "language" => env("DIALOGFLOW_LANGUAGE", "en-US"),
        "credentials_path" => env("DIALOGFLOW_CREDENTIALS"),
        "transport" => env("DIALOGFLOW_TRANSPORT", "rest"),
        "key_b64" => env("DIALOGFLOW_KEY_B64"),
        "json_key" => env("DIALOGFLOW_KEY_JSON", env("GCP_CREDS_JSON")),
    ],
];
