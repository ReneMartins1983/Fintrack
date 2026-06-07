<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Evita resolver o manifest do Vite ao renderizar páginas nos testes.
        $this->withoutVite();
    }
}
