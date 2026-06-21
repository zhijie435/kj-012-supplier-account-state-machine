<?php

namespace Tests\Unit\Models\Concerns;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\SupplierAccountStatus;
use App\Exceptions\StateTransitionException;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HasStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('supplier.approve', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo('supplier.approve');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('admin');
    }

    public function test_state_machine_returns_interface_instance(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $stateMachine = $supplier->stateMachine();

        $this->assertInstanceOf(StateMachineInterface::class, $stateMachine);
    }

    public function test_get_status_enum_from_enum_value(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $status = $supplier->getStatusEnum();

        $this->assertSame(SupplierAccountStatus::PENDING, $status);
    }

    public function test_get_status_enum_from_string_value(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'active']);

        $status = $supplier->getStatusEnum();

        $this->assertSame(SupplierAccountStatus::ACTIVE, $status);
    }

    public function test_is_pending(): void
    {
        $pending = Supplier::factory()->pending()->create();
        $active = Supplier::factory()->active()->create();

        $this->assertTrue($pending->isPending());
        $this->assertFalse($active->isPending());
    }

    public function test_is_verifying(): void
    {
        $verifying = Supplier::factory()->verifying()->create();
        $pending = Supplier::factory()->pending()->create();

        $this->assertTrue($verifying->isVerifying());
        $this->assertFalse($pending->isVerifying());
    }

    public function test_is_active(): void
    {
        $active = Supplier::factory()->active()->create();
        $suspended = Supplier::factory()->suspended()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($suspended->isActive());
    }

    public function test_is_suspended(): void
    {
        $suspended = Supplier::factory()->suspended()->create();
        $active = Supplier::factory()->active()->create();

        $this->assertTrue($suspended->isSuspended());
        $this->assertFalse($active->isSuspended());
    }

    public function test_is_rejected(): void
    {
        $rejected = Supplier::factory()->rejected()->create();
        $cancelled = Supplier::factory()->cancelled()->create();

        $this->assertTrue($rejected->isRejected());
        $this->assertFalse($cancelled->isRejected());
    }

    public function test_is_cancelled(): void
    {
        $cancelled = Supplier::factory()->cancelled()->create();
        $rejected = Supplier::factory()->rejected()->create();

        $this->assertTrue($cancelled->isCancelled());
        $this->assertFalse($rejected->isCancelled());
    }

    public function test_is_terminal(): void
    {
        $rejected = Supplier::factory()->rejected()->create();
        $cancelled = Supplier::factory()->cancelled()->create();
        $pending = Supplier::factory()->pending()->create();

        $this->assertTrue($rejected->isTerminal());
        $this->assertTrue($cancelled->isTerminal());
        $this->assertFalse($pending->isTerminal());
    }

    public function test_status_label_attribute(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->assertSame('待审核', $supplier->status_label);
    }

    public function test_status_color_attribute(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->assertSame('warning', $supplier->status_color);
    }

    public function test_allowed_transitions_attribute(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $transitions = $supplier->allowed_transitions;

        $this->assertIsArray($transitions);
        $this->assertNotEmpty($transitions);
        $this->assertContains(SupplierAccountStatus::VERIFYING, $transitions);
    }

    public function test_can_transition_to_valid(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->assertTrue($supplier->canTransitionTo(SupplierAccountStatus::VERIFYING));
    }

    public function test_can_transition_to_invalid(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->assertFalse($supplier->canTransitionTo(SupplierAccountStatus::ACTIVE));
    }

    public function test_validate_transition(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $result = $supplier->validateTransition(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $this->operator->id]
        );

        $this->assertInstanceOf(TransitionResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    public function test_transition_to_success(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $result = $supplier->transitionTo(
            SupplierAccountStatus::VERIFYING,
            ['operated_by' => $this->operator->id]
        );

        $this->assertSame(SupplierAccountStatus::VERIFYING, $result->status);
    }

    public function test_transition_to_failure_throws_exception(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->expectException(StateTransitionException::class);

        $supplier->transitionTo(
            SupplierAccountStatus::ACTIVE,
            ['operated_by' => $this->operator->id]
        );
    }

    public function test_allowed_transitions(): void
    {
        $supplier = Supplier::factory()->active()->create();

        $transitions = $supplier->allowedTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(SupplierAccountStatus::SUSPENDED, $transitions);
        $this->assertContains(SupplierAccountStatus::CANCELLED, $transitions);
    }
}
