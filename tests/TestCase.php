<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テストから外部（Gemini API など）へ実際に通信しない。Http::fake() していない通信は例外にする
        Http::preventStrayRequests();
    }
}
