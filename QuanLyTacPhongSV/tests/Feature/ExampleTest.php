<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_visiting_root_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect();
    }

    public function test_app_runs_in_vietnam_time(): void
    {
        $this->assertSame('Asia/Ho_Chi_Minh', config('app.timezone'));
    }
}
