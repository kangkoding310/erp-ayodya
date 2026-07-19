<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Bank/Index', [
            'banks' => Bank::query()
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('bank_name', 'ilike', "%{$search}%"))
                ->orderBy('bank_name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:255'],
        ]);

        Bank::create($validated);

        return back()->with('success', 'Bank created.');
    }

    public function update(Request $request, Bank $bank): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:255'],
        ]);

        $bank->update($validated);

        return back()->with('success', 'Bank updated.');
    }

    public function destroy(Bank $bank): RedirectResponse
    {
        $bank->delete();

        return back()->with('success', 'Bank deleted.');
    }
}
