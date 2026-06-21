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

    private const TRANSITION_MAP = [
        self::PENDING->value => [
            self::VERIFYING->value => ['permission' => 'supplier.approve'],
            self::REJECTED->value => ['permission' => 'supplier.approve', 'require_remark' => true],
            self::CANCELLED->value => ['permission' => 'supplier.approve'],
        ],
        self::VERIFYING->value => [
            self::ACTIVE->value => ['permission' => 'supplier.approve'],
            self::REJECTED->value => ['permission' => 'supplier.approve', 'require_remark' => true],
            self::SUSPENDED->value => ['permission' => 'supplier.approve'],
            self::PENDING->value => ['permission' => 'supplier.approve'],
        ],
        self::ACTIVE->value => [
            self::SUSPENDED->value => ['permission' => 'supplier.approve'],
            self::CANCELLED->value => ['permission' => 'supplier.approve'],
        ],
        self::SUSPENDED->value => [
            self::ACTIVE->value => ['permission' => 'supplier.approve'],
            self::CANCELLED->value => ['permission' => 'supplier.approve'],
        ],
        self::REJECTED->value => [],
        self::CANCELLED->value => [],
    ];

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
        return in_array($this, [self::REJECTED, self::CANCELLED], true);
    }

    public function canTransitionTo(self $target): bool
    {
        return isset(self::TRANSITION_MAP[$this->value][$target->value]);
    }

    public function allowedTransitions(): array
    {
        return array_map(
            fn (string $value) => self::from($value),
            array_keys(self::TRANSITION_MAP[$this->value] ?? [])
        );
    }

    public function getTransitionConfig(self $target): ?array
    {
        return self::TRANSITION_MAP[$this->value][$target->value] ?? null;
    }

    public function getRequiredPermission(self $target): ?string
    {
        return $this->getTransitionConfig($target)['permission'] ?? null;
    }

    public function requiresRemark(self $target): bool
    {
        return $this->getTransitionConfig($target)['require_remark'] ?? false;
    }
}
