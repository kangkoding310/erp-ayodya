<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Division/Index', [
            'divisions' => Division::query()
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:divisions,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Division::create($validated);

        return back()->with('success', 'Division created.');
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:divisions,code,'.$division->id],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $division->update($validated);

        return back()->with('success', 'Division updated.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        $division->delete();

        return back()->with('success', 'Division deleted.');
    }
}
