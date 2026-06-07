<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    public const TYPES = [
        'cash' => 'Dinheiro',
        'checking' => 'Conta corrente',
        'savings' => 'Poupança',
        'credit' => 'Cartão de crédito',
    ];

    protected $fillable = [
        'name',
        'type',
        'color',
        'initial_balance',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Saldo atual = saldo inicial + receitas - despesas.
     * Usa o agregado pré-carregado quando disponível (evita N+1).
     */
    public function currentBalance(): float
    {
        $income = (float) ($this->transactions_income_sum ?? $this->transactions()->where('type', 'income')->sum('amount'));
        $expense = (float) ($this->transactions_expense_sum ?? $this->transactions()->where('type', 'expense')->sum('amount'));

        return (float) $this->initial_balance + $income - $expense;
    }
}
