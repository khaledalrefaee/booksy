<?php

namespace Tests\Feature\Platform;

use App\Models\Owner;
use App\Models\PlatformLink;
use App\Models\PlatformSetting;
use App\Services\Platform\PlatformSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Owner → Settings: access control, validation/sanitising, persistence + cache,
 * the public site wiring, the maintenance mode and the public API.
 */
class PlatformSettingsTest extends TestCase
{
    private function owner(string $role = 'super_admin'): Owner
    {
        $o = Owner::factory()->create(['role' => $role]);
        $this->actingAs($o, 'owner');

        return $o;
    }

    private function ps(): PlatformSettings
    {
        return app(PlatformSettings::class);
    }

    private function save(string $tab, array $data)
    {
        return $this->from(route('owner.settings.index'))->put(route('owner.settings.update', $tab), $data);
    }

    /** Reset the in-memory snapshot so assertions read what the request really stored. */
    private function fresh(): PlatformSettings
    {
        $this->ps()->forget();

        return $this->ps();
    }

    /* ───────────────────────── access ───────────────────────── */

    public function test_settings_page_renders_all_six_tabs_for_the_owner(): void
    {
        $this->owner();

        $res = $this->get(route('owner.settings.index'))->assertOk();
        foreach (['general', 'support', 'links', 'releases', 'visibility', 'system'] as $tab) {
            $res->assertSee('id="ps-panel-'.$tab.'"', false);
        }
        // Sidebar item
        $res->assertSee(route('owner.settings.index'), false);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('owner.settings.index'))->assertRedirect();
        $this->put(route('owner.settings.update', 'general'), [])->assertRedirect();
    }

    public function test_staff_without_the_permission_cannot_view_or_change_settings(): void
    {
        $this->owner('support');   // has dashboard access, not platform.settings.manage

        $this->get(route('owner.settings.index'))->assertForbidden();
        $this->put(route('owner.settings.update', 'general'), ['general' => ['name_en' => 'Hacked']])->assertForbidden();
        $this->put(route('owner.settings.links'), ['links' => []])->assertForbidden();

        $this->assertNull($this->fresh()->string('general.name_en'));
    }

    public function test_settings_link_is_hidden_from_the_sidebar_without_permission(): void
    {
        $this->owner('support');

        $this->get(route('owner.dashboard'))->assertOk()->assertDontSee(route('owner.settings.index'), false);
    }

    public function test_admin_can_edit_settings_but_not_maintenance(): void
    {
        $this->owner('admin');

        $this->save('general', ['general' => ['name_en' => 'GlowRez']])->assertSessionHasNoErrors();
        $this->assertSame('GlowRez', $this->fresh()->string('general.name_en'));

        // Crafted request carrying the maintenance switch → refused, nothing changes.
        $this->put(route('owner.settings.update', 'system'), ['system' => ['mode' => 'maintenance']])->assertForbidden();
        $this->assertSame('normal', $this->fresh()->maintenance()['mode']);

        // Other system settings stay editable by them.
        $this->save('system', ['system' => ['show_version' => '1', 'update_notice' => '0']])->assertSessionHasNoErrors();
        $this->assertTrue($this->fresh()->bool('system.show_version'));
    }

    public function test_unknown_tab_is_not_routable(): void
    {
        $this->owner();

        $this->put('/owner/settings/payments', [])->assertNotFound();
    }

    /* ───────────────────────── saving ───────────────────────── */

    public function test_general_tab_saves_and_reloads_after_refresh(): void
    {
        $this->owner();

        $this->save('general', ['general' => [
            'name_ar' => 'غلوريز', 'name_en' => 'GlowRez', 'email' => 'hello@glowrez.com',
            'phone' => '+963 944 123-456', 'website_url' => 'https://glowrez.com', 'default_locale' => 'en',
        ]])->assertSessionHasNoErrors()->assertSessionHas('success');

        $ps = $this->fresh();
        $this->assertSame('غلوريز', $ps->string('general.name_ar'));
        $this->assertSame('+963944123456', $ps->string('general.phone'));   // normalised E.164

        $this->get(route('owner.settings.index'))->assertSee('value="hello@glowrez.com"', false);
    }

    public function test_html_is_stripped_and_output_is_escaped(): void
    {
        $this->owner();

        $this->save('general', ['general' => ['description_en' => '<b>Hi</b><script>alert(1)</script> there']])->assertSessionHasNoErrors();

        $stored = $this->fresh()->string('general.description_en');
        $this->assertStringNotContainsString('<', $stored);
        $this->assertStringNotContainsString('script', strtolower(str_replace('alert(1)', '', $stored)));
    }

    public function test_saving_again_with_the_same_values_reports_no_change(): void
    {
        $this->owner();
        $this->save('general', ['general' => ['name_en' => 'GlowRez']]);

        $this->save('general', ['general' => ['name_en' => 'GlowRez']])->assertSessionHas('info');
    }

    public function test_clearing_a_field_removes_the_stored_value(): void
    {
        $this->owner();
        $this->save('general', ['general' => ['company_name_en' => 'GlowRez Ltd']]);
        $this->save('general', ['general' => ['company_name_en' => '']]);

        $this->assertNull($this->fresh()->string('general.company_name_en'));
        $this->assertDatabaseMissing('platform_settings', ['key' => 'general.company_name_en']);
    }

    public function test_settings_changes_are_audited(): void
    {
        $this->owner();
        $this->save('general', ['general' => ['name_en' => 'GlowRez']]);

        $this->assertDatabaseHas('owner_audit_logs', ['action' => 'platform-settings.general.updated']);
    }

    public function test_cache_is_invalidated_after_saving(): void
    {
        $this->owner();
        $this->save('general', ['general' => ['name_en' => 'One']]);
        $this->assertSame('One', $this->ps()->string('general.name_en'));   // warms the cache

        $this->save('general', ['general' => ['name_en' => 'Two']]);

        $this->assertSame('Two', $this->ps()->string('general.name_en'));
    }

    /* ───────────────────────── validation ───────────────────────── */

    public function test_unsafe_and_malformed_values_are_rejected(): void
    {
        $this->owner();

        $this->save('support', ['support' => [
            'email'       => 'not-an-email',
            'phone'       => '0944123456',                     // no country code
            'faq_url'     => 'javascript:alert(1)',
            'help_center_url' => '//evil.test/x',
            'contact_url' => 'https://user:pass@evil.test/',
        ]])->assertSessionHasErrors(['support.email', 'support.phone', 'support.faq_url', 'support.help_center_url', 'support.contact_url']);

        $this->assertNull($this->fresh()->string('support.faq_url'));
    }

    public function test_relative_paths_are_allowed_only_where_the_schema_says_so(): void
    {
        $this->owner();

        $this->save('support', ['support' => ['help_center_url' => '/help']])->assertSessionHasNoErrors();
        $this->save('general', ['general' => ['website_url' => '/help']])->assertSessionHasErrors('general.website_url');
    }

    public function test_hours_need_both_ends_and_a_valid_format(): void
    {
        $this->owner();

        $this->save('support', ['support' => ['hours_from' => '09:00']])->assertSessionHasErrors('support.hours_to');
        $this->save('support', ['support' => ['hours_from' => '9am', 'hours_to' => '17:00']])->assertSessionHasErrors('support.hours_from');
        $this->save('support', ['support' => ['hours_from' => '09:00', 'hours_to' => '17:00', 'days' => ['sat', 'mon']]])->assertSessionHasNoErrors();

        $this->assertSame(['sat', 'mon'], $this->fresh()->get('support.days'));
        $this->assertSame('09:00 – 17:00 · '.__('Sat').' · '.__('Mon'), $this->fresh()->supportHoursLabel());
    }

    public function test_a_tab_cannot_write_another_tabs_settings(): void
    {
        $this->owner();

        $this->save('general', ['general' => ['name_en' => 'X'], 'system' => ['mode' => 'maintenance'], 'support' => ['email' => 'a@b.co']]);

        $this->assertSame('normal', $this->fresh()->maintenance()['mode']);
        $this->assertNull($this->fresh()->string('support.email'));
    }

    /* ───────────────────────── logo ───────────────────────── */

    public function test_logo_upload_replaces_the_old_file_and_validates_type_and_size(): void
    {
        Storage::fake('public');
        $this->owner();

        $this->save('general', ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)])->assertSessionHasNoErrors();
        $first = $this->fresh()->string('general.logo');
        Storage::disk('public')->assertExists($first);

        $this->save('general', ['logo' => UploadedFile::fake()->image('logo2.webp', 300, 300)])->assertSessionHasNoErrors();
        $second = $this->fresh()->string('general.logo');
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);

        // SVG (script carrier), oversized and tiny images are refused.
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->save('general', ['logo' => $svg])->assertSessionHasErrors('logo');
        $this->save('general', ['logo' => UploadedFile::fake()->image('big.png', 300, 300)->size(3000)])->assertSessionHasErrors('logo');
        $this->save('general', ['logo' => UploadedFile::fake()->image('tiny.png', 20, 20)])->assertSessionHasErrors('logo');
        $this->assertSame($second, $this->fresh()->string('general.logo'));

        $this->save('general', ['remove_logo' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($this->fresh()->string('general.logo'));
        Storage::disk('public')->assertMissing($second);
    }

    /* ───────────────────────── links ───────────────────────── */

    private function row(array $over = []): array
    {
        return array_merge(['id' => null, 'kind' => 'custom', 'label' => 'Blog', 'url' => 'https://blog.glowrez.com',
            'icon' => 'book-open', 'is_active' => '1', 'show_public' => '1'], $over);
    }

    public function test_links_can_be_added_edited_reordered_and_deleted(): void
    {
        $this->owner();

        $this->put(route('owner.settings.links'), ['links' => [
            $this->row(),
            $this->row(['kind' => 'instagram', 'label' => '', 'url' => 'https://instagram.com/glowrez', 'icon' => null]),
        ]])->assertSessionHasNoErrors();

        $links = PlatformLink::orderBy('sort_order')->get();
        $this->assertSame(['custom', 'instagram'], $links->pluck('kind')->all());
        $this->assertSame('Instagram', $links[1]->label);               // preset keeps its canonical name
        $this->assertNull($links[1]->icon);

        // Reorder + edit + delete in one save.
        $this->put(route('owner.settings.links'), ['links' => [
            $this->row(['id' => $links[1]->id, 'kind' => 'instagram', 'label' => '', 'url' => 'https://instagram.com/glowrez.sy', 'icon' => null]),
        ]])->assertSessionHasNoErrors();

        $this->assertSame(1, PlatformLink::count());
        $this->assertSame('https://instagram.com/glowrez.sy', PlatformLink::first()->url);
    }

    public function test_link_validation_rejects_bad_hosts_empty_urls_and_duplicates(): void
    {
        $this->owner();

        $this->put(route('owner.settings.links'), ['links' => [
            $this->row(['kind' => 'instagram', 'label' => '', 'url' => 'https://facebook.com/x', 'icon' => null]),
        ]])->assertSessionHasErrors('links.0.url');

        $this->put(route('owner.settings.links'), ['links' => [$this->row(['url' => ''])]])->assertSessionHasErrors('links.0.url');
        $this->put(route('owner.settings.links'), ['links' => [$this->row(['url' => 'javascript:alert(1)'])]])->assertSessionHasErrors('links.0.url');
        $this->put(route('owner.settings.links'), ['links' => [$this->row(['url' => 'ftp://files.test/a'])]])->assertSessionHasErrors('links.0.url');
        $this->put(route('owner.settings.links'), ['links' => [$this->row(['label' => ''])]])->assertSessionHasErrors('links.0.label');

        $ig = $this->row(['kind' => 'instagram', 'label' => '', 'url' => 'https://instagram.com/a', 'icon' => null]);
        $this->put(route('owner.settings.links'), ['links' => [$ig, $ig]])->assertSessionHasErrors('links.1.kind');

        $this->assertSame(0, PlatformLink::count());
    }

    public function test_link_names_are_sanitised(): void
    {
        $this->owner();

        $this->put(route('owner.settings.links'), ['links' => [$this->row(['label' => '<img src=x onerror=alert(1)>Blog'])]])->assertSessionHasNoErrors();

        $this->assertSame('Blog', PlatformLink::first()->label);
    }

    /* ───────────────────────── public site ───────────────────────── */

    private function link(array $over = []): PlatformLink
    {
        return PlatformLink::create(array_merge(['kind' => 'custom', 'label' => 'Blog', 'url' => 'https://blog.glowrez.com',
            'icon' => 'link', 'is_active' => true, 'show_public' => true, 'sort_order' => 0], $over));
    }

    public function test_footer_shows_only_active_visible_links_in_order(): void
    {
        $this->link(['label' => 'Second', 'url' => 'https://second.test.com', 'sort_order' => 2]);
        $this->link(['label' => 'First', 'url' => 'https://first.test.com', 'sort_order' => 1]);
        $this->link(['label' => 'Inactive', 'url' => 'https://inactive.test.com', 'is_active' => false]);
        $this->link(['label' => 'Hidden', 'url' => 'https://hidden.test.com', 'show_public' => false]);
        $this->link(['label' => 'Tampered', 'url' => 'javascript:alert(1)']);       // never printable, even if it got into the DB
        $this->link(['label' => 'Empty', 'url' => '']);

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('https://first.test.com', $html);
        $this->assertStringContainsString('https://second.test.com', $html);
        $this->assertLessThan(strpos($html, 'https://second.test.com'), strpos($html, 'https://first.test.com'));
        foreach (['inactive.test.com', 'hidden.test.com', 'javascript:alert', 'aria-label="Empty"'] as $absent) {
            $this->assertStringNotContainsString($absent, $html);
        }
        $this->assertStringNotContainsString('href="#" aria-label', $html);   // the old dead placeholders are gone
    }

    public function test_hiding_social_links_site_wide_keeps_them_stored(): void
    {
        $this->link(['url' => 'https://blog.glowrez.com']);
        $this->ps()->save(['visibility.show_social' => '0']);

        $this->get('/contact')->assertDontSee('blog.glowrez.com', false);
        $this->assertSame(1, PlatformLink::count());
    }

    public function test_footer_uses_official_contact_copyright_and_legal_links(): void
    {
        $this->ps()->save([
            'general.email' => 'hello@glowrez.com', 'general.phone' => '+963944123456',
            'visibility.copyright_en' => '© {year} GlowRez Ltd.', 'visibility.copyright_ar' => '© {year} غلوريز',
            'visibility.privacy_url' => 'https://legal.glowrez.com/privacy', 'visibility.terms_url' => '/terms-of-use',
            'visibility.footer_legal_en' => 'GlowRez is a registered mark.', 'visibility.footer_legal_ar' => 'غلوريز علامة مسجلة.',
            'system.show_version' => '1', 'release.web_version' => '1.4.2',
        ]);

        $html = $this->withSession(['locale' => 'en'])->get('/contact')->getContent();

        $this->assertStringContainsString('mailto:hello@glowrez.com', $html);
        $this->assertStringContainsString('tel:+963944123456', $html);
        $this->assertStringContainsString('© '.date('Y').' GlowRez Ltd.', $html);
        $this->assertStringContainsString('https://legal.glowrez.com/privacy', $html);
        $this->assertStringContainsString('/terms-of-use', $html);
        $this->assertStringContainsString('GlowRez is a registered mark.', $html);
        $this->assertStringContainsString('v1.4.2', $html);
    }

    public function test_official_contact_switches_hide_the_values_but_keep_them(): void
    {
        $this->ps()->save(['general.email' => 'hello@glowrez.com', 'general.phone' => '+963944123456',
            'visibility.show_official_email' => '0', 'visibility.show_official_phone' => '0']);

        $this->get('/contact')->assertDontSee('mailto:hello@glowrez.com', false)->assertDontSee('tel:+963944123456', false);
        $this->assertSame('hello@glowrez.com', $this->fresh()->string('general.email'));
    }

    public function test_contact_page_shows_support_channels_and_never_the_internal_ones(): void
    {
        $this->ps()->save([
            'support.email' => 'support@glowrez.com', 'support.phone' => '+963112223334', 'support.whatsapp' => '+963944123456',
            'support.help_center_url' => '/help', 'support.internal_email' => 'secret-team@glowrez.com',
            'support.internal_phone' => '+963999000111', 'support.internal_notes' => 'ESCALATE-TO-NIGHT-SHIFT',
            'support.hours_from' => '09:00', 'support.hours_to' => '17:00', 'support.days' => ['sat'],
            'support.default_message_en' => 'We reply within a day.',
        ]);

        $html = $this->withSession(['locale' => 'en'])->get('/contact')->getContent();

        $this->assertStringContainsString('mailto:support@glowrez.com', $html);
        $this->assertStringContainsString('tel:+963112223334', $html);
        $this->assertStringContainsString('https://wa.me/963944123456', $html);
        $this->assertStringContainsString('09:00 – 17:00', $html);
        $this->assertStringContainsString('We reply within a day.', $html);
        foreach (['secret-team@glowrez.com', '+963999000111', 'ESCALATE-TO-NIGHT-SHIFT'] as $internal) {
            $this->assertStringNotContainsString($internal, $html);
        }
        // the legacy hard-coded address is replaced once a support email exists
        $this->assertStringNotContainsString('info@glowrez.com', $html);
    }

    public function test_hiding_a_support_channel_hides_it_publicly_without_deleting_it(): void
    {
        $this->ps()->save(['support.email' => 'support@glowrez.com', 'support.email_visible' => '0',
            'support.phone' => '+963112223334']);

        $this->get('/contact')->assertDontSee('support@glowrez.com')->assertDontSee('info@glowrez.com')
            ->assertSee('tel:+963112223334', false);
        $this->assertSame('support@glowrez.com', $this->fresh()->string('support.email'));
    }

    public function test_master_switch_hides_every_support_channel(): void
    {
        $this->ps()->save(['support.email' => 'support@glowrez.com', 'visibility.show_support_links' => '0']);

        $this->get('/contact')->assertDontSee('support@glowrez.com')->assertDontSee('info@glowrez.com');
    }

    public function test_contact_form_goes_to_the_internal_support_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->ps()->save(['support.internal_email' => 'team@glowrez.com']);

        $this->post('/contact', ['name' => 'A', 'email' => 'a@b.co', 'subject' => 'Hi', 'message' => 'Hello there'])->assertRedirect();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactMessageMail::class, fn ($m) => $m->hasTo('team@glowrez.com'));
    }

    public function test_default_language_applies_until_the_visitor_chooses(): void
    {
        $this->ps()->save(['general.default_locale' => 'en']);

        $this->get('/contact')->assertSee('lang="en"', false);
        $this->withSession(['locale' => 'ar'])->get('/contact')->assertSee('lang="ar"', false);
    }

    public function test_pre_launch_pages_prefer_platform_whatsapp_and_instagram(): void
    {
        config(['leads.whatsapp_number' => null, 'leads.instagram_url' => null]);
        $this->ps()->save(['support.whatsapp' => '+44 7700 900123']);
        $this->link(['kind' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/glowrez']);
        $this->ps()->forget();

        $this->get('/welcome')->assertSee('https://wa.me/447700900123', false)->assertSee('https://instagram.com/glowrez', false);
    }

    /* ───────────────────────── releases ───────────────────────── */

    public function test_available_app_needs_a_store_link_and_version(): void
    {
        $this->owner();

        $this->save('releases', ['release' => ['android_status' => 'available']])
            ->assertSessionHasErrors(['release.android_version', 'release.android_play_url']);
        $this->save('releases', ['release' => ['ios_status' => 'available', 'ios_version' => '1.0.0']])
            ->assertSessionHasErrors('release.ios_store_url');

        $this->save('releases', ['release' => ['android_status' => 'available', 'android_version' => '1.0.0', 'android_build' => 10,
            'android_play_url' => 'https://play.google.com/store/apps/details?id=com.glowrez']])->assertSessionHasNoErrors();
    }

    public function test_release_links_must_be_https_and_on_the_right_store(): void
    {
        $this->owner();

        $this->save('releases', ['release' => ['android_apk_url' => 'http://files.glowrez.com/app.apk']])->assertSessionHasErrors('release.android_apk_url');
        $this->save('releases', ['release' => ['android_play_url' => 'https://example.com/app']])->assertSessionHasErrors('release.android_play_url');
        $this->save('releases', ['release' => ['ios_store_url' => 'https://play.google.com/x']])->assertSessionHasErrors('release.ios_store_url');
    }

    public function test_version_formats_and_minimum_rules(): void
    {
        $this->owner();

        $this->save('releases', ['release' => ['web_version' => 'latest']])->assertSessionHasErrors('release.web_version');
        $this->save('releases', ['release' => ['web_version' => '1.2.0', 'web_min_version' => '1.3.0']])->assertSessionHasErrors('release.web_min_version');
        $this->save('releases', ['release' => ['android_build' => 'abc']])->assertSessionHasErrors('release.android_build');
        $this->save('releases', ['release' => ['web_version' => '1.2.0', 'web_min_version' => '1.0.0', 'web_updated_on' => '2026-10-01']])->assertSessionHasNoErrors();
    }

    public function test_unpublished_apps_never_expose_download_links(): void
    {
        $this->ps()->save([
            'release.android_status' => 'in_development', 'release.android_version' => '0.9.0',
            'release.android_play_url' => 'https://play.google.com/store/apps/details?id=x', 'release.android_apk_url' => 'https://files.glowrez.com/a.apk',
            'release.ios_status' => 'unavailable', 'release.ios_store_url' => 'https://apps.apple.com/app/x',
        ]);

        $data = $this->getJson('/api/platform/config')->assertOk()->json('data.releases');

        $this->assertNull($data['android']['google_play_url']);
        $this->assertNull($data['android']['apk_url']);
        $this->assertNull($data['ios']['app_store_url']);
        $this->assertSame('in_development', $data['android']['status']);
    }

    public function test_available_app_exposes_its_links_and_update_flags(): void
    {
        $this->ps()->save([
            'release.android_status' => 'available', 'release.android_version' => '2.0.0', 'release.android_min_version' => '1.5.0',
            'release.android_play_url' => 'https://play.google.com/store/apps/details?id=x', 'system.update_notice' => '1',
        ]);

        $res = $this->getJson('/api/platform/config?platform=android&version=1.4.0')->assertOk();
        $res->assertJsonPath('data.releases.android.google_play_url', 'https://play.google.com/store/apps/details?id=x');
        $res->assertJsonPath('data.update.update_required', true);
        $res->assertJsonPath('data.update.update_available', true);

        $this->getJson('/api/platform/config?platform=android&version=2.0.0')
            ->assertJsonPath('data.update.update_required', false)->assertJsonPath('data.update.update_available', false);
    }

    public function test_update_notice_switch_controls_update_available(): void
    {
        $this->ps()->save(['release.android_version' => '2.0.0', 'system.update_notice' => '0']);

        $this->getJson('/api/platform/config?platform=android&version=1.0.0')->assertJsonPath('data.update.update_available', false);
    }

    /* ───────────────────────── public API ───────────────────────── */

    public function test_public_api_follows_visibility_and_never_leaks_internal_data(): void
    {
        $this->ps()->save([
            'general.name_en' => 'GlowRez', 'general.email' => 'hello@glowrez.com',
            'support.email' => 'support@glowrez.com', 'support.phone' => '+963112223334', 'support.phone_visible' => '0',
            'support.internal_email' => 'secret-team@glowrez.com', 'support.internal_notes' => 'ESCALATE-TO-NIGHT-SHIFT',
        ]);
        $this->link();

        $res = $this->getJson('/api/platform/config')->assertOk()->assertJsonPath('status', true);
        $body = $res->getContent();

        $this->assertStringContainsString('support@glowrez.com', $body);
        $this->assertStringNotContainsString('+963112223334', $body);          // hidden channel
        foreach (['secret-team@glowrez.com', 'ESCALATE-TO-NIGHT-SHIFT', 'internal'] as $leak) {
            $this->assertStringNotContainsString($leak, $body);
        }
        $res->assertJsonPath('data.social_links.0.url', 'https://blog.glowrez.com');
    }

    /* ───────────────────────── maintenance ───────────────────────── */

    private function startMaintenance(array $extra = []): void
    {
        $this->ps()->save(array_merge(['system.mode' => 'maintenance', 'system.message_en' => 'Back at noon.'], $extra));
    }

    public function test_visitors_get_the_maintenance_page_and_api_visitors_a_json_503(): void
    {
        $this->startMaintenance(['system.status_page_url' => 'https://status.glowrez.com']);

        $this->withSession(['locale' => 'en'])->get('/')->assertStatus(503)->assertSee('Back at noon.')
            ->assertSee('https://status.glowrez.com', false)->assertHeader('Retry-After');
        $this->get('/contact')->assertStatus(503);
        $this->postJson('/api/customer/send-code', ['phone' => '123'])->assertStatus(503)->assertJsonPath('status', false);
    }

    public function test_paused_mode_has_its_own_wording(): void
    {
        $this->ps()->save(['system.mode' => 'paused']);

        $this->withSession(['locale' => 'en'])->get('/')->assertStatus(503)->assertSee('temporarily paused');
    }

    public function test_the_owner_console_stays_reachable_for_everyone_and_staff_can_browse(): void
    {
        $this->startMaintenance();

        $this->get(route('owner.login'))->assertOk();          // team can always sign in again
        $this->get(route('csrf.token'))->assertOk();
        $this->getJson('/api/platform/config')->assertOk()->assertJsonPath('data.status.mode', 'maintenance');

        $this->owner();
        $this->get('/')->assertOk();                             // authorised staff can still see the site
        $this->get(route('owner.settings.index'))->assertOk();
    }

    public function test_a_signed_in_user_without_dashboard_access_is_not_exempt(): void
    {
        $this->startMaintenance();
        $this->actingAs(Owner::factory()->create(['role' => 'field_sales']), 'owner');   // no owner-dashboard.view

        $this->get('/')->assertStatus(503);
    }

    public function test_company_dashboards_follow_the_scope(): void
    {
        $this->startMaintenance();                                  // scope: public (default)
        $this->get(route('company.login'))->assertOk();

        $this->ps()->save(['system.scope' => 'all']);
        $this->get(route('company.login'))->assertStatus(503);
        $this->get(route('owner.login'))->assertOk();               // still never the console
    }

    public function test_turning_maintenance_off_reopens_the_site(): void
    {
        $this->startMaintenance();
        $this->get('/')->assertStatus(503);

        $this->owner();
        $this->save('system', ['system' => ['mode' => 'normal']])->assertSessionHasNoErrors();
        auth('owner')->logout();

        $this->get('/')->assertOk();
        $this->assertDatabaseHas('owner_audit_logs', ['action' => 'platform-settings.maintenance.changed']);
    }

    public function test_super_admin_can_switch_maintenance_through_the_form(): void
    {
        $this->owner();

        $this->save('system', ['system' => ['mode' => 'maintenance', 'scope' => 'all', 'message_en' => 'Soon']])->assertSessionHasNoErrors();

        $m = $this->fresh()->maintenance();
        $this->assertSame(['maintenance', 'all', 'Soon'], [$m['mode'], $m['scope'], $m['message']]);

        $this->save('system', ['system' => ['mode' => 'broken']])->assertSessionHasErrors('system.mode');
    }

    public function test_settings_fall_back_to_defaults_when_the_store_is_unreachable(): void
    {
        // Simulates "deployed before migrating" / a database hiccup: every read throws.
        $good = config('database.default');
        config(['database.connections.broken' => array_merge(config("database.connections.$good"), ['host' => '127.0.0.1', 'port' => 1, 'database' => 'nope']),
                'database.default' => 'broken']);
        \Illuminate\Support\Facades\DB::purge('broken');
        $this->ps()->forget();

        try {
            $this->assertSame('normal', $this->ps()->maintenance()['mode']);
            $this->assertSame([], $this->ps()->publicLinks());
            $this->assertNull($this->ps()->string('general.name_en'));
        } finally {
            config(['database.default' => $good]);
            \Illuminate\Support\Facades\DB::purge('broken');
            $this->ps()->forget();
        }
    }
}
