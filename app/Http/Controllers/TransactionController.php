<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $reference = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $query = $user->transactions()
            ->with(['category:id,name,icon,color,type', 'account:id,name,color'])
            ->whereBetween('date', [$reference->toDateString(), $reference->copy()->endOfMonth()->toDateString()]);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($accountId = $request->input('account_id')) {
            $query->where('account_id', $accountId);
        }

        $transactions = $query->latest('date')->latest('id')->paginate(20)->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'color']),
            'categories' => $user->categories()->orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']),
            'filters' => [
                'month' => $month,
                'type' => $request->input('type', ''),
                'category_id' => $request->input('category_id', ''),
                'account_id' => $request->input('account_id', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $request->user()->transactions()->create($data);

        return back()->with('success', 'Lançamento adicionado.');
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeOwnership($request, $transaction);

        $transaction->update($this->validateData($request));

        return back()->with('success', 'Lançamento atualizado.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeOwnership($request, $transaction);

        $transaction->delete();

        return back()->with('success', 'Lançamento removido.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        // garante que conta/categoria pertencem ao usuário
        abort_unless($request->user()->accounts()->whereKey($data['account_id'])->exists(), 403);
        if (! empty($data['category_id'])) {
            abort_unless($request->user()->categories()->whereKey($data['category_id'])->exists(), 403);
        }

        return $data;
    }

    private function authorizeOwnership(Request $request, Transaction $transaction): void
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
    }
}
