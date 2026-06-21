<?php

namespace Tests\Unit\Contracts\StateMachine;

use App\Contracts\StateMachine\TransitionResult;
use PHPUnit\Framework\TestCase;

class TransitionResultTest extends TestCase
{
    public function test_success_result(): void
    {
        $result = TransitionResult::success('Operation successful');

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isInvalid());
        $this->assertSame('Operation successful', $result->message);
        $this->assertEmpty($result->errors);
    }

    public function test_success_result_without_message(): void
    {
        $result = TransitionResult::success();

        $this->assertTrue($result->isValid());
        $this->assertSame('', $result->message);
    }

    public function test_failure_result(): void
    {
        $errors = ['key' => 'value', 'foo' => 'bar'];
        $result = TransitionResult::failure('Something went wrong', $errors);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());
        $this->assertSame('Something went wrong', $result->message);
        $this->assertSame($errors, $result->errors);
    }

    public function test_failure_result_without_errors(): void
    {
        $result = TransitionResult::failure('Something went wrong');

        $this->assertFalse($result->isValid());
        $this->assertSame('Something went wrong', $result->message);
        $this->assertEmpty($result->errors);
    }

    public function test_constructor_values(): void
    {
        $result = new TransitionResult(true, 'test', ['a' => 1]);

        $this->assertTrue($result->valid);
        $this->assertSame('test', $result->message);
        $this->assertSame(['a' => 1], $result->errors);
    }

    public function test_readonly_properties(): void
    {
        $result = TransitionResult::success('test');

        $this->expectException(\Error::class);

        $result->valid = false;
    }
}
