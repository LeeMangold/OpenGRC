<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AI provider and model to use for AI-powered features.
    | The provider can be set to 'openai' or 'digitalocean'.
    |
    */

    'provider' => env('AI_PROVIDER', 'openai'),

    'model' => env('AI_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | API keys for each provider. These are used as fallbacks when the
    | database settings don't have a key configured.
    |
    */

    'keys' => [
        'openai' => env('OPENAI_API_KEY'),
        'digitalocean' => env('DIGITALOCEAN_AI_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota Settings
    |--------------------------------------------------------------------------
    |
    | Daily token limits for AI usage. Set to 0 to disable quotas.
    |
    */

    'quota' => [
        'prompt' => env('AI_PROMPT_QUOTA', 1000000),
        'response' => env('AI_RESPONSE_QUOTA', 1000000),
    ],
];
