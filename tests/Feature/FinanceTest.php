<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_loads_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }

    public function test_user_can_create_a_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->expense()->create();

        $this->actingAs($user)->post('/transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 99.90,
            'description' => 'Compra teste',
            'date' => '2026-06-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Compra teste',
            'amount' => 99.90,
        ]);
    }

    public function test_transaction_requires_amount_and_description(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this->actingAs($user)->post('/transactions', [
            'account_id' => $account->id,
            'type' => 'expense',
            'date' => '2026-06-01',
        ])->assertSessionHasErrors(['amount', 'description']);
    }

    public function test_user_cannot_update_anothers_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = Account::factory()->for($owner)->create();
        $transaction = Transaction::factory()->for($owner)->for($account)->create();

        $this->actingAs($intruder)->put("/transactions/{$transaction->id}", [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 10,
            'description' => 'hack',
            'date' => '2026-06-01',
        ])->assertForbidden();
    }

    public function test_account_current_balance_is_computed(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 1000]);

        Transaction::factory()->for($user)->for($account)->create(['type' => 'income', 'amount' => 500]);
        Transaction::factory()->for($user)->for($account)->create(['type' => 'expense', 'amount' => 200]);

        $this->assertEquals(1300.0, $account->fresh()->currentBalance());
    }

    public function test_user_can_create_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/accounts', [
            'name' => 'Banco X',
            'type' => 'checking',
            'color' => '#7c3aed',
            'initial_balance' => 250,
        ])->assertRedirect();

        $this->assertDatabaseHas('accounts', ['user_id' => $user->id, 'name' => 'Banco X']);
    }

    public function test_user_can_create_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/categories', [
            'name' => 'Mercado',
            'type' => 'expense',
            'color' => '#ef4444',
            'icon' => '🛒',
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Mercado']);
    }

    public function test_budget_is_upserted_per_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->expense()->create();

        $this->actingAs($user)->post('/budgets', ['category_id' => $category->id, 'amount' => 500])->assertRedirect();
        $this->actingAs($user)->post('/budgets', ['category_id' => $category->id, 'amount' => 800])->assertRedirect();

        $this->assertEquals(1, Budget::where('category_id', $category->id)->count());
        $this->assertDatabaseHas('budgets', ['category_id' => $category->id, 'amount' => 800]);
    }
}
