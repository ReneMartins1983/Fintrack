<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => $request->user()->categories()
                ->withCount('transactions')
                ->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->categories()->create($this->validateData($request));

        return back()->with('success', 'Categoria criada.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeOwnership($request, $category);

        $category->update($this->validateData($request));

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeOwnership($request, $category);

        $category->delete();

        return back()->with('success', 'Categoria removida.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', 'in:income,expense'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['required', 'string', 'max:16'],
        ]);
    }

    private function authorizeOwnership(Request $request, Category $category): void
    {
        abort_unless($category->user_id === $request->user()->id, 403);
    }
}
