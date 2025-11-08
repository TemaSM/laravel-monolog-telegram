<?php

namespace TheCoder\MonologTelegram\Attributes;

interface TopicLogInterface
{
    public function getTopicId(array $topicsLevel): string|null;
}
