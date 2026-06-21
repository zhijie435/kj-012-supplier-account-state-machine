<?php

namespace App\Models\Concerns;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\SupplierAccountStatus;
use App\Services\StateMachine\SupplierAccountStateMachine;
use BackedEnum;

trait HasStateMachine
{
    public function stateMachine(): StateMachineInterface
    {
        return new SupplierAccountStateMachine($this);
    }

    public function transitionTo(BackedEnum $targetState, array $context = []): self
    {
        $this->stateMachine()->transitionTo($targetState, $context);

        $this->refresh();

        return $this;
    }

    public function canTransitionTo(BackedEnum $targetState, array $context = []): bool
    {
        return $this->stateMachine()->canTransitionTo($targetState, $context);
    }

    public function validateTransition(BackedEnum $targetState, array $context = []): TransitionResult
    {
        return $this->stateMachine()->validateTransition($targetState, $context);
    }

    public function getStatusEnum(): SupplierAccountStatus
    {
        $currentValue = $this->status;

        return $currentValue instanceof SupplierAccountStatus
            ? $currentValue
            : SupplierAccountStatus::from($currentValue);
    }

    public function isPending(): bool
    {
        return $this->getStatusEnum() === SupplierAccountStatus::PENDING;
    }

    public function isVerifying(): bool
    {
        return $this->getStatusEnum() === SupplierAccountStatus::VERIFYING;
    }

    public function isActive(): bool
    {
        return $this->getStatusEnum() === SupplierAccountStatus::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->getStatusEnum() === SupplierAccountStatus::SUSPENDED;
    }

    public function isRejected(): bool
    {
        return $this->getStatusEnum() === SupplierAccountStatus::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->getStatusEnum() === SupplierAccountStatus::CANCELLED;
    }

    public function isTerminal(): bool
    {
        return $this->getStatusEnum()->isTerminal();
    }

    public function allowedTransitions(): array
    {
        return $this->stateMachine()->allowedTransitions();
    }
}
