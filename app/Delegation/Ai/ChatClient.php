<?php

namespace App\Delegation\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal client for any OpenAI-compatible chat completions API.
 */
class ChatClient
{
    public static function isConfigured(): bool
    {
        return filled(config('delegation.ai.api_key')) && filled(config('delegation.ai.model'));
    }

    /**
     * Ask for a JSON object and return it decoded.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     *
     * @throws AssistantUnavailable
     */
    public function json(array $messages): array
    {
        if (! self::isConfigured()) {
            throw new AssistantUnavailable('The AI assistant is not configured.');
        }

        $failure = null;

        // Free models get saturated: on a rate limit or provider error, try the next one.
        foreach (self::models() as $model) {
            try {
                $response = Http::baseUrl((string) config('delegation.ai.base_url'))
                    ->withToken((string) config('delegation.ai.api_key'))
                    ->timeout((int) config('delegation.ai.timeout'))
                    ->acceptJson()
                    ->post('/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => 0.3,
                        'response_format' => ['type' => 'json_object'],
                    ])
                    ->throw();

                return $this->decode((string) $response->json('choices.0.message.content', ''));
            } catch (ConnectionException $exception) {
                $failure = $exception;
            } catch (RequestException $exception) {
                $failure = $exception;

                if ($exception->response->status() !== 429 && ! $exception->response->serverError()) {
                    break;
                }
            }
        }

        throw new AssistantUnavailable('The AI assistant did not answer.', previous: $failure);
    }

    /**
     * Models to try in order, from a comma-separated DELEGATION_AI_MODEL.
     *
     * @return list<string>
     */
    public static function models(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('delegation.ai.model')))));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AssistantUnavailable
     */
    private function decode(string $content): array
    {

        // Some models wrap JSON in prose or code fences: keep the outermost object.
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        $decoded = $start !== false && $end !== false ? json_decode(substr($content, $start, $end - $start + 1), true) : null;

        if (! is_array($decoded)) {
            throw new AssistantUnavailable('The AI assistant returned an unreadable answer.');
        }

        return $decoded;
    }
}
