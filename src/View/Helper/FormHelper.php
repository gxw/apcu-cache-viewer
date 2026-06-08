<?php

declare(strict_types=1);

namespace App\View\Helper;

use App\Security\CsrfProtection;

class FormHelper
{
    public static function csrfField(): string
    {
        return CsrfProtection::getTokenField();
    }

    public static function method(string $method): string
    {
        $method = strtoupper($method);
        $validMethods = ['PUT', 'PATCH', 'DELETE'];
        
        if (in_array($method, $validMethods, true)) {
            return sprintf(
                '<input type="hidden" name="_method" value="%s">',
                htmlspecialchars($method, ENT_QUOTES, 'UTF-8')
            );
        }
        
        return '';
    }
}
