<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\EWallet\Exceptions\DuplicateTransactionException;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Exceptions\WalletNotFoundException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExceptionTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_insufficient_balance_exception_extends_exception(): void
    {
        $exception = new InsufficientBalanceException(5000, 2000);

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function test_insufficient_balance_exception_has_message(): void
    {
        $exception = new InsufficientBalanceException(5000, 2000);

        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_insufficient_balance_exception_can_be_thrown(): void
    {
        $this->expectException(InsufficientBalanceException::class);

        throw new InsufficientBalanceException(10000, 5000);
    }

    public function test_insufficient_balance_exception_with_zero_balance(): void
    {
        $exception = new InsufficientBalanceException(5000, 0);

        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_duplicate_transaction_exception_extends_exception(): void
    {
        $exception = new DuplicateTransactionException('test_key_123');

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function test_duplicate_transaction_exception_has_message(): void
    {
        $exception = new DuplicateTransactionException('test_key_123');

        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_duplicate_transaction_exception_can_be_thrown(): void
    {
        $this->expectException(DuplicateTransactionException::class);

        throw new DuplicateTransactionException('payment_order_123');
    }

    public function test_wallet_not_found_exception_extends_exception(): void
    {
        $exception = new WalletNotFoundException(123);

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function test_wallet_not_found_exception_has_message(): void
    {
        $exception = new WalletNotFoundException(456);

        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_wallet_not_found_exception_can_be_thrown(): void
    {
        $this->expectException(WalletNotFoundException::class);

        throw new WalletNotFoundException(789);
    }

    public function test_wallet_not_found_exception_with_different_ids(): void
    {
        $exception1 = new WalletNotFoundException(1);
        $exception2 = new WalletNotFoundException(999);

        $this->assertNotEmpty($exception1->getMessage());
        $this->assertNotEmpty($exception2->getMessage());
    }

    public function test_all_exceptions_are_catchable(): void
    {
        $caught = 0;

        try {
            throw new InsufficientBalanceException(1000, 500);
        } catch (InsufficientBalanceException $e) {
            $caught++;
        }

        try {
            throw new DuplicateTransactionException('key');
        } catch (DuplicateTransactionException $e) {
            $caught++;
        }

        try {
            throw new WalletNotFoundException(1);
        } catch (WalletNotFoundException $e) {
            $caught++;
        }

        $this->assertEquals(3, $caught);
    }

    public function test_exceptions_can_be_caught_as_generic_exception(): void
    {
        $caught = 0;

        try {
            throw new InsufficientBalanceException(1000, 500);
        } catch (Exception $e) {
            $caught++;
        }

        try {
            throw new DuplicateTransactionException('key');
        } catch (Exception $e) {
            $caught++;
        }

        try {
            throw new WalletNotFoundException(1);
        } catch (Exception $e) {
            $caught++;
        }

        $this->assertEquals(3, $caught);
    }
}
