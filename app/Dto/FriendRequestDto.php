<?php

namespace App\Dto;

class FriendRequestDto{

    public function __construct(
        public int $id,
        public int $to,
        public string $firstName,
        public string $lastName,
        public mixed $profile_photo,
        public bool $opened,
        public bool $accepted,
    )
    {
        //
    }
}
