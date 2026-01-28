<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class AiService
{
    protected Client $client;

    protected AiProvider $provider;

    protected string $model;

    protected string $apiKey;

    public function __construct()
    {
        $this->client = new Client;
        $this->initializeProvider();
    }

    protected function initializeProvider(): void
    {
        $this->provider = $this->resolveProvider();
        $this->model = $this->resolveModel();
        $this->apiKey = $this->resolveApiKey();
    }

    protected function resolveProvider(): AiProvider
    {
        $settingProvider = setting('ai.provider');
        if ($settingProvider) {
            return AiProvider::tryFrom($settingProvider) ?? AiProvider::OpenAI;
        }

        $configProvider = config('ai.provider');
        if ($configProvider) {
            return AiProvider::tryFrom($configProvider) ?? AiProvider::OpenAI;
        }

        return AiProvider::OpenAI;
    }

    protected function resolveModel(): string
    {
        $settingModel = setting('ai.model');
        if ($settingModel) {
            return $settingModel;
        }

        $configModel = config('ai.model');
        if ($configModel) {
            return $configModel;
        }

        return $this->provider->getDefaultModel();
    }

    protected function resolveApiKey(): string
    {
        $settingKey = setting($this->provider->getSettingKeyName());
        if (filled($settingKey)) {
            try {
                return Crypt::decryptString($settingKey);
            } catch (\Exception $e) {
                // Fall through to env key
            }
        }

        $envKey = config('ai.keys.'.$this->provider->value);
        if (filled($envKey)) {
            return $envKey;
        }

        return '';
    }

    public function getProvider(): AiProvider
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Send a chat completion request.
     *
     * @return array{content: string, model: string, usage: array}
     *
     * @throws RuntimeException
     * @throws GuzzleException
     */
    public function chatCompletion(string $systemPrompt, string $userPrompt): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AI provider is not configured. Please set an API key.');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
        ];

        if ($this->provider === AiProvider::OpenAI) {
            $payload['store'] = true;
        }

        $response = $this->client->request('POST', $this->provider->getEndpoint(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->apiKey}",
            ],
            'json' => $payload,
        ]);

        $body = $response->getBody();
        $data = json_decode($body, true);

        return [
            'content' => $data['choices'][0]['message']['content'],
            'model' => $data['model'] ?? $this->model,
            'usage' => $data['usage'] ?? [],
        ];
    }
}
