<?php

namespace App\Support;

class Flash
{
    public static function success(string $title, ?string $text = null): array
    {
        return [
            'type' => 'success',
            'title' => $title,
            'text' => $text,
        ];
    }

    public static function error(string $title, ?string $text = null): array
    {
        return [
            'type' => 'error',
            'title' => $title,
            'text' => $text,
        ];
    }

    public static function warning(string $title, ?string $text = null): array
    {
        return [
            'type' => 'warning',
            'title' => $title,
            'text' => $text,
        ];
    }

    public static function info(string $title, ?string $text = null): array
    {
        return [
            'type' => 'info',
            'title' => $title,
            'text' => $text,
        ];
    }
}
