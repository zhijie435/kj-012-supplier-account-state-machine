<?php

namespace Tests\Unit\Services\StateMachine;

use App\Contracts\StateMachine\TransitionResult;
use App\Enums\SupplierAccountStatus;
use App\Exceptions\StateTransitionException;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierAccountStatusLog;
use App\Models\User;
use App\Services\StateMachine\SupplierAccountStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

enum FakeBackedEnum: string
{
    case TEST = 'test';
}

class SupplierAccountStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('supplier.approve', 'web');
        Permission::findOrCreate('supplier.view', 'web');

        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo(['supplier.approve', 'supplier.view']);

        $this->operator = User::factory()->create();
        $this->operator->assignRole('admin');
    }

    public function test_get_model(): void
    {
        $supplier = Supplier::factory()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->assertSame($supplier, $stateMachine->getModel());
    }

    public function test_current_state(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->assertSame(SupplierAccountStatus::PENDING, $stateMachine->currentState());
    }

    public function test_current_state_from_string(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'verifying']);
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->assertSame(SupplierAccountStatus::VERIFYING, $stateMachine->currentState());
    }

    public function test_can_transition_to_with_invalid_type(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $fakeEnum = FakeBackedEnum::TEST;

        $this->assertFalse($stateMachine->canTransitionTo($fakeEnum));
    }

    public function test_can_transition_to_valid(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->assertTrue($stateMachine->canTransitionTo(SupplierAccountStatus::VERIFYING));
    }

    public function test_can_transition_to_invalid(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->assertFalse($stateMachine->canTransitionTo(SupplierAccountStatus::ACTIVE));
    }

    public function test_validate_transition_with_invalid_type(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $fakeEnum = FakeBackedEnum::TEST;

        $result = $stateMachine->validateTransition($fakeEnum);

        $this->assertInstanceOf(TransitionResult::class, $result);
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());
        $this->assertSame('无效的供应商账户状态类型', $result->message);
        $this->assertTrue($result->errors['invalid_type']);
    }

    public function test_validate_transition_terminal_state_same_state(): void
    {
        $supplier = Supplier::factory()->rejected()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::REJECTED);

        $this->assertTrue($result->isValid());
        $this->assertSame('状态未变更', $result->message);
    }

    public function test_validate_transition_terminal_state_different_state(): void
    {
        $supplier = Supplier::factory()->rejected()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::ACTIVE);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('terminal_state', $result->errors);
        $this->assertTrue($result->errors['terminal_state']);
        $this->assertSame('rejected', $result->errors['current_state']);
    }

    public function test_validate_transition_same_state(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::PENDING);

        $this->assertTrue($result->isValid());
        $this->assertSame('状态未变更', $result->message);
    }

    public function test_validate_transition_invalid_transition(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::ACTIVE);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid_transition', $result->errors);
        $this->assertTrue($result->errors['invalid_transition']);
        $this->assertSame('pending', $result->errors['from_state']);
        $this->assertSame('active', $result->errors['to_state']);
        $this->assertNotEmpty($result->errors['allowed_states']);
    }

    public function test_validate_transition_remark_required_missing(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::REJECTED,
            ['operated_by' => $this->operator->id]
        );

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('remark_required', $result->errors);
        $this->assertTrue($result->errors['remark_required']);
    }

    public function test_validate_transition_remark_required_provided(): void
    {
        $supplier = Supplier::factory()->verifying()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::REJECTED,
            ['remark' => '资料不齐全', 'operated_by' => $this->operator->id]
        );

        $this->assertTrue($result->isValid());
    }

    public function test_validate_transition_missing_operator(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::VERIFYING, []);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('permission_denied', $result->errors);
        $this->assertTrue($result->errors['missing_operator']);
    }

    public function test_validate_transition_invalid_operator(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => 99999]
        );

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('permission_denied', $result->errors);
        $this->assertTrue($result->errors['invalid_operator']);
    }

    public function test_validate_transition_permission_denied(): void
    {
        $noPermUser = User::factory()->create();
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $noPermUser->id]
        );

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('permission_denied', $result->errors);
        $this->assertSame('supplier.approve', $result->errors['required_permission']);
    }

    public function test_validate_transition_business_rule_empty_license(): void
    {
        $supplier = Supplier::factory()->verifying()->withoutLicense()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id]
        );

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('business_rule_violation', $result->errors);
        $this->assertSame('empty_business_license', $result->errors['rule']);
        $this->assertSame('供应商激活前需上传营业执照', $result->message);
    }

    public function test_validate_transition_business_rule_empty_contact_info(): void
    {
        $supplier = Supplier::factory()->verifying()->withoutContactInfo()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id]
        );

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('business_rule_violation', $result->errors);
        $this->assertSame('empty_contact_info', $result->errors['rule']);
    }

    public function test_validate_transition_business_rule_has_orders(): void
    {
        $supplier = Supplier::factory()->active()->create();
        Order::factory()->create(['supplier_id' => $supplier->id]);

        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::CANCELLED,
            ['operated_by' => $this->operator->id]
        );

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('business_rule_violation', $result->errors);
        $this->assertSame('has_orders', $result->errors['rule']);
        $this->assertSame('供应商存在关联订单，无法注销', $result->message);
    }

    public function test_validate_transition_success(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $this->operator->id]
        );

        $this->assertTrue($result->isValid());
    }

    public function test_transition_to_with_invalid_type_throws_exception(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $fakeEnum = FakeBackedEnum::TEST;

        $this->expectException(StateTransitionException::class);

        $stateMachine->transitionTo($fakeEnum);
    }

    public function test_transition_to_invalid_transition_throws_exception(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->expectException(StateTransitionException::class);

        $stateMachine->transitionTo(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id]
        );
    }

    public function test_transition_to_terminal_state_throws_exception(): void
    {
        $supplier = Supplier::factory()->rejected()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->expectException(StateTransitionException::class);

        $stateMachine->transitionTo(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id]
        );
    }

    public function test_transition_to_same_state_returns_unchanged(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->transitionTo(
            SupplierAccountStatus::PENDING,
            ['operated_by' => $this->operator->id]
        );

        $this->assertSame($supplier, $result);
        $this->assertSame(SupplierAccountStatus::PENDING, $supplier->fresh()->status);
        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 1);
    }

    public function test_transition_pending_to_verifying(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->transitionTo(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $this->operator->id, 'remark' => '开始审核']
        );

        $freshSupplier = $supplier->fresh();
        $this->assertSame(SupplierAccountStatus::VERIFYING, $freshSupplier->status);
        $this->assertNotNull($freshSupplier->verifying_at);
        $this->assertNull($freshSupplier->activated_at);
        $this->assertSame($this->operator->id, $freshSupplier->operated_by);

        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 2);
        $log = SupplierAccountStatusLog::latest('id')->first();
        $this->assertSame($supplier->id, $log->supplier_id);
        $this->assertSame(SupplierAccountStatus::PENDING, $log->from_status);
        $this->assertSame(SupplierAccountStatus::VERIFYING, $log->to_status);
        $this->assertSame('开始审核', $log->remark);
        $this->assertSame($this->operator->id, $log->operated_by);
    }

    public function test_transition_verifying_to_active(): void
    {
        $supplier = Supplier::factory()->verifying()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $stateMachine->transitionTo(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id]
        );

        $freshSupplier = $supplier->fresh();
        $this->assertSame(SupplierAccountStatus::ACTIVE, $freshSupplier->status);
        $this->assertNotNull($freshSupplier->verifying_at);
        $this->assertNotNull($freshSupplier->activated_at);
        $this->assertNull($freshSupplier->suspended_at);

        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 2);
        $log = SupplierAccountStatusLog::latest('id')->first();
        $this->assertSame(SupplierAccountStatus::VERIFYING, $log->from_status);
        $this->assertSame(SupplierAccountStatus::ACTIVE, $log->to_status);
    }

    public function test_transition_verifying_to_rejected_with_remark(): void
    {
        $supplier = Supplier::factory()->verifying()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $stateMachine->transitionTo(
            SupplierAccountStatus::REJECTED,
            ['operated_by' => $this->operator->id, 'remark' => '资料不齐全']
        );

        $freshSupplier = $supplier->fresh();
        $this->assertSame(SupplierAccountStatus::REJECTED, $freshSupplier->status);
        $this->assertNotNull($freshSupplier->rejected_at);

        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 2);
        $log = SupplierAccountStatusLog::latest('id')->first();
        $this->assertSame(SupplierAccountStatus::VERIFYING, $log->from_status);
        $this->assertSame(SupplierAccountStatus::REJECTED, $log->to_status);
        $this->assertSame('资料不齐全', $log->remark);
    }

    public function test_transition_active_to_suspended(): void
    {
        $supplier = Supplier::factory()->active()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $stateMachine->transitionTo(
            SupplierAccountStatus::SUSPENDED,
            ['operated_by' => $this->operator->id, 'remark' => '违规操作']
        );

        $freshSupplier = $supplier->fresh();
        $this->assertSame(SupplierAccountStatus::SUSPENDED, $freshSupplier->status);
        $this->assertNotNull($freshSupplier->suspended_at);
    }

    public function test_transition_suspended_to_active(): void
    {
        $supplier = Supplier::factory()->suspended()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $stateMachine->transitionTo(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id, 'remark' => '申诉通过']
        );

        $freshSupplier = $supplier->fresh();
        $this->assertSame(SupplierAccountStatus::ACTIVE, $freshSupplier->status);
        $this->assertNotNull($freshSupplier->activated_at);
    }

    public function test_transition_remark_required_missing_throws_exception(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->expectException(StateTransitionException::class);

        $stateMachine->transitionTo(
            SupplierAccountStatus::REJECTED,
            ['operated_by' => $this->operator->id]
        );
    }

    public function test_transition_permission_denied_throws_exception(): void
    {
        $noPermUser = User::factory()->create();
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->expectException(StateTransitionException::class);

        $stateMachine->transitionTo(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $noPermUser->id]
        );
    }

    public function test_transition_business_rule_violation_throws_exception(): void
    {
        $supplier = Supplier::factory()->active()->create();
        Order::factory()->create(['supplier_id' => $supplier->id]);

        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->expectException(StateTransitionException::class);

        $stateMachine->transitionTo(
            SupplierAccountStatus::CANCELLED,
            ['operated_by' => $this->operator->id]
        );
    }

    public function test_transition_creates_log_entry(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $stateMachine->transitionTo(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $this->operator->id, 'remark' => '测试备注']
        );

        $this->assertDatabaseHas(SupplierAccountStatusLog::class, [
            'supplier_id' => $supplier->id,
            'from_status' => SupplierAccountStatus::PENDING->value,
            'to_status' => SupplierAccountStatus::VERIFYING->value,
            'remark' => '测试备注',
            'operated_by' => $this->operator->id,
        ]);
    }

    public function test_transition_uses_auth_id_when_no_operated_by_context(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $this->actingAs($this->operator);

        $stateMachine->transitionTo(
            SupplierAccountStatus::VERIFYING,
            ['remark' => '使用 Auth::id()']
        );

        $freshSupplier = $supplier->fresh();
        $this->assertSame($this->operator->id, $freshSupplier->operated_by);

        $this->assertDatabaseHas(SupplierAccountStatusLog::class, [
            'supplier_id' => $supplier->id,
            'operated_by' => $this->operator->id,
        ]);
    }

    public function test_full_workflow_pending_verifying_active_suspended_cancelled(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);
        $context = ['operated_by' => $this->operator->id];

        $stateMachine->transitionTo(SupplierAccountStatus::VERIFYING, $context);
        $this->assertSame(SupplierAccountStatus::VERIFYING, $supplier->fresh()->status);

        $stateMachine->transitionTo(SupplierAccountStatus::ACTIVE, $context);
        $this->assertSame(SupplierAccountStatus::ACTIVE, $supplier->fresh()->status);

        $stateMachine->transitionTo(SupplierAccountStatus::SUSPENDED, $context);
        $this->assertSame(SupplierAccountStatus::SUSPENDED, $supplier->fresh()->status);

        $stateMachine->transitionTo(SupplierAccountStatus::CANCELLED, $context);
        $this->assertSame(SupplierAccountStatus::CANCELLED, $supplier->fresh()->status);

        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 5);
    }

    public function test_full_workflow_pending_verifying_rejected(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);
        $context = ['operated_by' => $this->operator->id];

        $stateMachine->transitionTo(SupplierAccountStatus::VERIFYING, $context);
        $this->assertSame(SupplierAccountStatus::VERIFYING, $supplier->fresh()->status);

        $stateMachine->transitionTo(SupplierAccountStatus::REJECTED, [...$context, 'remark' => '不符合要求']);
        $this->assertSame(SupplierAccountStatus::REJECTED, $supplier->fresh()->status);

        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 3);
    }

    public function test_full_workflow_pending_verifying_pending(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);
        $context = ['operated_by' => $this->operator->id];

        $stateMachine->transitionTo(SupplierAccountStatus::VERIFYING, $context);
        $this->assertSame(SupplierAccountStatus::VERIFYING, $supplier->fresh()->status);

        $stateMachine->transitionTo(SupplierAccountStatus::PENDING, $context);
        $this->assertSame(SupplierAccountStatus::PENDING, $supplier->fresh()->status);

        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 3);
    }

    public function test_allowed_transitions(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $transitions = $stateMachine->allowedTransitions();

        $this->assertCount(3, $transitions);
        $this->assertContains(SupplierAccountStatus::VERIFYING, $transitions);
        $this->assertContains(SupplierAccountStatus::REJECTED, $transitions);
        $this->assertContains(SupplierAccountStatus::CANCELLED, $transitions);
    }

    public function test_transaction_rollback_on_database_error(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        try {
            \DB::beginTransaction();

            $stateMachine->transitionTo(
                SupplierAccountStatus::VERIFYING,
                ['operated_by' => $this->operator->id]
            );

            throw new \RuntimeException('Simulated DB error');
        } catch (\RuntimeException $e) {
            \DB::rollBack();
        }

        $this->assertSame(SupplierAccountStatus::PENDING, $supplier->fresh()->status);
        $this->assertDatabaseCount(SupplierAccountStatusLog::class, 1);
    }

    public function test_build_exception_from_validation_terminal_state(): void
    {
        $supplier = Supplier::factory()->rejected()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        try {
            $stateMachine->transitionTo(
                SupplierAccountStatus::ACTIVE,
                ['operated_by' => $this->operator->id]
            );
        } catch (StateTransitionException $e) {
            $this->assertSame(422, $e->getHttpCode());
            $this->assertSame('STATE_TRANSITION_ERROR', $e->getErrorCode());
            $this->assertSame('terminal_state', $e->getDetails()['error_type']);
            $this->assertTrue($e->getDetails()['is_terminal']);
        }
    }

    public function test_build_exception_from_validation_invalid_transition(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        $stateMachine = new SupplierAccountStateMachine($supplier);

        try {
            $stateMachine->transitionTo(
                SupplierAccountStatus::ACTIVE,
                ['operated_by' => $this->operator->id]
            );
        } catch (StateTransitionException $e) {
            $this->assertSame('invalid_transition', $e->getDetails()['error_type']);
            $this->assertSame('待审核', $e->getDetails()['from_state']);
            $this->assertSame('已激活', $e->getDetails()['to_state']);
        }
    }
}
