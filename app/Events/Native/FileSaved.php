<?php

namespace App\Events\Native;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileSaved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public bool $success,
        public string $error = '',
    ) {}
}
