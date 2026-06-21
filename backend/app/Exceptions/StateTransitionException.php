<?php

namespace App\Exceptions;

class StateTransitionException extends BaseException
{
    protected int $httpCode = 422;

    protected string $errorCode = 'STATE_TRANSITION_ERROR';

    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
    }

    public static function invalidType(): self
    {
        return new self('无效的供应商账户状态类型', [
            'error_type' => 'invalid_type',
        ]);
    }

    public static function invalidTransition(
        string $fromState,
        string $toState,
        array $allowedStates = [],
    ): self {
        $message = sprintf('不允许从「%s」变更为「%s」', $fromState, $toState);

        if (! empty($allowedStates)) {
            $message .= '，允许的目标状态：'.implode('、', $allowedStates);
        }

        return new self($message, [
            'error_type' => 'invalid_transition',
            'from_state' => $fromState,
            'to_state' => $toState,
            'allowed_states' => $allowedStates,
        ]);
    }

    public static function terminalState(string $state): self
    {
        return new self("当前已处于终态（{$state}），无法变更状态", [
            'error_type' => 'terminal_state',
            'current_state' => $state,
            'is_terminal' => true,
        ]);
    }

    public static function permissionDenied(
        string $fromState,
        string $toState,
        ?string $requiredPermission = null,
    ): self {
        $message = $requiredPermission
            ? "没有「{$requiredPermission}」权限，无法从「{$fromState}」变更为「{$toState}」"
            : "没有权限执行从「{$fromState}」到「{$toState}」的状态变更";

        return new self($message, [
            'error_type' => 'permission_denied',
            'from_state' => $fromState,
            'to_state' => $toState,
            'required_permission' => $requiredPermission,
        ]);
    }

    public static function businessRuleViolation(
        string $fromState,
        string $toState,
        string $ruleMessage,
        ?string $ruleCode = null,
    ): self {
        return new self($ruleMessage, [
            'error_type' => 'business_rule_violation',
            'from_state' => $fromState,
            'to_state' => $toState,
            'rule_code' => $ruleCode,
        ]);
    }

    public static function remarkRequired(
        string $fromState,
        string $toState,
    ): self {
        return new self("变更为「{$toState}」需要填写备注说明", [
            'error_type' => 'remark_required',
            'from_state' => $fromState,
            'to_state' => $toState,
        ]);
    }
}
