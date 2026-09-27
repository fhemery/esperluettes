<?php

namespace App\Domains\Config\Public\Exceptions;

use LogicException;

/**
 * Thrown when code checks a feature toggle that no service provider declared.
 */
class UndeclaredFeatureToggleException extends LogicException
{
    public function __construct(string $domain, string $name)
    {
        parent::__construct("Feature toggle {$domain}/{$name} is not declared.");
    }
}
