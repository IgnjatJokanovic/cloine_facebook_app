<?php

namespace App\Dto;

class MessageDto{

    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string|null $profile,
        public string $body,
        public string $created_at,
        public bool $opened,
    )
    {
        //
    }
}
