<?php

namespace App\Services\StateMachine;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\SupplierAccountStatus;
use App\Exceptions\StateTransitionException;
use App\Models\Supplier;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupplierAccountStateMachine implements StateMachineInterface
{
    public function __construct(
        protected Supplier $supplier,
    ) {}

    public function getModel(): Model
    {
        return $this->supplier;
    }

    public function currentState(): BackedEnum
    {
        return $this->supplier->status instanceof SupplierAccountStatus
            ? $this->supplier->status
            : SupplierAccountStatus::from($this->supplier->status);
    }

    public function canTransitionTo(BackedEnum $targetState, array $context = []): bool
    {
        if (! $targetState instanceof SupplierAccountStatus) {
            return false;
        }

        return $this->currentState()->canTransitionTo($targetState);
    }

    public function validateTransition(BackedEnum $targetState, array $context = []): TransitionResult
    {
        if (! $targetState instanceof SupplierAccountStatus) {
            return TransitionResult::failure('无效的供应商账户状态类型');
        }

        $current = $this->currentState();

        if ($current === $targetState) {
            return TransitionResult::success('状态未变更');
        }

        if ($current->isTerminal()) {
            return TransitionResult::failure("供应商账户已处于终态（{$current->label()}），无法变更状态");
        }

        if (! $this->canTransitionTo($targetState, $context)) {
            $allowed = array_map(fn (SupplierAccountStatus $s) => $s->label(), $current->allowedTransitions());
            $allowedStr = $allowed ? implode('、', $allowed) : '无';

            return TransitionResult::failure(
                "不允许从「{$current->label()}」变更为「{$targetState->label()}」，允许的目标状态：{$allowedStr}"
            );
        }

        return $this->validateBusinessRules($targetState, $context);
    }

    protected function validateBusinessRules(SupplierAccountStatus $targetState, array $context): TransitionResult
    {
        switch ($targetState) {
            case SupplierAccountStatus::ACTIVE:
                if (empty($this->supplier->business_license)) {
                    return TransitionResult::failure('供应商激活前需上传营业执照');
                }
                if (empty($this->supplier->contact_person) || empty($this->supplier->phone)) {
                    return TransitionResult::failure('供应商激活前需完善联系人和联系方式');
                }
                break;

            case SupplierAccountStatus::CANCELLED:
                if ($this->supplier->orders()->count() > 0) {
                    return TransitionResult::failure('供应商存在关联订单，无法注销');
                }
                break;
        }

        return TransitionResult::success();
    }

    public function transitionTo(BackedEnum $targetState, array $context = []): Model
    {
        if (! $targetState instanceof SupplierAccountStatus) {
            throw new \InvalidArgumentException('无效的供应商账户状态类型');
        }

        $validation = $this->validateTransition($targetState, $context);

        if ($validation->isInvalid()) {
            throw new StateTransitionException(
                $validation->message ?: "不允许从「{$this->currentState()->label()}」变更为「{$targetState->label()}」",
                [
                    'from_state' => $this->currentState()->label(),
                    'to_state' => $targetState->label(),
                    'allowed_states' => array_map(fn (SupplierAccountStatus $s) => $s->label(), $this->currentState()->allowedTransitions()),
                ]
            );
        }

        return DB::transaction(function () use ($targetState, $context) {
            $timestampField = $targetState->timestampField();

            if ($timestampField && ! $this->supplier->$timestampField) {
                $this->supplier->$timestampField = now();
            }

            $this->supplier->status = $targetState;

            if (array_key_exists('remark', $context)) {
                $this->supplier->remark = $context['remark'];
            }

            if (isset($context['operated_by'])) {
                $this->supplier->operated_by = $context['operated_by'];
            }

            $this->supplier->save();

            return $this->supplier;
        });
    }

    public function allowedTransitions(): array
    {
        return $this->currentState()->allowedTransitions();
    }
}
