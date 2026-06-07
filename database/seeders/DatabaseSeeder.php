<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo FinTrack',
            'email' => 'demo@fintrack.app',
            'password' => Hash::make('password'),
        ]);

        // --- Contas -----------------------------------------------------------
        $accounts = [];
        foreach ([
            'wallet' => ['Carteira', 'cash', '#a855f7', 320.00],
            'bank' => ['Conta corrente', 'checking', '#7c3aed', 4250.00],
            'savings' => ['Poupança', 'savings', '#0ea5e9', 15000.00],
            'card' => ['Cartão de crédito', 'credit', '#ef4444', 0.00],
        ] as $key => [$name, $type, $color, $balance]) {
            $accounts[$key] = $user->accounts()->create([
                'name' => $name,
                'type' => $type,
                'color' => $color,
                'initial_balance' => $balance,
            ]);
        }

        // --- Categorias -------------------------------------------------------
        $cats = [];
        $income = [
            'salary' => ['Salário', '#16a34a', '💰'],
            'freelance' => ['Freelance', '#0ea5e9', '💻'],
            'yield' => ['Rendimentos', '#f59e0b', '📈'],
        ];
        $expense = [
            'rent' => ['Aluguel', '#8b5cf6', '🏠'],
            'market' => ['Mercado', '#ef4444', '🛒'],
            'transport' => ['Transporte', '#06b6d4', '🚗'],
            'food' => ['Restaurante', '#f97316', '🍔'],
            'fun' => ['Lazer', '#ec4899', '🎮'],
            'health' => ['Saúde', '#14b8a6', '💊'],
            'subs' => ['Assinaturas', '#6366f1', '📺'],
            'education' => ['Educação', '#a855f7', '📚'],
        ];
        foreach ($income as $key => [$name, $color, $icon]) {
            $cats[$key] = $user->categories()->create([
                'name' => $name, 'type' => 'income', 'color' => $color, 'icon' => $icon,
            ]);
        }
        foreach ($expense as $key => [$name, $color, $icon]) {
            $cats[$key] = $user->categories()->create([
                'name' => $name, 'type' => 'expense', 'color' => $color, 'icon' => $icon,
            ]);
        }

        // --- Orçamentos (opcional) -------------------------------------------
        foreach (['market' => 1200, 'food' => 600, 'fun' => 400, 'transport' => 350] as $key => $limit) {
            Budget::create([
                'user_id' => $user->id,
                'category_id' => $cats[$key]->id,
                'amount' => $limit,
            ]);
        }

        // --- Lançamentos: 3 meses (atual + 2 anteriores) ----------------------
        // Cada item: [categoria, conta, dia, descrição, valor-base]
        $expenseTemplate = [
            ['rent', 'bank', 5, 'Aluguel do apartamento', 1500.00],
            ['market', 'card', 6, 'Compra no supermercado', 420.00],
            ['market', 'card', 18, 'Feira e hortifruti', 280.00],
            ['transport', 'card', 8, 'Combustível', 220.00],
            ['transport', 'wallet', 22, 'Aplicativos de transporte', 95.00],
            ['food', 'card', 12, 'Almoços e jantares', 310.00],
            ['food', 'wallet', 25, 'Lanches', 140.00],
            ['fun', 'card', 15, 'Cinema e streaming de jogos', 180.00],
            ['fun', 'card', 28, 'Bar com amigos', 160.00],
            ['health', 'bank', 10, 'Farmácia', 130.00],
            ['subs', 'card', 3, 'Netflix + Spotify', 89.00],
            ['education', 'bank', 14, 'Curso online', 120.00],
        ];
        $incomeTemplate = [
            ['salary', 'bank', 5, 'Salário mensal', 6500.00],
            ['freelance', 'bank', 20, 'Projeto freelance', 1300.00],
            ['yield', 'savings', 1, 'Rendimento da poupança', 95.00],
        ];

        // variação determinística por mês (sem aleatoriedade)
        $factors = [0 => 1.00, 1 => 0.92, 2 => 1.08];

        for ($m = 0; $m <= 2; $m++) {
            $month = Carbon::now()->startOfMonth()->subMonths($m);
            $factor = $factors[$m];

            foreach ($expenseTemplate as [$cat, $acc, $day, $desc, $base]) {
                // omite alguns itens em um mês para variar o histórico
                if ($m === 1 && in_array($cat, ['education', 'health'], true)) {
                    continue;
                }
                $this->tx($user, $cats[$cat], $accounts[$acc], 'expense', $desc,
                    round($base * $factor, 2), $month->copy()->day(min($day, $month->daysInMonth)));
            }

            foreach ($incomeTemplate as [$cat, $acc, $day, $desc, $base]) {
                if ($cat === 'freelance' && $m === 2) {
                    continue; // sem freelance há 2 meses
                }
                $this->tx($user, $cats[$cat], $accounts[$acc], 'income', $desc,
                    round($base * ($cat === 'salary' ? 1 : $factor), 2),
                    $month->copy()->day(min($day, $month->daysInMonth)));
            }
        }
    }

    private function tx(User $user, Category $cat, Account $acc, string $type, string $desc, float $amount, Carbon $date): void
    {
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $acc->id,
            'category_id' => $cat->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $desc,
            'date' => $date->toDateString(),
        ]);
    }
}
