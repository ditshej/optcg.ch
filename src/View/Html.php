<?php

declare(strict_types=1);

namespace Optcg\View;

final class Html
{
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
