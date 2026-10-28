<?php

namespace Tests\Feature\Panel;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_a_business_are_sent_to_onboarding()
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertRedirect(route('onboarding.create'));
        $this->get(route('services.index'))->assertRedirect(route('onboarding.create'));
        $this->get(route('onboarding.create'))->assertOk();
    }

    public function test_onboarding_creates_the_business_with_a_unique_slug()
    {
        Business::factory()->create(['name' => 'Barbería Central', 'slug' => 'barberia-central']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.store'), [
                'name' => 'Barbería Central',
                'phone' => '+591 70000000',
            ])
            ->assertRedirect(route('dashboard'));

        $business = $user->business()->sole();
        $this->assertSame('barberia-central-2', $business->slug);
        $this->assertSame('America/La_Paz', $business->timezone);
    }

    public function test_onboarding_is_skipped_once_the_business_exists()
    {
        $business = Business::factory()->create();
        $this->actingAs($business->owner);

        $this->get(route('onboarding.create'))->assertRedirect(route('dashboard'));
        $this->post(route('onboarding.store'), ['name' => 'Otro negocio'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, Business::count());
    }

    public function test_business_name_is_required()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('onboarding.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }
}
