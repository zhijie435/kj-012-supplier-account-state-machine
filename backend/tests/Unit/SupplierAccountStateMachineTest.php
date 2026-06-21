<?php

namespace Tests\Unit;

use App\Enums\SupplierAccountStatus;
use App\Exceptions\StateTransitionException;
use App\Models\Supplier;
use App\Services\StateMachine\SupplierAccountStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierAccountStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_enum_has_correct_labels()
    {
        $this->assertEquals('待审核', SupplierAccountStatus::PENDING->label());
        $this->assertEquals('审核中', SupplierAccountStatus::VERIFYING->label());
        $this->assertEquals('已激活', SupplierAccountStatus::ACTIVE->label());
        $this->assertEquals('已暂停', SupplierAccountStatus::SUSPENDED->label());
        $this->assertEquals('已拒绝', SupplierAccountStatus::REJECTED->label());
        $this->assertEquals('已注销', SupplierAccountStatus::CANCELLED->label());
    }

    public function test_enum_has_correct_colors()
    {
        $this->assertEquals('warning', SupplierAccountStatus::PENDING->color());
        $this->assertEquals('info', SupplierAccountStatus::VERIFYING->color());
        $this->assertEquals('success', SupplierAccountStatus::ACTIVE->color());
        $this->assertEquals('danger', SupplierAccountStatus::SUSPENDED->color());
        $this->assertEquals('danger', SupplierAccountStatus::REJECTED->color());
        $this->assertEquals('secondary', SupplierAccountStatus::CANCELLED->color());
    }

    public function test_terminal_states()
    {
        $this->assertFalse(SupplierAccountStatus::PENDING->isTerminal());
        $this->assertFalse(SupplierAccountStatus::VERIFYING->isTerminal());
        $this->assertFalse(SupplierAccountStatus::ACTIVE->isTerminal());
        $this->assertFalse(SupplierAccountStatus::SUSPENDED->isTerminal());
        $this->assertTrue(SupplierAccountStatus::REJECTED->isTerminal());
        $this->assertTrue(SupplierAccountStatus::CANCELLED->isTerminal());
    }

    public function test_pending_can_transition_to_verifying()
    {
        $this->assertTrue(SupplierAccountStatus::PENDING->canTransitionTo(SupplierAccountStatus::VERIFYING));
    }

    public function test_pending_can_transition_to_rejected()
    {
        $this->assertTrue(SupplierAccountStatus::PENDING->canTransitionTo(SupplierAccountStatus::REJECTED));
    }

    public function test_pending_can_transition_to_cancelled()
    {
        $this->assertTrue(SupplierAccountStatus::PENDING->canTransitionTo(SupplierAccountStatus::CANCELLED));
    }

    public function test_pending_cannot_transition_to_active()
    {
        $this->assertFalse(SupplierAccountStatus::PENDING->canTransitionTo(SupplierAccountStatus::ACTIVE));
    }

    public function test_verifying_can_transition_to_active()
    {
        $this->assertTrue(SupplierAccountStatus::VERIFYING->canTransitionTo(SupplierAccountStatus::ACTIVE));
    }

    public function test_verifying_can_transition_to_suspended()
    {
        $this->assertTrue(SupplierAccountStatus::VERIFYING->canTransitionTo(SupplierAccountStatus::SUSPENDED));
    }

    public function test_verifying_can_transition_back_to_pending()
    {
        $this->assertTrue(SupplierAccountStatus::VERIFYING->canTransitionTo(SupplierAccountStatus::PENDING));
    }

    public function test_active_can_transition_to_suspended()
    {
        $this->assertTrue(SupplierAccountStatus::ACTIVE->canTransitionTo(SupplierAccountStatus::SUSPENDED));
    }

    public function test_active_can_transition_to_cancelled()
    {
        $this->assertTrue(SupplierAccountStatus::ACTIVE->canTransitionTo(SupplierAccountStatus::CANCELLED));
    }

    public function test_suspended_can_transition_to_active()
    {
        $this->assertTrue(SupplierAccountStatus::SUSPENDED->canTransitionTo(SupplierAccountStatus::ACTIVE));
    }

    public function test_suspended_can_transition_to_cancelled()
    {
        $this->assertTrue(SupplierAccountStatus::SUSPENDED->canTransitionTo(SupplierAccountStatus::CANCELLED));
    }

    public function test_rejected_cannot_transition_to_any_state()
    {
        $this->assertEquals([], SupplierAccountStatus::REJECTED->allowedTransitions());
    }

    public function test_cancelled_cannot_transition_to_any_state()
    {
        $this->assertEquals([], SupplierAccountStatus::CANCELLED->allowedTransitions());
    }

    public function test_state_machine_validation_for_invalid_transition()
    {
        $supplier = new Supplier(['status' => SupplierAccountStatus::PENDING->value]);
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::ACTIVE);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('不允许从', $result->message);
    }

    public function test_state_machine_validation_for_same_state()
    {
        $supplier = new Supplier(['status' => SupplierAccountStatus::PENDING->value]);
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::PENDING);

        $this->assertTrue($result->isValid());
        $this->assertEquals('状态未变更', $result->message);
    }

    public function test_state_machine_validation_for_terminal_state()
    {
        $supplier = new Supplier(['status' => SupplierAccountStatus::REJECTED->value]);
        $stateMachine = new SupplierAccountStateMachine($supplier);

        $result = $stateMachine->validateTransition(SupplierAccountStatus::ACTIVE);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('终态', $result->message);
    }

    public function test_allowed_transitions_for_pending()
    {
        $transitions = SupplierAccountStatus::PENDING->allowedTransitions();
        $values = array_map(fn ($s) => $s->value, $transitions);

        $this->assertContains(SupplierAccountStatus::VERIFYING->value, $values);
        $this->assertContains(SupplierAccountStatus::REJECTED->value, $values);
        $this->assertContains(SupplierAccountStatus::CANCELLED->value, $values);
        $this->assertNotContains(SupplierAccountStatus::ACTIVE->value, $values);
    }

    public function test_allowed_transitions_for_active()
    {
        $transitions = SupplierAccountStatus::ACTIVE->allowedTransitions();
        $values = array_map(fn ($s) => $s->value, $transitions);

        $this->assertContains(SupplierAccountStatus::SUSPENDED->value, $values);
        $this->assertContains(SupplierAccountStatus::CANCELLED->value, $values);
        $this->assertNotContains(SupplierAccountStatus::VERIFYING->value, $values);
    }

    public function test_timestamp_fields()
    {
        $this->assertEquals('verifying_at', SupplierAccountStatus::VERIFYING->timestampField());
        $this->assertEquals('activated_at', SupplierAccountStatus::ACTIVE->timestampField());
        $this->assertEquals('suspended_at', SupplierAccountStatus::SUSPENDED->timestampField());
        $this->assertEquals('rejected_at', SupplierAccountStatus::REJECTED->timestampField());
        $this->assertEquals('cancelled_at', SupplierAccountStatus::CANCELLED->timestampField());
        $this->assertNull(SupplierAccountStatus::PENDING->timestampField());
    }

    public function test_supplier_model_has_status_accessors()
    {
        $supplier = new Supplier(['status' => SupplierAccountStatus::ACTIVE->value]);

        $this->assertEquals('已激活', $supplier->status_label);
        $this->assertEquals('success', $supplier->status_color);
    }

    public function test_supplier_model_scope_filters()
    {
        Supplier::create([
            'name' => '测试供应商1',
            'contact_person' => '张三',
            'phone' => '13800138001',
            'status' => SupplierAccountStatus::PENDING->value,
        ]);

        Supplier::create([
            'name' => '测试供应商2',
            'contact_person' => '李四',
            'phone' => '13800138002',
            'status' => SupplierAccountStatus::ACTIVE->value,
        ]);

        $this->assertEquals(1, Supplier::pending()->count());
        $this->assertEquals(1, Supplier::active()->count());
        $this->assertEquals(0, Supplier::suspended()->count());
    }
}
