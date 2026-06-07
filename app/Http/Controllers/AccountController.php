<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $accounts = $request->user()->accounts()->orderBy('name')->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'type_label' => Account::TYPES[$account->type] ?? $account->type,
                'color' => $account->color,
                'initial_balance' => (float) $account->initial_balance,
                'balance' => $account->currentBalance(),
            ]);

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'types' => Account::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->accounts()->create($this->validateData($request));

        return back()->with('success', 'Conta criada.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $this->authorizeOwnership($request, $account);

        $account->update($this->validateData($request));

        return back()->with('success', 'Conta atualizada.');
    }

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        $this->authorizeOwnership($request, $account);

        $account->delete();

        return back()->with('success', 'Conta removida.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', 'in:'.implode(',', array_keys(Account::TYPES))],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'initial_balance' => ['required', 'numeric'],
        ]);
    }

    private function authorizeOwnership(Request $request, Account $account): void
    {
        abort_unless($account->user_id === $request->user()->id, 403);
    }
}
