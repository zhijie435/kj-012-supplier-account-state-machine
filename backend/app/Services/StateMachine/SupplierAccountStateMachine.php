<?php

namespace App\Services\StateMachine;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\SupplierAccountStatus;
use App\Exceptions\StateTransitionException;
use App\Models\Supplier;
use App\Models\SupplierAccountStatusLog;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierAccountStateMachine implements StateMachineInterface
{
    private const BUSINESS_RULES = [
        SupplierAccountStatus::ACTIVE->value => [
            [
                'condition' => 'empty_business_license',
                'message' => '供应商激活前需上传营业执照',
            ],
            [
                'condition' => 'empty_contact_info',
                'message' => '供应商激活前需完善联系人和联系方式',
            ],
        ],
        SupplierAccountStatus::CANCELLED->value => [
            [
                'condition' => 'has_orders',
                'message' => '供应商存在关联订单，无法注销',
            ],
        ],
    ];

    public function __construct(
        protected Supplier $supplier,
    ) {}

    public function getModel(): Model
    {
        return $this->supplier;
    }

    public function currentState(): SupplierAccountStatus
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
            return TransitionResult::failure('无效的供应商账户状态类型', ['invalid_type' => true]);
        }

        $current = $this->currentState();

        if ($current->isTerminal()) {
            if ($current === $targetState) {
                return TransitionResult::success('状态未变更');
            }

            return TransitionResult::failure(
                "供应商账户已处于终态（{$current->label()}），无法变更状态",
                ['terminal_state' => true, 'current_state' => $current->value]
            );
        }

        if ($current === $targetState) {
            return TransitionResult::success('状态未变更');
        }

        if (! $this->canTransitionTo($targetState, $context)) {
            $allowed = array_map(fn (SupplierAccountStatus $s) => $s->label(), $current->allowedTransitions());

            return TransitionResult::failure(
                "不允许从「{$current->label()}」变更为「{$targetState->label()}」，允许的目标状态：".($allowed ? implode('、', $allowed) : '无'),
                [
                    'invalid_transition' => true,
                    'from_state' => $current->value,
                    'to_state' => $targetState->value,
                    'allowed_states' => array_map(fn (SupplierAccountStatus $s) => $s->value, $current->allowedTransitions()),
                ]
            );
        }

        $remarkValidation = $this->validateRemarkRequirement($current, $targetState, $context);
        if ($remarkValidation->isInvalid()) {
            return $remarkValidation;
        }

        $permissionValidation = $this->validatePermission($current, $targetState, $context);
        if ($permissionValidation->isInvalid()) {
            return $permissionValidation;
        }

        return $this->validateBusinessRules($targetState, $context);
    }

    protected function validateRemarkRequirement(
        SupplierAccountStatus $current,
        SupplierAccountStatus $targetState,
        array $context
    ): TransitionResult {
        if ($current->requiresRemark($targetState) && empty($context['remark'])) {
            return TransitionResult::failure(
                "变更为「{$targetState->label()}」需要填写备注说明",
                ['remark_required' => true]
            );
        }

        return TransitionResult::success();
    }

    protected function validatePermission(
        SupplierAccountStatus $current,
        SupplierAccountStatus $targetState,
        array $context
    ): TransitionResult {
        $requiredPermission = $current->getRequiredPermission($targetState);

        if ($requiredPermission === null) {
            return TransitionResult::success();
        }

        $operatorId = $context['operated_by'] ?? Auth::id();

        if ($operatorId === null) {
            return TransitionResult::failure(
                '无法确定操作人，无法执行状态变更',
                ['permission_denied' => true, 'missing_operator' => true]
            );
        }

        $operator = User::find($operatorId);

        if ($operator === null) {
            return TransitionResult::failure(
                '操作人不存在，无法执行状态变更',
                ['permission_denied' => true, 'invalid_operator' => true]
            );
        }

        if (! $operator->can($requiredPermission)) {
            return TransitionResult::failure(
                "您没有「{$requiredPermission}」权限，无法执行此状态变更",
                ['permission_denied' => true, 'required_permission' => $requiredPermission]
            );
        }

        return TransitionResult::success();
    }

    protected function validateBusinessRules(SupplierAccountStatus $targetState, array $context): TransitionResult
    {
        $rules = self::BUSINESS_RULES[$targetState->value] ?? [];

        foreach ($rules as $rule) {
            if ($this->checkBusinessRuleCondition($rule['condition'])) {
                return TransitionResult::failure($rule['message'], [
                    'business_rule_violation' => true,
                    'rule' => $rule['condition'],
                ]);
            }
        }

        return TransitionResult::success();
    }

    protected function checkBusinessRuleCondition(string $condition): bool
    {
        return match ($condition) {
            'empty_business_license' => empty($this->supplier->business_license),
            'empty_contact_info' => empty($this->supplier->contact_person) || empty($this->supplier->phone),
            'has_orders' => $this->supplier->orders()->count() > 0,
            default => false,
        };
    }

    public function transitionTo(BackedEnum $targetState, array $context = []): Model
    {
        if (! $targetState instanceof SupplierAccountStatus) {
            throw StateTransitionException::invalidType();
        }

        $currentState = $this->currentState();

        $validation = $this->validateTransition($targetState, $context);

        if ($validation->isInvalid()) {
            throw $this->buildExceptionFromValidation($currentState, $targetState, $validation);
        }

        if ($currentState === $targetState) {
            return $this->supplier;
        }

        return DB::transaction(function () use ($currentState, $targetState, $context) {
            $timestampField = $targetState->timestampField();

            if ($timestampField) {
                $this->supplier->$timestampField = now();
            }

            $operatorId = $context['operated_by'] ?? Auth::id();
            $this->supplier->operated_by = $operatorId;
            $this->supplier->status = $targetState;

            SupplierAccountStatusLog::create([
                'supplier_id' => $this->supplier->id,
                'from_status' => $currentState,
                'to_status' => $targetState,
                'remark' => $context['remark'] ?? null,
                'operated_by' => $operatorId,
            ]);

            $this->supplier->save();

            return $this->supplier;
        });
    }

    protected function buildExceptionFromValidation(
        SupplierAccountStatus $currentState,
        SupplierAccountStatus $targetState,
        TransitionResult $validation
    ): StateTransitionException {
        $errors = $validation->errors;

        if (isset($errors['terminal_state'])) {
            return StateTransitionException::terminalState($currentState->label());
        }

        if (isset($errors['invalid_transition'])) {
            return StateTransitionException::invalidTransition(
                $currentState->label(),
                $targetState->label(),
                array_map(
                    fn (string $value) => SupplierAccountStatus::from($value)->label(),
                    $errors['allowed_states'] ?? []
                )
            );
        }

        if (isset($errors['permission_denied'])) {
            return StateTransitionException::permissionDenied(
                $currentState->label(),
                $targetState->label(),
                $errors['required_permission'] ?? null
            );
        }

        if (isset($errors['business_rule_violation'])) {
            return StateTransitionException::businessRuleViolation(
                $currentState->label(),
                $targetState->label(),
                $validation->message,
                $errors['rule'] ?? null
            );
        }

        return StateTransitionException::invalidTransition(
            $currentState->label(),
            $targetState->label(),
            array_map(fn (SupplierAccountStatus $s) => $s->label(), $currentState->allowedTransitions())
        );
    }

    public function allowedTransitions(): array
    {
        return $this->currentState()->allowedTransitions();
    }
}
