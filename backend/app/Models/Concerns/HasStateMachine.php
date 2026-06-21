<?php

namespace App\Models\Concerns;

use App\Contracts\StateMachine\StateMachineInterface;
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

        return $this;
    }

    public function canTransitionTo(BackedEnum $targetState, array $context = []): bool
    {
        return $this->stateMachine()->canTransitionTo($targetState, $context);
    }

    public function getStatusEnum(): SupplierAccountStatus
    {
        $rawValue = $this->getRawOriginal('status') ?? $this->status;

        return $rawValue instanceof SupplierAccountStatus
            ? $rawValue
            : SupplierAccountStatus::from($rawValue);
    }

    public function isPending(): bool
    {
        return $this->status === SupplierAccountStatus::PENDING;
    }

    public function isVerifying(): bool
    {
        return $this->status === SupplierAccountStatus::VERIFYING;
    }

    public function isActive(): bool
    {
        return $this->status === SupplierAccountStatus::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === SupplierAccountStatus::SUSPENDED;
    }

    public function isRejected(): bool
    {
        return $this->status === SupplierAccountStatus::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === SupplierAccountStatus::CANCELLED;
    }

    public function isTerminal(): bool
    {
        return $this->getStatusEnum()->isTerminal();
    }
}
