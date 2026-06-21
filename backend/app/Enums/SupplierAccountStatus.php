<?php

namespace App\Enums;

enum SupplierAccountStatus: string
{
    case PENDING = 'pending';
    case VERIFYING = 'verifying';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::VERIFYING => '审核中',
            self::ACTIVE => '已激活',
            self::SUSPENDED => '已暂停',
            self::REJECTED => '已拒绝',
            self::CANCELLED => '已注销',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::VERIFYING => 'info',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
            self::REJECTED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    public function timestampField(): ?string
    {
        return match ($this) {
            self::VERIFYING => 'verifying_at',
            self::ACTIVE => 'activated_at',
            self::SUSPENDED => 'suspended_at',
            self::REJECTED => 'rejected_at',
            self::CANCELLED => 'cancelled_at',
            default => null,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::CANCELLED,
        ], true);
    }

    public function canTransitionTo(self $target): bool
    {
        $transitions = [
            self::PENDING->value => [
                self::VERIFYING->value,
                self::REJECTED->value,
                self::CANCELLED->value,
            ],
            self::VERIFYING->value => [
                self::ACTIVE->value,
                self::REJECTED->value,
                self::SUSPENDED->value,
                self::PENDING->value,
            ],
            self::ACTIVE->value => [
                self::SUSPENDED->value,
                self::CANCELLED->value,
            ],
            self::SUSPENDED->value => [
                self::ACTIVE->value,
                self::CANCELLED->value,
            ],
            self::REJECTED->value => [],
            self::CANCELLED->value => [],
        ];

        return in_array($target->value, $transitions[$this->value] ?? [], true);
    }

    public function allowedTransitions(): array
    {
        $transitions = [
            self::PENDING->value => [self::VERIFYING, self::REJECTED, self::CANCELLED],
            self::VERIFYING->value => [self::ACTIVE, self::REJECTED, self::SUSPENDED, self::PENDING],
            self::ACTIVE->value => [self::SUSPENDED, self::CANCELLED],
            self::SUSPENDED->value => [self::ACTIVE, self::CANCELLED],
            self::REJECTED->value => [],
            self::CANCELLED->value => [],
        ];

        return $transitions[$this->value] ?? [];
    }
}
