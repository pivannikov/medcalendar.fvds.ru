<?php

declare(strict_types=1);

namespace App\Model;

class VariablesStore
{
    public function __construct(
        public readonly string $signup_token,
        public readonly string $google_tag,
        public readonly string $server_sender_email,
        public readonly string $system_email,
        public readonly string $admin_email,
    ) 
    {}
}
