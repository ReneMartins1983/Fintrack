<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $monthQuery = fn (string $type) => $user->transactions()
            ->where('type', $type)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $monthIncome = (float) $monthQuery('income');
        $monthExpense = (float) $monthQuery('expense');

        // Saldo total = saldos iniciais + todas as receitas - todas as despesas
        $totalBalance = (float) $user->accounts()->sum('initial_balance')
            + (float) $user->transactions()->where('type', 'income')->sum('amount')
            - (float) $user->transactions()->where('type', 'expense')->sum('amount');

        return Inertia::render('Dashboard', [
            'summary' => [
                'balance' => round($totalBalance, 2),
                'income' => round($monthIncome, 2),
                'expense' => round($monthExpense, 2),
                'result' => round($monthIncome - $monthExpense, 2),
            ],
            'monthlyChart' => $this->monthlyEvolution($user->id),
            'categoryChart' => $this->expenseByCategory($user->id, $start, $end),
            'budgets' => $this->budgetProgress($user, $start, $end),
            'recent' => $user->transactions()
                ->with(['category:id,name,icon,color', 'account:id,name'])
                ->latest('date')->latest('id')->limit(8)->get(),
            'monthLabel' => $start->locale('pt_BR')->isoFormat('MMMM [de] YYYY'),
        ]);
    }

    /** Receitas x despesas dos últimos 6 meses. */
    private function monthlyEvolution(int $userId): array
    {
        $labels = [];
        $income = [];
        $expense = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $from = $month->copy()->startOfMonth()->toDateString();
            $to = $month->copy()->endOfMonth()->toDateString();

            $labels[] = $month->locale('pt_BR')->isoFormat('MMM');
            $income[] = round((float) Transaction::forUser($userId)->where('type', 'income')->between($from, $to)->sum('amount'), 2);
            $expense[] = round((float) Transaction::forUser($userId)->where('type', 'expense')->between($from, $to)->sum('amount'), 2);
        }

        return compact('labels', 'income', 'expense');
    }

    /** Despesas do mês agrupadas por categoria (para o gráfico de pizza). */
    private function expenseByCategory(int $userId, Carbon $start, Carbon $end): array
    {
        return Transaction::forUser($userId)
            ->where('transactions.type', 'expense')
            ->between($start->toDateString(), $end->toDateString())
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->selectRaw('categories.name, categories.color, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'color' => $row->color,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /** Progresso dos orçamentos do mês (gasto vs limite). */
    private function budgetProgress($user, Carbon $start, Carbon $end): array
    {
        return $user->budgets()->with('category:id,name,icon,color')->get()
            ->map(function ($budget) use ($user, $start, $end) {
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
            })->all();
    }
}
