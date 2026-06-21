<?php

namespace Tests\Unit\Enums;

use App\Enums\SupplierAccountStatus;
use PHPUnit\Framework\TestCase;

class SupplierAccountStatusTest extends TestCase
{
    public function test_all_cases_defined(): void
    {
        $cases = SupplierAccountStatus::cases();

        $this->assertCount(6, $cases);
        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('pending', $values);
        $this->assertContains('verifying', $values);
        $this->assertContains('active', $values);
        $this->assertContains('suspended', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('cancelled', $values);
    }

    public function test_label_mapping(): void
    {
        $this->assertSame('待审核', SupplierAccountStatus::PENDING->label());
        $this->assertSame('审核中', SupplierAccountStatus::VERIFYING->label());
        $this->assertSame('已激活', SupplierAccountStatus::ACTIVE->label());
        $this->assertSame('已暂停', SupplierAccountStatus::SUSPENDED->label());
        $this->assertSame('已拒绝', SupplierAccountStatus::REJECTED->label());
        $this->assertSame('已注销', SupplierAccountStatus::CANCELLED->label());
    }

    public function test_color_mapping(): void
    {
        $this->assertSame('warning', SupplierAccountStatus::PENDING->color());
        $this->assertSame('info', SupplierAccountStatus::VERIFYING->color());
        $this->assertSame('success', SupplierAccountStatus::ACTIVE->color());
        $this->assertSame('danger', SupplierAccountStatus::SUSPENDED->color());
        $this->assertSame('danger', SupplierAccountStatus::REJECTED->color());
        $this->assertSame('secondary', SupplierAccountStatus::CANCELLED->color());
    }

    public function test_timestamp_field_mapping(): void
    {
        $this->assertNull(SupplierAccountStatus::PENDING->timestampField());
        $this->assertSame('verifying_at', SupplierAccountStatus::VERIFYING->timestampField());
        $this->assertSame('activated_at', SupplierAccountStatus::ACTIVE->timestampField());
        $this->assertSame('suspended_at', SupplierAccountStatus::SUSPENDED->timestampField());
        $this->assertSame('rejected_at', SupplierAccountStatus::REJECTED->timestampField());
        $this->assertSame('cancelled_at', SupplierAccountStatus::CANCELLED->timestampField());
    }

    public function test_is_terminal(): void
    {
        $this->assertFalse(SupplierAccountStatus::PENDING->isTerminal());
        $this->assertFalse(SupplierAccountStatus::VERIFYING->isTerminal());
        $this->assertFalse(SupplierAccountStatus::ACTIVE->isTerminal());
        $this->assertFalse(SupplierAccountStatus::SUSPENDED->isTerminal());
        $this->assertTrue(SupplierAccountStatus::REJECTED->isTerminal());
        $this->assertTrue(SupplierAccountStatus::CANCELLED->isTerminal());
    }

    public function test_from_value(): void
    {
        $this->assertSame(SupplierAccountStatus::PENDING, SupplierAccountStatus::from('pending'));
        $this->assertSame(SupplierAccountStatus::VERIFYING, SupplierAccountStatus::from('verifying'));
        $this->assertSame(SupplierAccountStatus::ACTIVE, SupplierAccountStatus::from('active'));
        $this->assertSame(SupplierAccountStatus::SUSPENDED, SupplierAccountStatus::from('suspended'));
        $this->assertSame(SupplierAccountStatus::REJECTED, SupplierAccountStatus::from('rejected'));
        $this->assertSame(SupplierAccountStatus::CANCELLED, SupplierAccountStatus::from('cancelled'));
    }

    public function test_pending_can_transition_to(): void
    {
        $pending = SupplierAccountStatus::PENDING;

        $this->assertTrue($pending->canTransitionTo(SupplierAccountStatus::VERIFYING));
        $this->assertTrue($pending->canTransitionTo(SupplierAccountStatus::REJECTED));
        $this->assertTrue($pending->canTransitionTo(SupplierAccountStatus::CANCELLED));

        $this->assertFalse($pending->canTransitionTo(SupplierAccountStatus::PENDING));
        $this->assertFalse($pending->canTransitionTo(SupplierAccountStatus::ACTIVE));
        $this->assertFalse($pending->canTransitionTo(SupplierAccountStatus::SUSPENDED));
    }

    public function test_verifying_can_transition_to(): void
    {
        $verifying = SupplierAccountStatus::VERIFYING;

        $this->assertTrue($verifying->canTransitionTo(SupplierAccountStatus::PENDING));
        $this->assertTrue($verifying->canTransitionTo(SupplierAccountStatus::ACTIVE));
        $this->assertTrue($verifying->canTransitionTo(SupplierAccountStatus::REJECTED));
        $this->assertTrue($verifying->canTransitionTo(SupplierAccountStatus::SUSPENDED));

        $this->assertFalse($verifying->canTransitionTo(SupplierAccountStatus::VERIFYING));
        $this->assertFalse($verifying->canTransitionTo(SupplierAccountStatus::CANCELLED));
    }

    public function test_active_can_transition_to(): void
    {
        $active = SupplierAccountStatus::ACTIVE;

        $this->assertTrue($active->canTransitionTo(SupplierAccountStatus::SUSPENDED));
        $this->assertTrue($active->canTransitionTo(SupplierAccountStatus::CANCELLED));

        $this->assertFalse($active->canTransitionTo(SupplierAccountStatus::PENDING));
        $this->assertFalse($active->canTransitionTo(SupplierAccountStatus::VERIFYING));
        $this->assertFalse($active->canTransitionTo(SupplierAccountStatus::ACTIVE));
        $this->assertFalse($active->canTransitionTo(SupplierAccountStatus::REJECTED));
    }

    public function test_suspended_can_transition_to(): void
    {
        $suspended = SupplierAccountStatus::SUSPENDED;

        $this->assertTrue($suspended->canTransitionTo(SupplierAccountStatus::ACTIVE));
        $this->assertTrue($suspended->canTransitionTo(SupplierAccountStatus::CANCELLED));

        $this->assertFalse($suspended->canTransitionTo(SupplierAccountStatus::PENDING));
        $this->assertFalse($suspended->canTransitionTo(SupplierAccountStatus::VERIFYING));
        $this->assertFalse($suspended->canTransitionTo(SupplierAccountStatus::SUSPENDED));
        $this->assertFalse($suspended->canTransitionTo(SupplierAccountStatus::REJECTED));
    }

    public function test_rejected_cannot_transition(): void
    {
        $rejected = SupplierAccountStatus::REJECTED;

        foreach (SupplierAccountStatus::cases() as $target) {
            $this->assertFalse($rejected->canTransitionTo($target));
        }
    }

    public function test_cancelled_cannot_transition(): void
    {
        $cancelled = SupplierAccountStatus::CANCELLED;

        foreach (SupplierAccountStatus::cases() as $target) {
            $this->assertFalse($cancelled->canTransitionTo($target));
        }
    }

    public function test_pending_allowed_transitions(): void
    {
        $transitions = SupplierAccountStatus::PENDING->allowedTransitions();

        $this->assertCount(3, $transitions);
        $this->assertContains(SupplierAccountStatus::VERIFYING, $transitions);
        $this->assertContains(SupplierAccountStatus::REJECTED, $transitions);
        $this->assertContains(SupplierAccountStatus::CANCELLED, $transitions);
    }

    public function test_verifying_allowed_transitions(): void
    {
        $transitions = SupplierAccountStatus::VERIFYING->allowedTransitions();

        $this->assertCount(4, $transitions);
        $this->assertContains(SupplierAccountStatus::PENDING, $transitions);
        $this->assertContains(SupplierAccountStatus::ACTIVE, $transitions);
        $this->assertContains(SupplierAccountStatus::SUSPENDED, $transitions);
        $this->assertContains(SupplierAccountStatus::REJECTED, $transitions);
    }

    public function test_active_allowed_transitions(): void
    {
        $transitions = SupplierAccountStatus::ACTIVE->allowedTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(SupplierAccountStatus::SUSPENDED, $transitions);
        $this->assertContains(SupplierAccountStatus::CANCELLED, $transitions);
    }

    public function test_suspended_allowed_transitions(): void
    {
        $transitions = SupplierAccountStatus::SUSPENDED->allowedTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(SupplierAccountStatus::ACTIVE, $transitions);
        $this->assertContains(SupplierAccountStatus::CANCELLED, $transitions);
    }

    public function test_rejected_allowed_transitions_is_empty(): void
    {
        $this->assertEmpty(SupplierAccountStatus::REJECTED->allowedTransitions());
    }

    public function test_cancelled_allowed_transitions_is_empty(): void
    {
        $this->assertEmpty(SupplierAccountStatus::CANCELLED->allowedTransitions());
    }

    public function test_get_transition_config(): void
    {
        $pending = SupplierAccountStatus::PENDING;

        $verifyingConfig = $pending->getTransitionConfig(SupplierAccountStatus::VERIFYING);
        $this->assertNotNull($verifyingConfig);
        $this->assertSame('supplier.approve', $verifyingConfig['permission']);

        $rejectedConfig = $pending->getTransitionConfig(SupplierAccountStatus::REJECTED);
        $this->assertNotNull($rejectedConfig);
        $this->assertSame('supplier.approve', $rejectedConfig['permission']);
        $this->assertTrue($rejectedConfig['require_remark']);

        $activeConfig = $pending->getTransitionConfig(SupplierAccountStatus::ACTIVE);
        $this->assertNull($activeConfig);
    }

    public function test_get_required_permission(): void
    {
        $pending = SupplierAccountStatus::PENDING;

        $this->assertSame('supplier.approve', $pending->getRequiredPermission(SupplierAccountStatus::VERIFYING));
        $this->assertSame('supplier.approve', $pending->getRequiredPermission(SupplierAccountStatus::REJECTED));
        $this->assertNull($pending->getRequiredPermission(SupplierAccountStatus::ACTIVE));
    }

    public function test_requires_remark(): void
    {
        $pending = SupplierAccountStatus::PENDING;
        $verifying = SupplierAccountStatus::VERIFYING;

        $this->assertTrue($pending->requiresRemark(SupplierAccountStatus::REJECTED));
        $this->assertTrue($verifying->requiresRemark(SupplierAccountStatus::REJECTED));

        $this->assertFalse($pending->requiresRemark(SupplierAccountStatus::VERIFYING));
        $this->assertFalse($verifying->requiresRemark(SupplierAccountStatus::ACTIVE));
        $this->assertFalse($verifying->requiresRemark(SupplierAccountStatus::SUSPENDED));
    }
}
