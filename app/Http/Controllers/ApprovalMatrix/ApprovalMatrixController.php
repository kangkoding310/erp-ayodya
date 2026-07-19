<?php

namespace App\Http\Controllers\ApprovalMatrix;

use App\Http\Controllers\Controller;
use App\Models\ApprovalMatrix;
use App\Models\Employee;
use App\Models\PurchaseRequest;
use App\Models\PurchaseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalMatrixController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ApprovalMatrix/Index', [
            'approvalMatrices' => ApprovalMatrix::query()
                ->with('purchaseType:id,name', 'levels.approver:id,name')
                ->orderBy('name')
                ->get(),
            'purchaseTypes' => PurchaseType::orderBy('name')->get(['id', 'name']),
            'users' => Employee::query()
                ->whereNotNull('user_id')
                ->orderBy('name')
                ->get(['user_id', 'name'])
                ->map(fn (Employee $employee) => ['id' => $employee->user_id, 'name' => $employee->name])
                ->values(),
            'modelTypes' => [
                ['value' => PurchaseRequest::class, 'label' => 'Purchase Request'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated) {
            $matrix = ApprovalMatrix::create([
                'name' => $validated['name'],
                'purchase_type_id' => $validated['purchase_type_id'] ?? null,
                'model_type' => $validated['model_type'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['levels'] as $level) {
                $matrix->levels()->create($level);
            }
        });

        return back()->with('success', 'Approval matrix created.');
    }

    public function update(Request $request, ApprovalMatrix $approvalMatrix): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated, $approvalMatrix) {
            $approvalMatrix->update([
                'name' => $validated['name'],
                'purchase_type_id' => $validated['purchase_type_id'] ?? null,
                'model_type' => $validated['model_type'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $approvalMatrix->levels()->delete();

            foreach ($validated['levels'] as $level) {
                $approvalMatrix->levels()->create($level);
            }
        });

        return back()->with('success', 'Approval matrix updated.');
    }

    public function destroy(ApprovalMatrix $approvalMatrix): RedirectResponse
    {
        $approvalMatrix->delete();

        return back()->with('success', 'Approval matrix deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'purchase_type_id' => ['nullable', 'exists:purchase_types,id'],
            'model_type' => ['required', 'string'],
            'is_active' => ['boolean'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.level' => ['required', 'integer', 'min:1'],
            'levels.*.approver_id' => ['required', 'exists:users,id'],
            'levels.*.is_required' => ['boolean'],
        ]);
    }
}
