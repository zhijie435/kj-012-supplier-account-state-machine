<?php

namespace App\Http\Controllers;

use App\Enums\SupplierAccountStatus;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierAccountStatusLogResource;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:supplier.view')->only(['index', 'show', 'statusLogs']);
        $this->middleware('permission:supplier.create')->only(['store']);
        $this->middleware('permission:supplier.edit')->only(['update', 'updateStatus']);
        $this->middleware('permission:supplier.delete')->only(['destroy']);
        $this->middleware('permission:supplier.approve')->only(['verify', 'activate', 'suspend', 'reject', 'cancel']);
    }

    public function index(Request $request)
    {
        $query = Supplier::withCount(['products', 'orders']);

        $this->applySearch($query, $request, ['name', 'company_name', 'contact_person', 'phone']);

        if ($request->filled('status')) {
            $query->byStatus($request->string('status'));
        }

        if ($request->filled('is_cross_border')) {
            $query->where('is_cross_border', $request->boolean('is_cross_border'));
        }

        return SupplierResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return new SupplierResource($supplier);
    }

    public function show(Request $request, Supplier $supplier)
    {
        return new SupplierResource(
            $supplier->loadCount(['products', 'orders'])
                ->load(['statusLogs' => fn ($q) => $q->with('operator')->limit(10)])
        );
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $supplier->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function updateStatus(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'remark' => ['nullable', 'string'],
        ]);

        $targetStatus = SupplierAccountStatus::from($validated['status']);

        $supplier->transitionTo($targetStatus, [
            'remark' => $validated['remark'] ?? null,
            'operated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh());
    }

    public function statusLogs(Request $request, Supplier $supplier)
    {
        $logs = $supplier->statusLogs()
            ->with('operator')
            ->paginate($this->perPage($request));

        return SupplierAccountStatusLogResource::collection($logs);
    }

    public function verify(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'remark' => ['nullable', 'string'],
        ]);

        $supplier->transitionTo(SupplierAccountStatus::VERIFYING, [
            'remark' => $validated['remark'] ?? null,
            'operated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh());
    }

    public function activate(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'remark' => ['nullable', 'string'],
        ]);

        $supplier->transitionTo(SupplierAccountStatus::ACTIVE, [
            'remark' => $validated['remark'] ?? null,
            'operated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh());
    }

    public function suspend(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'remark' => ['nullable', 'string'],
        ]);

        $supplier->transitionTo(SupplierAccountStatus::SUSPENDED, [
            'remark' => $validated['remark'] ?? null,
            'operated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh());
    }

    public function reject(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'remark' => ['required', 'string'],
        ]);

        $supplier->transitionTo(SupplierAccountStatus::REJECTED, [
            'remark' => $validated['remark'],
            'operated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh());
    }

    public function cancel(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'remark' => ['nullable', 'string'],
        ]);

        $supplier->transitionTo(SupplierAccountStatus::CANCELLED, [
            'remark' => $validated['remark'] ?? null,
            'operated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh());
    }

    public function validateTransition(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $targetStatus = SupplierAccountStatus::from($validated['status']);

        $result = $supplier->stateMachine()->validateTransition($targetStatus);

        return response()->json([
            'valid' => $result->isValid(),
            'message' => $result->message,
            'errors' => $result->errors,
        ]);
    }

    public function allowedTransitions(Request $request, Supplier $supplier)
    {
        $transitions = $supplier->allowedTransitions();

        return response()->json([
            'data' => array_map(
                fn ($status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                    'requires_remark' => $supplier->getStatusEnum()->requiresRemark($status),
                ],
                $transitions
            ),
        ]);
    }
}
