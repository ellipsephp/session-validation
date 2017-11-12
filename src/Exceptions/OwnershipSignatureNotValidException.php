<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use UnexpectedValueException;

class OwnershipSignatureNotValidException extends UnexpectedValueException
{
    public function __construct($signature)
    {
        $msg = "The value retuned by the signature callback must be an array.\n%s";

        parent::__construct(sprintf($msg, print_r($signature, true)));
    }
}
