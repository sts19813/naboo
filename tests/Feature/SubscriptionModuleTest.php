<?php

namespace Tests\Feature;

use Tests\TestCase;

class SubscriptionModuleTest extends TestCase
{
    public function test_subscription_module_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('features.subscription_module_enabled'));
        $this->assertFalse(app('router')->has('subscription.index'));
        $this->get('/suscripcion')->assertNotFound();
    }
}
