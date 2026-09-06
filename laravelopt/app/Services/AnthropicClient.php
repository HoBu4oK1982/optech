<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Тонкий клиент Messages API Anthropic поверх Http-фасада Laravel.
 *
 * SDK намеренно не используется: одна ручка (POST /v1/messages), и тянуть ради
 * неё зависимость в проект незачем.
 *
 * Берёт на себя три вещи, без которых запросы к API рвутся на реальных данных:
 *   1) повтор запроса при 429/5xx и сетевых сбоях, с уважением к Retry-After;
 *   2) продолжение хода при stop_reason = pause_turn — так API отдаёт паузу,
 *      когда серверный веб-поиск отработал дольше одного ответа;
 *   3) распознавание stop_reason = refusal, чтобы отказ не выглядел как
 *      «модель вернула пустой текст».
 */
class AnthropicClient
{
    /** Сколько раз повторять один HTTP-запрос при временной ошибке. */
    public const MAX_ATTEMPTS = 3;

    /** Ограничение на число продолжений после pause_turn. */
    private const MAX_PAUSE_CONTINUATIONS = 4;

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $endpoint,
        private string $version,
        private int $timeout,
        private ?Closure $logger = null,
    ) {
    }

    public static function fromConfig(?Closure $logger = null): self
    {
        $key = (string) config('services.anthropic.key');

        if ($key === '') {
            throw new RuntimeException(
                'Не задан ANTHROPIC_API_KEY. Добавьте ключ в .env и выполните `php artisan config:clear`.'
            );
        }

        return new self(
            $key,
            (string) config('services.anthropic.model'),
            (string) config('services.anthropic.endpoint'),
            (string) config('services.anthropic.version'),
            (int) config('services.anthropic.timeout'),
            $logger,
        );
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Один законченный ответ модели.
     *
     * @param  array<int,array<string,mixed>>  $tools  серверные инструменты (веб-поиск и т.п.)
     * @return array{text:string, usage:array<string,int>, searches:int, stop_reason:?string}
     */
    public function complete(string $system, string $prompt, array $tools = [], int $maxTokens = 16000): array
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $usage = ['input_tokens' => 0, 'output_tokens' => 0];
        $searches = 0;
        $text = '';
        $stopReason = null;

        for ($round = 0; $round <= self::MAX_PAUSE_CONTINUATIONS; $round++) {
            $payload = [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => $messages,
                // Adaptive thinking: модель сама решает, сколько рассуждать.
                // Фиксированный budget_tokens на моделях 4.6+ уже не применяется.
                'thinking' => ['type' => 'adaptive'],
            ];

            if ($tools) {
                $payload['tools'] = $tools;
            }

            $body = $this->post($payload);

            $usage['input_tokens'] += (int) data_get($body, 'usage.input_tokens', 0);
            $usage['output_tokens'] += (int) data_get($body, 'usage.output_tokens', 0);

            $content = (array) data_get($body, 'content', []);
            $stopReason = data_get($body, 'stop_reason');

            foreach ($content as $block) {
                if (($block['type'] ?? null) === 'text') {
                    $text .= $block['text'] ?? '';
                }
                if (($block['type'] ?? null) === 'server_tool_use') {
                    $searches++;
                }
            }

            // Отказ модели приходит с HTTP 200 — без этой проверки он выглядел
            // бы просто как пустой ответ.
            if ($stopReason === 'refusal') {
                throw new RuntimeException(
                    'Модель отклонила запрос (stop_reason: refusal): '
                    . (string) data_get($body, 'stop_details.explanation', 'без пояснения')
                );
            }

            if ($stopReason !== 'pause_turn') {
                break;
            }

            // pause_turn: возвращаем ответ обратно как ход ассистента и просим
            // продолжить с того же места.
            $messages[] = ['role' => 'assistant', 'content' => $content];
            $this->log('pause_turn: продолжаем ход, раунд ' . ($round + 1));
        }

        if ($stopReason === 'pause_turn') {
            throw new RuntimeException(
                'Модель так и не завершила ход за ' . self::MAX_PAUSE_CONTINUATIONS . ' продолжений.'
            );
        }

        if ($stopReason === 'max_tokens') {
            throw new RuntimeException('Ответ обрезан по max_tokens — увеличьте лимит.');
        }

        return [
            'text' => trim($text),
            'usage' => $usage,
            'searches' => $searches,
            'stop_reason' => $stopReason,
        ];
    }

    /**
     * POST с повторами. Повторяем только то, что имеет смысл повторять:
     * 408/409/429 и 5xx плюс сетевые ошибки. На 400/401/403 повтор ничего не
     * изменит — такие ошибки поднимаем сразу.
     *
     * @return array<string,mixed>
     */
    private function post(array $payload): array
    {
        $lastError = 'неизвестная ошибка';

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->version,
                    'content-type' => 'application/json',
                ])
                    ->timeout($this->timeout)
                    ->post($this->endpoint, $payload);
            } catch (Throwable $e) {
                $lastError = 'сетевая ошибка: ' . $e->getMessage();
                $this->log("Попытка {$attempt}/" . self::MAX_ATTEMPTS . " — {$lastError}");
                $this->backoff($attempt, null);
                continue;
            }

            if ($response->successful()) {
                return (array) $response->json();
            }

            $status = $response->status();
            $message = $this->errorMessage($response);

            if ($status < 500 && ! in_array($status, [408, 409, 429], true)) {
                throw new RuntimeException("Anthropic API вернул {$status}: {$message}");
            }

            $lastError = "HTTP {$status}: {$message}";
            $this->log("Попытка {$attempt}/" . self::MAX_ATTEMPTS . " — {$lastError}");
            $this->backoff($attempt, $response->header('retry-after'));
        }

        throw new RuntimeException(
            'Anthropic API не ответил за ' . self::MAX_ATTEMPTS . " попыток. Последняя ошибка — {$lastError}"
        );
    }

    private function backoff(int $attempt, ?string $retryAfter): void
    {
        if ($attempt >= self::MAX_ATTEMPTS) {
            return;
        }

        $seconds = is_numeric($retryAfter) ? (int) $retryAfter : (2 ** $attempt);
        sleep(max(1, min($seconds, 60)));
    }

    private function errorMessage(Response $response): string
    {
        $json = $response->json();

        return (string) (data_get($json, 'error.message') ?: mb_substr($response->body(), 0, 300));
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
    }
}
