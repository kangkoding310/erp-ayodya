<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    private const DEFAULT_PASSWORD = 'password';

    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Employee/Index', [
            'employees' => Employee::query()
                ->with('division:id,name', 'user:id,name,email')
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'division_id' => ['required', 'exists:divisions,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => self::DEFAULT_PASSWORD,
            ]);

            Employee::create([
                'name' => $validated['name'],
                'division_id' => $validated['division_id'],
                'position' => $validated['position'] ?? null,
                'user_id' => $user->id,
            ]);
        });

        return back()->with('success', 'Employee created.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $emailRule = Rule::unique('users', 'email');
        if ($employee->user_id) {
            $emailRule = $emailRule->ignore($employee->user_id);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'division_id' => ['required', 'exists:divisions,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $emailRule],
        ]);

        DB::transaction(function () use ($validated, $employee) {
            if ($employee->user_id) {
                $employee->user()->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ]);
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => self::DEFAULT_PASSWORD,
                ]);
                $employee->user_id = $user->id;
            }

            $employee->update([
                'name' => $validated['name'],
                'division_id' => $validated['division_id'],
                'position' => $validated['position'] ?? null,
                'user_id' => $employee->user_id,
            ]);
        });

        return back()->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        try {
            DB::transaction(function () use ($employee) {
                if ($employee->user_id) {
                    User::whereKey($employee->user_id)->delete();
                }

                $employee->delete();
            });
        } catch (QueryException $e) {
            if ($e->getCode() !== '23503') {
                throw $e;
            }

            return back()->with('error', 'Cannot delete employee: their linked user account is still referenced by other records (e.g. purchase requests or approvals).');
        }

        return back()->with('success', 'Employee deleted.');
    }
}
