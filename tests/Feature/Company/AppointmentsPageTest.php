<?php

namespace Tests\Feature\Company;

use App\Models\Branch;
use App\Models\Company;
use Tests\TestCase;

/**
 * Smoke coverage for the appointments workspace.
 *
 * The page inlines its whole front end, so a render test is the cheapest
 * guard that exists: it catches missing routes, blade errors and a config
 * bridge that stops matching the JS that reads it — the class of mistake
 * that has repeatedly shipped here because nobody could load the page.
 */
class AppointmentsPageTest extends TestCase
{
    private function company(): Company
    {
        $company = Company::factory()->create();
        Branch::factory()->create(['company_id' => $company->id]);

        return $company;
    }

    public function test_the_appointments_page_renders_for_a_company(): void
    {
        $company = $this->company();

        $this->actingAs($company, 'company')
            ->get(route('company.appointments.index'))
            ->assertOk();
    }

    public function test_it_ships_the_javascript_the_page_depends_on(): void
    {
        $company = $this->company();

        $html = $this->actingAs($company, 'company')
            ->get(route('company.appointments.index'))
            ->assertOk()
            ->getContent();

        // the calendar library and the page's own script must both be present
        $this->assertStringContainsString('fullcalendar', $html);
        $this->assertStringContainsString('booksy-calendar', $html);
    }

    public function test_guests_cannot_reach_it(): void
    {
        $this->get(route('company.appointments.index'))->assertRedirect();
    }
}
