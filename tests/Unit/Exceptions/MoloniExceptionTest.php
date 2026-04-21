<?php

namespace Tomahock\Moloni\Tests\Unit\Exceptions;

use Tomahock\Moloni\Exceptions\MoloniAuthException;
use Tomahock\Moloni\Exceptions\MoloniException;
use Tomahock\Moloni\Tests\TestCase;

class MoloniExceptionTest extends TestCase
{
    public function test_moloni_exception_stores_message_and_code(): void
    {
        $e = new MoloniException('Something went wrong', 422);

        $this->assertSame('Something went wrong', $e->getMessage());
        $this->assertSame(422, $e->getCode());
        $this->assertSame([], $e->getErrors());
    }

    public function test_moloni_exception_stores_errors_array(): void
    {
        $errors = [
            ['code' => 'ERR001', 'message' => 'Field required'],
            ['code' => 'ERR002', 'message' => 'Invalid value'],
        ];

        $e = new MoloniException('Validation failed', 0, $errors);

        $this->assertSame($errors, $e->getErrors());
    }

    public function test_moloni_exception_stores_previous_throwable(): void
    {
        $previous = new \RuntimeException('Original');
        $e = new MoloniException('Wrapped', 0, [], $previous);

        $this->assertSame($previous, $e->getPrevious());
    }

    public function test_moloni_auth_exception_extends_moloni_exception(): void
    {
        $e = new MoloniAuthException('Auth failed', 401);

        $this->assertInstanceOf(MoloniException::class, $e);
        $this->assertSame('Auth failed', $e->getMessage());
        $this->assertSame(401, $e->getCode());
    }

    public function test_moloni_exception_defaults(): void
    {
        $e = new MoloniException();

        $this->assertSame('', $e->getMessage());
        $this->assertSame(0, $e->getCode());
        $this->assertSame([], $e->getErrors());
        $this->assertNull($e->getPrevious());
    }
}
