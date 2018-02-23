<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use TypeError;

class OwnershipSignatureTypeException extends TypeError implements SessionValidationExceptionInterface
{
    public function __construct($value)
    {
        $template = "Trying to use a value of type %s as session ownership signature - callable expected";

        $type = is_object($value) ? get_class($value) : gettype($value);

        $msg = sprintf($template, $type);

        parent::__construct($msg);
    }
}
