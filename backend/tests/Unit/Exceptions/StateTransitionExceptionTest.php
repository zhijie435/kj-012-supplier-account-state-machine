<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\StateTransitionException;
use PHPUnit\Framework\TestCase;

class StateTransitionExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new StateTransitionException('test');

        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_default_values(): void
    {
        $exception = new StateTransitionException('test message');

        $this->assertSame(422, $exception->getHttpCode());
        $this->assertSame('STATE_TRANSITION_ERROR', $exception->getErrorCode());
        $this->assertSame('test message', $exception->getMessage());
    }

    public function test_custom_details(): void
    {
        $details = ['key' => 'value', 'foo' => 'bar'];
        $exception = new StateTransitionException('test', $details);

        $this->assertSame($details, $exception->getDetails());
    }

    public function test_invalid_type(): void
    {
        $exception = StateTransitionException::invalidType();

        $this->assertSame('无效的供应商账户状态类型', $exception->getMessage());
        $this->assertSame('invalid_type', $exception->getDetails()['error_type']);
        $this->assertSame(422, $exception->getHttpCode());
    }

    public function test_invalid_transition_without_allowed_states(): void
    {
        $exception = StateTransitionException::invalidTransition('待审核', '已激活');

        $this->assertSame('不允许从「待审核」变更为「已激活」', $exception->getMessage());
        $this->assertSame('invalid_transition', $exception->getDetails()['error_type']);
        $this->assertSame('待审核', $exception->getDetails()['from_state']);
        $this->assertSame('已激活', $exception->getDetails()['to_state']);
        $this->assertEmpty($exception->getDetails()['allowed_states']);
    }

    public function test_invalid_transition_with_allowed_states(): void
    {
        $exception = StateTransitionException::invalidTransition(
            '待审核',
            '已激活',
            ['审核中', '已拒绝', '已注销']
        );

        $this->assertSame(
            '不允许从「待审核」变更为「已激活」，允许的目标状态：审核中、已拒绝、已注销',
            $exception->getMessage()
        );
        $this->assertSame('invalid_transition', $exception->getDetails()['error_type']);
        $this->assertSame('待审核', $exception->getDetails()['from_state']);
        $this->assertSame('已激活', $exception->getDetails()['to_state']);
        $this->assertSame(['审核中', '已拒绝', '已注销'], $exception->getDetails()['allowed_states']);
    }

    public function test_terminal_state(): void
    {
        $exception = StateTransitionException::terminalState('已拒绝');

        $this->assertSame(
            '当前已处于终态（已拒绝），无法变更状态',
            $exception->getMessage()
        );
        $this->assertSame('terminal_state', $exception->getDetails()['error_type']);
        $this->assertSame('已拒绝', $exception->getDetails()['current_state']);
        $this->assertTrue($exception->getDetails()['is_terminal']);
    }

    public function test_permission_denied_with_permission(): void
    {
        $exception = StateTransitionException::permissionDenied(
            '待审核',
            '审核中',
            'supplier.approve'
        );

        $this->assertSame(
            '没有「supplier.approve」权限，无法从「待审核」变更为「审核中」',
            $exception->getMessage()
        );
        $this->assertSame('permission_denied', $exception->getDetails()['error_type']);
        $this->assertSame('待审核', $exception->getDetails()['from_state']);
        $this->assertSame('审核中', $exception->getDetails()['to_state']);
        $this->assertSame('supplier.approve', $exception->getDetails()['required_permission']);
    }

    public function test_permission_denied_without_permission(): void
    {
        $exception = StateTransitionException::permissionDenied('待审核', '审核中');

        $this->assertSame(
            '没有权限执行从「待审核」到「审核中」的状态变更',
            $exception->getMessage()
        );
        $this->assertSame('permission_denied', $exception->getDetails()['error_type']);
        $this->assertSame('待审核', $exception->getDetails()['from_state']);
        $this->assertSame('审核中', $exception->getDetails()['to_state']);
        $this->assertNull($exception->getDetails()['required_permission']);
    }

    public function test_business_rule_violation(): void
    {
        $exception = StateTransitionException::businessRuleViolation(
            '待审核',
            '已激活',
            '供应商激活前需上传营业执照',
            'empty_business_license'
        );

        $this->assertSame(
            '供应商激活前需上传营业执照',
            $exception->getMessage()
        );
        $this->assertSame('business_rule_violation', $exception->getDetails()['error_type']);
        $this->assertSame('待审核', $exception->getDetails()['from_state']);
        $this->assertSame('已激活', $exception->getDetails()['to_state']);
        $this->assertSame('empty_business_license', $exception->getDetails()['rule_code']);
    }

    public function test_business_rule_violation_without_rule_code(): void
    {
        $exception = StateTransitionException::businessRuleViolation(
            '待审核',
            '已激活',
            '供应商激活前需上传营业执照'
        );

        $this->assertSame(
            '供应商激活前需上传营业执照',
            $exception->getMessage()
        );
        $this->assertNull($exception->getDetails()['rule_code']);
    }

    public function test_remark_required(): void
    {
        $exception = StateTransitionException::remarkRequired('待审核', '已拒绝');

        $this->assertSame(
            '变更为「已拒绝」需要填写备注说明',
            $exception->getMessage()
        );
        $this->assertSame('remark_required', $exception->getDetails()['error_type']);
        $this->assertSame('待审核', $exception->getDetails()['from_state']);
        $this->assertSame('已拒绝', $exception->getDetails()['to_state']);
    }
}
