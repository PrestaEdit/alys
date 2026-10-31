<?php

namespace App\Support;

/**
 * Détection de plateforme fiable dans l'app native.
 *
 * System::isIos()/isAndroid() reposent sur Device.GetInfo, qui n'est pas
 * enregistré sans le plugin nativephp/mobile-device : ils renvoient toujours
 * false. On se base donc sur l'OS du PHP embarqué (Darwin = iOS, Linux = Android).
 */
final class Platform
{
    public static function isNative(): bool
    {
        return function_exists('nativephp_call');
    }

    public static function isIos(): bool
    {
        return self::isNative() && PHP_OS_FAMILY === 'Darwin';
    }

    public static function isAndroid(): bool
    {
        return self::isNative() && PHP_OS_FAMILY === 'Linux';
    }
}
