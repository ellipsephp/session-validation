<?php

use Ellipse\Session\Exceptions\OwnershipSignatureTypeException;

describe('OwnershipSignatureTypeException', function () {

    beforeEach(function () {

        $this->exception = new OwnershipSignatureTypeException('signature');

    });

    it('should extend TypeError', function () {

        expect($this->exception)->toBeAnInstanceOf(TypeError::class);

    });

});
