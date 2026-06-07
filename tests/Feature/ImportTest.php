<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_requires_auth(): void
    {
        $this->get('/import')->assertRedirect('/login');
    }

    public function test_rows_are_imported_with_type_inferred_by_sign(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this->actingAs($user)->post('/import', [
            'account_id' => $account->id,
            'rows' => [
                ['date' => '2026-06-01', 'description' => 'Mercado', 'amount' => -120.50],
                ['date' => '2026-06-05', 'description' => 'Salário', 'amount' => 6500],
                ['date' => '2026-06-06', 'description' => 'Zerado', 'amount' => 0], // ignorado
            ],
        ])->assertRedirect(route('transactions.index'));

        $this->assertEquals(2, $user->transactions()->count());
        $this->assertDatabaseHas('transactions', ['description' => 'Mercado', 'type' => 'expense', 'amount' => 120.50]);
        $this->assertDatabaseHas('transactions', ['description' => 'Salário', 'type' => 'income', 'amount' => 6500]);
    }

    public function test_cannot_import_into_anothers_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->for($other)->create();

        $this->actingAs($user)->post('/import', [
            'account_id' => $account->id,
            'rows' => [['date' => '2026-06-01', 'description' => 'x', 'amount' => 10]],
        ])->assertForbidden();
    }
}
