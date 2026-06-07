<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Import/Index', [
            'accounts' => $request->user()->accounts()->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.date' => ['required', 'date'],
            'rows.*.description' => ['required', 'string', 'max:255'],
            'rows.*.amount' => ['required', 'numeric'],
        ]);

        abort_unless($request->user()->accounts()->whereKey($data['account_id'])->exists(), 403);

        $imported = 0;

        DB::transaction(function () use ($request, $data, &$imported) {
            foreach ($data['rows'] as $row) {
                $amount = (float) $row['amount'];
                if (abs($amount) < 0.01) {
                    continue; // ignora valores zerados
                }

                $request->user()->transactions()->create([
                    'account_id' => $data['account_id'],
                    'category_id' => null,
                    'type' => $amount < 0 ? 'expense' : 'income',
                    'amount' => abs($amount),
                    'description' => $row['description'],
                    'date' => $row['date'],
                ]);

                $imported++;
            }
        });

        return redirect()->route('transactions.index')
            ->with('success', "{$imported} lançamento(s) importado(s).");
    }
}
