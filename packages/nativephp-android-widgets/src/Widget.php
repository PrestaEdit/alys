<?php

namespace NativePhp\AndroidWidgets;

class Widget
{
    public static function update(string $title, string $content, string $badge = ''): void
    {
        if (function_exists('nativephp_call')) {
            nativephp_call('Widget.Update', json_encode([
                'title'   => $title,
                'content' => $content,
                'badge'   => $badge,
            ]));
        }
    }
}
