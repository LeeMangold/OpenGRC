<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AiProvider: string implements HasLabel
{
    case OpenAI = 'openai';
    case DigitalOcean = 'digitalocean';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::DigitalOcean => 'DigitalOcean',
        };
    }

    public function getEndpoint(): string
    {
        return match ($this) {
            self::OpenAI => 'https://api.openai.com/v1/chat/completions',
            self::DigitalOcean => 'https://inference.do-ai.run/v1/chat/completions',
        };
    }

    /**
     * Get available models for this provider.
     *
     * @return array<string, string>
     */
    public function getModels(): array
    {
        return match ($this) {
            self::OpenAI => [
                'gpt-4.1-mini' => 'GPT-4.1 Mini',
                'gpt-4.1' => 'GPT-4.1',
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            self::DigitalOcean => [
                'alibaba-qwen3-32b' => 'Qwen3 32B',
                // 'alibaba-qwen3-235b' => 'Qwen3 235B',
                // 'anthropic-claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
                // 'meta-llama-3.3-70b-instruct' => 'Llama 3.3 70B Instruct',
            ],
        };
    }

    public function getDefaultModel(): string
    {
        return match ($this) {
            self::OpenAI => 'gpt-4.1-mini',
            self::DigitalOcean => 'alibaba-qwen3-32b',
        };
    }

    public function getEnvKeyName(): string
    {
        return match ($this) {
            self::OpenAI => 'OPENAI_API_KEY',
            self::DigitalOcean => 'DIGITALOCEAN_AI_KEY',
        };
    }

    public function getSettingKeyName(): string
    {
        return match ($this) {
            self::OpenAI => 'ai.openai_key',
            self::DigitalOcean => 'ai.digitalocean_key',
        };
    }
}
