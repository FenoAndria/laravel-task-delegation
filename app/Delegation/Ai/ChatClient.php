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
        return filled(config('delego.ai.api_key')) && filled(config('delego.ai.model'));
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

        try {
            $response = Http::baseUrl((string) config('delego.ai.base_url'))
                ->withToken((string) config('delego.ai.api_key'))
                ->timeout((int) config('delego.ai.timeout'))
                ->acceptJson()
                ->post('/chat/completions', [
                    'model' => config('delego.ai.model'),
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object'],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new AssistantUnavailable('The AI assistant did not answer.', previous: $exception);
        }

        $content = (string) $response->json('choices.0.message.content', '');

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
