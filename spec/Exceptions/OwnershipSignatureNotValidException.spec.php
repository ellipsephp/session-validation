<?php

use Ellipse\Session\Exceptions\OwnershipSignatureNotValidException;

describe('OwnershipSignatureNotValidException', function () {

    beforeEach(function () {

        $this->exception = new OwnershipSignatureNotValidException('signature');

    });

    it('should extend UnexpectedValueException', function () {

        expect($this->exception)->toBeAnInstanceOf(UnexpectedValueException::class);

    });

});
