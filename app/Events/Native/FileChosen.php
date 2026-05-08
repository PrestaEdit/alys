<?php

namespace App\Events\Native;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileChosen
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $filename,
        public string $content,
    ) {}
}
