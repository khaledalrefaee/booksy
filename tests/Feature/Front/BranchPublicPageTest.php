<?php

namespace Tests\Feature\Front;

use App\Models\Branch;
use App\Models\BranchImage;
use App\Models\Company;
use App\Models\Country;
use App\Models\Employee;
use App\Models\Service;
use App\Models\SocialLink;
use Tests\TestCase;

/**
 * Public branch page (/branch/{branch}): each branch shows ITS OWN contact
 * channels, services with their real images, the team, the location & hours,
 * and a work gallery that is separate from the venue photos.
 */
class BranchPublicPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    private function branch(Company $company, array $attrs = []): Branch
    {
        return Branch::factory()->create(['company_id' => $company->id] + $attrs);
    }

    // ── contact & social links ───────────────────────────────────────────────

    public function test_each_branch_shows_only_its_own_contact_channels(): void
    {
        $company = Company::factory()->create();
        $a = $this->branch($company, ['phone' => '+963911111111']);
        $b = $this->branch($company, ['phone' => '+971500000000']);

        SocialLink::syncFor($a, ['instagram' => '@glow_damascus', 'whatsapp' => '+963 922 222 222']);
        SocialLink::syncFor($b, ['instagram' => 'glow_dubai', 'whatsapp' => '+971 55 333 3333', 'facebook' => 'GlowDubai']);
        SocialLink::syncFor($company, ['instagram' => 'glow_company']); // company-level — never shown per branch

        // Contacts render as icon buttons — assert the links each page carries.
        $this->get(route('front.branch', $a))->assertOk()
            ->assertSee('https://instagram.com/glow_damascus', false)
            ->assertSee('https://wa.me/963922222222', false)
            ->assertDontSee('glow_dubai')
            ->assertDontSee('facebook.com/GlowDubai', false)
            ->assertDontSee('glow_company');

        $this->get(route('front.branch', $b))->assertOk()
            ->assertSee('https://instagram.com/glow_dubai', false)
            ->assertSee('https://wa.me/971553333333', false)
            ->assertSee('https://facebook.com/GlowDubai', false)
            ->assertDontSee('glow_damascus');
    }

    public function test_a_branch_without_contact_data_renders_no_empty_buttons(): void
    {
        $branch = $this->branch(Company::factory()->create(), ['phone' => null]);

        $this->get(route('front.branch', $branch))->assertOk()
            ->assertDontSee('class="br-contacts"', false)
            ->assertDontSee('wa.me', false)
            ->assertDontSee('instagram.com', false);

        $this->assertSame([], $branch->publicContacts());
    }

    public function test_whatsapp_numbers_are_normalised_for_wa_me(): void
    {
        $syria = Country::factory()->create(['dial_code' => '+963']);
        $branch = $this->branch(Company::factory()->create(), ['country_id' => $syria->id]);

        // Spaces, "+" and the 00 prefix are cleaned; a local 0… number gets the dial code.
        $this->assertSame('963912345678', SocialLink::internationalDigits('+963 912 345 678'));
        $this->assertSame('963912345678', SocialLink::internationalDigits('00963912345678'));
        $this->assertSame('963912345678', SocialLink::internationalDigits('0912345678', '+963'));

        SocialLink::syncFor($branch, ['whatsapp' => '0912 345 678'], '+963');
        $wa = collect($branch->fresh()->publicContacts())->firstWhere('platform', 'whatsapp');

        $this->assertSame('https://wa.me/963912345678', $wa['href']);
        $this->assertSame('+963912345678', $wa['value']);
    }

    // ── services, team, gallery ──────────────────────────────────────────────

    public function test_services_show_their_own_image_only_when_they_have_one(): void
    {
        $branch = $this->branch(Company::factory()->create());
        Service::factory()->create(['branch_id' => $branch->id, 'name_en' => 'With Photo', 'image_path' => 'services/cut.jpg', 'is_active' => true, 'is_bookable_online' => true]);
        Service::factory()->create(['branch_id' => $branch->id, 'name_en' => 'No Photo', 'image_path' => null, 'is_active' => true, 'is_bookable_online' => true]);

        $html = $this->get(route('front.branch', $branch))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="br-svc-img"'));
        $this->assertStringContainsString('storage/services/cut.jpg', $html);
    }

    public function test_the_team_tab_shows_public_details_only(): void
    {
        $branch = $this->branch(Company::factory()->create());
        Employee::factory()->create([
            'company_id' => $branch->company_id, 'branch_id' => $branch->id,
            'name_en' => 'Lina Stylist', 'email' => 'lina.private@example.com', 'phone' => '+963999000111', 'is_active' => true,
        ]);

        $this->get(route('front.branch', $branch))->assertOk()
            ->assertSee('id="br-team"', false)
            ->assertSee('Lina Stylist')
            ->assertDontSee('lina.private@example.com')
            ->assertDontSee('+963999000111');
    }

    public function test_work_photos_have_their_own_gallery_separate_from_venue_photos(): void
    {
        $branch = $this->branch(Company::factory()->create());
        $branch->images()->create(['path' => 'branches/x/place-1.webp', 'type' => BranchImage::TYPE_PLACE, 'status' => BranchImage::STATUS_APPROVED, 'is_cover' => true]);
        $branch->images()->create(['path' => 'branches/x/work-1.webp', 'type' => BranchImage::TYPE_WORK, 'status' => BranchImage::STATUS_APPROVED]);
        $branch->images()->create(['path' => 'branches/x/work-2.webp', 'type' => BranchImage::TYPE_WORK, 'status' => BranchImage::STATUS_APPROVED]);
        $branch->images()->create(['path' => 'branches/x/work-pending.webp', 'type' => BranchImage::TYPE_WORK, 'status' => BranchImage::STATUS_PENDING]);

        $html = $this->get(route('front.branch', $branch))->assertOk()->getContent();

        // Only approved work photos, as gallery tiles; pending never leaks.
        $this->assertSame(2, substr_count($html, 'data-work="'));
        $this->assertStringNotContainsString('work-pending.webp', $html);

        // The hero shows the venue photo; the work grid doesn't contain it.
        $grid = substr($html, strpos($html, 'id="br-work-grid"'));
        $grid = substr($grid, 0, strpos($grid, '</div>'));
        $this->assertStringNotContainsString('place-1', $grid);
        $this->assertStringContainsString('place-1.webp', $html);
    }

    public function test_the_work_gallery_is_hidden_when_there_are_no_work_photos(): void
    {
        $branch = $this->branch(Company::factory()->create());

        $this->get(route('front.branch', $branch))->assertOk()
            ->assertDontSee('id="br-work"', false)
            ->assertDontSee('data-target="br-work"', false);
    }

    public function test_opening_hours_use_the_branch_clock_style_and_week_start(): void
    {
        $branch = $this->branch(Company::factory()->create(), ['time_format' => '24h', 'first_day_of_week' => 6]);
        foreach (range(0, 6) as $d) {
            $branch->workingHours()->create(['day_of_week' => $d, 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:30']);
        }

        $html = $this->get(route('front.branch', $branch))->assertOk()->getContent();
        $hours = substr($html, strpos($html, 'class="br-hours"'));

        $this->assertStringContainsString('09:00 – 18:30', $hours);
        $this->assertStringNotContainsString('PM', substr($hours, 0, 2000));
        // Week starts on Saturday: it is the first day row.
        $this->assertLessThan(strpos($hours, 'Sunday'), strpos($hours, 'Saturday'));
    }
}
