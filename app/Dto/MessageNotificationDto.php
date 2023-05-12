<?php

namespace App\Dto;

class MessageNotificationDto{

    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string|null $profile,
        public int $messageId,
        public int $from,
        public int $to,
        public string $body,
        public string $created_at,
        public bool $opened,
    )
    {
        //
    }
}
