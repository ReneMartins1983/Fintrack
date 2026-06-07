<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $budgets = $user->budgets()->with('category:id,name,icon,color')->get()
            ->map(function (Budget $budget) use ($user, $start, $end) {
                $spent = (float) $user->transactions()
                    ->where('type', 'expense')
                    ->where('category_id', $budget->category_id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->sum('amount');

                $limit = (float) $budget->amount;

                return [
                    'id' => $budget->id,
                    'category' => $budget->category,
                    'limit' => round($limit, 2),
                    'spent' => round($spent, 2),
                    'percent' => $limit > 0 ? min(100, round($spent / $limit * 100)) : 0,
                ];
            });

        $usedCategoryIds = $user->budgets()->pluck('category_id');

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets,
            'monthLabel' => $start->locale('pt_BR')->isoFormat('MMMM [de] YYYY'),
            'availableCategories' => $user->categories()
                ->where('type', 'expense')
                ->whereNotIn('id', $usedCategoryIds)
                ->orderBy('name')->get(['id', 'name', 'icon', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        abort_unless($request->user()->categories()->whereKey($data['category_id'])->exists(), 403);

        // upsert: um orçamento por categoria
        $request->user()->budgets()->updateOrCreate(
            ['category_id' => $data['category_id']],
            ['amount' => $data['amount']],
        );

        return back()->with('success', 'Orçamento salvo.');
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        abort_unless($budget->user_id === $request->user()->id, 403);

        $budget->delete();

        return back()->with('success', 'Orçamento removido.');
    }
}
