<?php

namespace TheCoder\MonologTelegram;

use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class TelegramBotHandler extends AbstractProcessingHandler
{
    /**
     * text parameter in sendMessage method
     * @see https://core.telegram.org/bots/api#sendmessage
     */
    protected const TELEGRAM_MESSAGE_SIZE = 4096;

    /**
     * bot api url
     * @var string
     */
    protected string $botApi;

    /**
     * Telegram bot access token provided by BotFather.
     * Create telegram bot with https://telegram.me/BotFather and use access token from it.
     * @var string
     */
    protected string $token;

    /**
     * if telegram is blocked in your region you can use proxy
     * @var null
     */
    protected string|null $proxy;

    /**
     * Telegram channel name.
     * Since to start with '@' symbol as prefix.
     * @var string
     */
    protected string|int $chatId;

    /**
     * If chat groups are used instead of telegram channels,
     * and the ability to set topics on groups is enabled,
     * this configuration can be utilized.
     * @var string|null
     */
    protected string|int|null $topicId;

    protected string|null $queue = null;

    protected TopicDetector $topicDetector;

    /**
     * Timeout for Telegram API requests in seconds.
     *
     * @var int
     */
    protected int $timeout;

    /**
     * SSL verification enabled.
     *
     * @var bool
     */
    protected bool $verifySsl;

    /**
     * @param string $token Telegram bot access token provided by BotFather
     * @param string $channel Telegram channel name
     * @inheritDoc
     */
    public function __construct(
        string      $token,
        string|int  $chat_id,
        string|null $topic_id = null,
        string|null $queue = null,
        array       $topics_level = [],
                    $level = Logger::DEBUG,
        bool        $bubble = true,
        string      $bot_api = 'https://api.telegram.org/bot',
        string|null $proxy = null,
        int         $timeout = 5,
        bool        $verify_ssl = true
    )
    {
        if (empty($token) || !is_string($token)) {
            throw new \InvalidArgumentException('Bot token must be a non-empty string');
        }

        if (!is_string($chat_id) && !is_int($chat_id)) {
            throw new \InvalidArgumentException('Chat ID must be a string or integer');
        }

        if ($timeout < 1 || $timeout > 300) {
            throw new \InvalidArgumentException('Timeout must be between 1 and 300 seconds');
        }

        if ($proxy !== null && !is_string($proxy)) {
            throw new \InvalidArgumentException('Proxy must be a string or null');
        }

        parent::__construct($level, $bubble);

        $this->token = $token;
        $this->botApi = $bot_api;
        $this->chatId = $chat_id;
        $this->topicId = $topic_id;
        $this->queue = $queue;
        $this->level = $level;
        $this->bubble = $bubble;
        $this->proxy = $proxy;
        $this->timeout = $timeout;
        $this->verifySsl = $verify_ssl;

        $this->topicDetector = new TopicDetector($topics_level);
    }

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    protected function write($record): void
    {
        $topicId = $this->topicDetector->getTopicByAttribute($record);

        $token = $record['context']['token'] ?? null;
        $chatId = $record['context']['chat_id'] ?? null;
        $topicId = $topicId ?? $record['context']['topic_id'] ?? null;

        $this->send($record['formatted'], $token, $chatId, $topicId);
    }

    private function truncateTextToTelegramLimit(string $textMessage): string
    {
        if (mb_strlen($textMessage) <= self::TELEGRAM_MESSAGE_SIZE) {
            return $textMessage;
        }

        return mb_substr($textMessage, 0, self::TELEGRAM_MESSAGE_SIZE, 'UTF-8');
    }

    /**
     * Send request to @link https://api.telegram.org/bot on SendMessage action.
     * @param string $message
     * @param null $token
     * @param null $chatId
     * @param null $topicId
     * @throws GuzzleException
     */
    protected function send(string $message, $token = null, $chatId = null, $topicId = null): void
    {
        $token = $token ?? $this->token;
        $chatId = $chatId ?? $this->chatId;
        $topicId = $topicId ?? $this->topicId;

        $url = !str_contains($this->botApi, 'https://api.telegram.org')
            ? $this->botApi
            : $this->botApi . $token . '/SendMessage';

        $message = $this->truncateTextToTelegramLimit($message);

        if (empty($this->queue)) {
            dispatch_sync(new SendJob($url, $message, $chatId, $topicId, $this->proxy, $this->timeout, $this->verifySsl));
        } else {
            dispatch(new SendJob($url, $message, $chatId, $topicId, $this->proxy, $this->timeout, $this->verifySsl))->onQueue($this->queue);
        }
    }
}
