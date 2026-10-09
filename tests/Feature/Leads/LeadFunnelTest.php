<?php

namespace Tests\Feature\Leads;

use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadVisit;
use App\Models\Owner;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Public pre-launch funnel: attribution capture, the interest form (validation,
 * spam traps, duplicate handling) and the notification email.
 */
class LeadFunnelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['leads.notification_email' => 'team@example.test']);
    }

    /** A form token minted `$age` seconds ago (the form refuses anything faster than min_fill_seconds). */
    private function token(int $age = 30): string
    {
        return Crypt::encryptString((string) (time() - $age));
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            '_t'                 => $this->token(),
            'full_name'          => 'Ahmad Ali',
            'phone'              => '0944 123 456',
            'whatsapp_same'      => '1',
            'email'              => 'Ahmad@Example.test',
            'business_name'      => 'Glow Beauty Center',
            'business_type'      => 'beauty_center',
            'city'               => 'damascus',
            'area'               => 'Mazzeh',
            'number_of_branches' => 3,
            'interests'          => ['bookings', 'employees', 'reports'],
        ], $over);
    }

    private function submit(array $over = [], bool $json = true)
    {
        return $json
            ? $this->postJson(route('leads.store'), $this->payload($over))
            : $this->post(route('leads.store'), $this->payload($over));
    }

    /* ── pages ──────────────────────────────────────────────────────────── */

    public function test_funnel_pages_render_without_login(): void
    {
        $this->get('/welcome')->assertOk()->assertSee('/join', false)->assertSee('/for-business', false);
        $this->get('/join')->assertOk()->assertSee('name="hp_extra"', false)->assertSee('name="_t"', false);
        $this->get('/for-business')->assertOk()->assertSee('/join', false);
    }

    public function test_whatsapp_and_instagram_are_hidden_until_configured(): void
    {
        config(['leads.whatsapp_number' => null, 'leads.instagram_url' => null]);
        $this->get('/welcome')->assertDontSee('wa.me', false)->assertDontSee('instagram.com', false);

        config(['leads.whatsapp_number' => '963944000111', 'leads.instagram_url' => 'https://instagram.com/glowrez']);
        $this->get('/welcome')->assertSee('https://wa.me/963944000111', false)->assertSee('https://instagram.com/glowrez', false);
    }

    public function test_thanks_page_without_a_submission_goes_back_to_the_form(): void
    {
        $this->get('/join/thanks')->assertRedirect(route('leads.join'));
    }

    /* ── attribution ────────────────────────────────────────────────────── */

    public function test_tracking_parameters_are_saved_with_the_lead(): void
    {
        $this->get('/welcome?source=IG&campaign=Launch01&utm_medium=story&utm_content=reel-3')->assertOk();
        $this->get('/join')->assertOk();   // a plain link later must NOT erase the first touch

        $this->submit()->assertOk();

        $lead = Lead::firstOrFail();
        $this->assertSame('instagram', $lead->source);          // "IG" normalised
        $this->assertSame('launch01', $lead->campaign);
        $this->assertSame('story', $lead->utm_medium);
        $this->assertSame('reel-3', $lead->utm_content);
        $this->assertSame('/welcome', $lead->landing_page);
    }

    public function test_a_newer_tracking_link_overrides_the_stored_one(): void
    {
        $this->get('/join?source=instagram&campaign=a');
        $this->get('/join?source=whatsapp&campaign=b');
        $this->submit();

        $this->assertSame('whatsapp', Lead::first()->source);
        $this->assertSame('b', Lead::first()->campaign);
    }

    public function test_source_defaults_to_direct_and_unknown_values_become_other(): void
    {
        $this->submit();
        $this->assertSame('direct', Lead::first()->source);

        $this->get('/join?source=totally-new-thing');
        $this->submit(['phone' => '0955 000 111', 'email' => null]);
        $this->assertSame('other', Lead::where('phone_normalized', '963955000111')->first()->source);
    }

    public function test_tracking_input_is_sanitised(): void
    {
        $this->get('/join?source=instagram&campaign='.urlencode('<script>alert(1)</script>Spring Sale!'));
        $this->submit();

        $this->assertSame('scriptalert1scriptspringsale', Lead::first()->campaign);
    }

    public function test_visits_are_counted_once_per_visitor_and_bots_are_ignored(): void
    {
        $this->get('/join?source=qr');
        $this->get('/join?source=qr');
        $this->assertSame(1, LeadVisit::where('page', 'join')->count());

        $this->withHeader('User-Agent', 'WhatsApp/2.23 A')->get('/welcome?source=whatsapp');
        $this->assertSame(0, LeadVisit::where('page', 'welcome')->count());
    }

    /* ── saving ─────────────────────────────────────────────────────────── */

    public function test_a_valid_submission_is_saved_with_everything_the_form_collected(): void
    {
        $this->submit()->assertOk()->assertJson(['status' => true, 'data' => ['duplicate' => false]]);

        $lead = Lead::firstOrFail();
        $this->assertSame('Ahmad Ali', $lead->full_name);
        $this->assertSame('963944123456', $lead->phone_normalized);
        $this->assertSame('0944 123 456', $lead->whatsapp);          // "same number on WhatsApp"
        $this->assertSame('ahmad@example.test', $lead->email);
        $this->assertSame('beauty_center', $lead->business_type);
        $this->assertSame('damascus', $lead->city);
        $this->assertSame('Mazzeh', $lead->area);
        $this->assertSame(3, $lead->number_of_branches);
        $this->assertEqualsCanonicalizing(['bookings', 'employees', 'reports'], $lead->interests);
        $this->assertSame('new', $lead->status);
        $this->assertSame(1, $lead->interaction_count);
        $this->assertNotNull($lead->last_interaction_at);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => LeadActivity::CREATED]);
    }

    public function test_a_free_text_city_is_kept(): void
    {
        $this->submit(['city' => 'other', 'city_other' => 'Berlin'])->assertOk();
        $this->assertSame('Berlin', Lead::first()->city);

        $this->submit(['phone' => '0955 111 222', 'email' => null, 'city' => 'other', 'city_other' => ''])->assertStatus(422)->assertJsonValidationErrors('city_other');
    }

    public function test_the_form_works_without_javascript_and_lands_on_the_thank_you_page(): void
    {
        $this->submit([], false)->assertRedirect(route('leads.thanks'));
        $this->get(route('leads.thanks'))->assertOk()->assertSee('GlowRez');
        $this->assertSame(1, Lead::count());
    }

    public function test_markup_in_text_fields_is_stripped(): void
    {
        $this->submit(['full_name' => '<b>Ahmad</b> <script>x</script>', 'business_name' => "Glow\n\n  <i>Center</i>"])->assertOk();

        $lead = Lead::first();
        $this->assertStringNotContainsString('<', $lead->full_name);
        $this->assertSame('Glow Center', $lead->business_name);
    }

    public function test_validation_errors_come_back_as_json_per_field(): void
    {
        $this->postJson(route('leads.store'), ['_t' => $this->token(), 'phone' => 'abc', 'email' => 'nope', 'number_of_branches' => 500, 'business_type' => 'bogus'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'phone', 'email', 'business_name', 'business_type', 'city', 'number_of_branches']);

        $this->assertSame(0, Lead::count());
    }

    /* ── email ──────────────────────────────────────────────────────────── */

    public function test_a_new_lead_queues_the_notification_email_with_the_agreed_subject(): void
    {
        Mail::fake();
        $this->submit()->assertOk();

        Mail::assertQueued(NewLeadMail::class, function (NewLeadMail $mail) {
            return $mail->hasTo('team@example.test')
                && $mail->envelope()->subject === 'New GlowRez Lead — Beauty center — Damascus'
                && $mail->hasReplyTo('ahmad@example.test');
        });
    }

    public function test_the_email_carries_every_field_and_a_button_to_the_lead(): void
    {
        $this->submit(['campaign' => 'ignored']);
        $lead = Lead::first();

        $html = (new NewLeadMail($lead->id))->render();

        foreach (['Ahmad Ali', 'Glow Beauty Center', 'مركز تجميل', 'دمشق', 'Mazzeh', 'الحجوزات', 'الموظفين', 'التقارير', 'مباشر'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString(route('owner.leads.show', $lead), $html);
        $this->assertStringContainsString('فتح الـ Lead في لوحة التحكم', $html);
    }

    public function test_no_email_is_attempted_when_no_inbox_is_configured_but_the_lead_is_still_saved(): void
    {
        Mail::fake();
        config(['leads.notification_email' => null]);

        $this->submit()->assertOk();

        Mail::assertNothingQueued();
        $this->assertSame(1, Lead::count());
    }

    public function test_the_notification_address_can_list_several_inboxes(): void
    {
        Mail::fake();
        config(['leads.notification_email' => 'a@example.test, b@example.test; not-an-email']);

        $this->submit();

        Mail::assertQueued(NewLeadMail::class, fn ($m) => $m->hasTo('a@example.test') && $m->hasTo('b@example.test'));
    }

    /* ── duplicates ─────────────────────────────────────────────────────── */

    public function test_the_same_phone_in_any_format_updates_the_existing_lead(): void
    {
        $this->get('/join?source=instagram&campaign=launch01');
        $this->submit(['interests' => ['bookings']])->assertOk();

        $this->get('/join?source=whatsapp&campaign=retarget');
        $res = $this->submit([
            'phone' => '+963 944-123-456', 'whatsapp_same' => null, 'email' => null,
            'interests' => ['payroll'], 'business_name' => 'Glow Beauty & Spa', 'number_of_branches' => 4,
        ]);

        $res->assertOk()->assertJson(['status' => true, 'data' => ['duplicate' => true]]);
        $this->assertSame(1, Lead::count());

        $lead = Lead::first();
        $this->assertSame(2, $lead->interaction_count);
        $this->assertEqualsCanonicalizing(['bookings', 'payroll'], $lead->interests);   // merged, nothing lost
        $this->assertSame('Glow Beauty & Spa', $lead->business_name);                    // newest value wins…
        $this->assertSame('instagram', $lead->source);                                   // …but first-touch stays

        $activity = $lead->activities()->where('type', LeadActivity::RESUBMITTED)->firstOrFail();
        $this->assertSame('Glow Beauty Center', $activity->meta['changes']['business_name'][0]);   // …and the old value is in the timeline
        $this->assertSame('whatsapp', $activity->meta['source']);
        $this->assertSame(['payroll'], $activity->meta['added_interests']);
    }

    public function test_a_repeat_submission_still_alerts_the_team_and_says_so(): void
    {
        $this->submit();
        Mail::fake();
        $this->submit();

        Mail::assertQueued(NewLeadMail::class, fn (NewLeadMail $m) => $m->repeat && str_starts_with($m->envelope()->subject, 'Returning GlowRez Lead'));
    }

    public function test_the_visitor_sees_a_friendly_message_never_a_sql_error(): void
    {
        $this->submit();
        $res = $this->submit();

        $res->assertOk();
        $this->assertStringNotContainsStringIgnoringCase('duplicate entry', $res->getContent());
        $this->assertStringNotContainsStringIgnoringCase('sql', $res->getContent());
        $this->get('/join')->assertSee('يبدو أن معلوماتك مسجلة لدينا مسبقاً');   // the copy lives in the success panel
    }

    public function test_the_same_email_with_a_new_phone_is_the_same_person(): void
    {
        $this->submit();
        $this->submit(['phone' => '0988 777 666', 'whatsapp_same' => null])->assertJson(['data' => ['duplicate' => true]]);

        $this->assertSame(1, Lead::count());
        $this->assertSame('963944123456', Lead::first()->phone_normalized);   // original number untouched
    }

    public function test_a_closed_lead_that_registers_again_is_reopened(): void
    {
        $this->submit();
        Lead::first()->update(['status' => 'not_interested']);

        $this->submit();

        $this->assertSame('new', Lead::first()->status);
        $this->assertDatabaseHas('lead_activities', ['type' => LeadActivity::REOPENED]);
    }

    public function test_an_in_progress_lead_keeps_its_status_on_a_repeat(): void
    {
        $this->submit();
        Lead::first()->update(['status' => 'negotiation']);

        $this->submit();

        $this->assertSame('negotiation', Lead::first()->status);
    }

    /* ── abuse ──────────────────────────────────────────────────────────── */

    public function test_the_honeypot_pretends_to_succeed_but_stores_nothing(): void
    {
        Mail::fake();

        $this->submit(['hp_extra' => 'http://spam.example'])->assertOk()->assertJson(['status' => true]);

        $this->assertSame(0, Lead::count());
        Mail::assertNothingQueued();
    }

    public function test_a_form_filled_faster_than_a_human_is_dropped(): void
    {
        $this->submit(['_t' => $this->token(0)])->assertOk();
        $this->assertSame(0, Lead::count());
    }

    public function test_a_missing_tampered_or_stale_token_is_rejected_with_a_helpful_message(): void
    {
        foreach ([null, 'garbage', $this->token(86400 * 30)] as $bad) {
            $this->submit(['_t' => $bad])->assertStatus(422)->assertJsonPath('status', false);
        }
        $this->assertSame(0, Lead::count());
    }

    public function test_the_form_is_rate_limited_per_ip(): void
    {
        config(['leads.rate_limit.per_ten_minutes' => 2]);

        $this->submit(['phone' => '0944 000 001', 'email' => null])->assertOk();
        $this->submit(['phone' => '0944 000 002', 'email' => null])->assertOk();
        $this->submit(['phone' => '0944 000 003', 'email' => null])->assertStatus(429);

        $this->assertSame(2, Lead::count());
    }

    public function test_browse_only_mode_hides_login_and_register_but_leaves_the_routes_alive(): void
    {
        config(['leads.browse_only' => true]);

        foreach (['/for-business', '/about'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString(route('company.register'), $html, $url);
            $this->assertStringNotContainsString(route('company.login'), $html, $url);
            $this->assertStringNotContainsString('CustomerAuthModal.open(()=>location.reload()', $html, $url);
        }
        $this->get('/for-business')->assertSee(route('leads.join'), false);

        $this->get(route('company.login'))->assertOk();      // existing businesses can still reach it by URL
        $this->get(route('company.register'))->assertOk();
    }

    public function test_turning_browse_only_off_brings_the_buttons_back(): void
    {
        config(['leads.browse_only' => false]);

        $this->get('/for-business')->assertSee(route('company.register'), false)->assertSee(route('company.login'), false);
    }

    public function test_the_gateway_can_take_over_the_root_for_first_time_visitors(): void
    {
        config(['leads.gateway_on_root' => true]);

        $this->get('/')->assertOk()->assertSee('gw-paths', false);

        // choosing "I'm a client" sets a cookie and falls through to the marketplace
        $this->get('/?enter=customer')->assertRedirect(route('front.index'))->assertCookie('gr_entry', 'customer');
        $this->assertSame(1, LeadVisit::where('page', 'welcome')->count());   // the gateway visit was counted
    }

    /* ── helpers on the model ───────────────────────────────────────────── */

    public function test_the_whatsapp_link_exists_only_for_a_valid_number(): void
    {
        $this->assertSame('https://wa.me/963944123456', (new Lead(['whatsapp' => '0944 123 456']))->whatsappUrl());
        $this->assertNull((new Lead(['whatsapp' => 'abc']))->whatsappUrl());
        $this->assertNull((new Lead(['whatsapp' => null]))->whatsappUrl());
        $this->assertNull((new Lead(['whatsapp' => '12']))->whatsappUrl());
    }

    public function test_phone_numbers_normalise_to_one_canonical_form(): void
    {
        foreach (['0944 123 456', '+963944123456', '00963 944 123 456', '944123456', '٠٩٤٤١٢٣٤٥٦', '963-944-123-456'] as $raw) {
            $this->assertSame('963944123456', \App\Support\LeadPhone::normalize($raw), $raw);
        }
        $this->assertNull(\App\Support\LeadPhone::normalize('hello'));
        $this->assertSame('4915112345678', \App\Support\LeadPhone::normalize('+49 151 12345678'));
    }

    /* ── owner side ─────────────────────────────────────────────────────── */

    private function ownerWithRole(string $role): Owner
    {
        $owner = Owner::factory()->create(['role' => $role, 'is_active' => true, 'must_change_password' => false]);
        $this->actingAs($owner, 'owner');

        return $owner;
    }

    private function makeLead(array $over = []): Lead
    {
        static $n = 0;
        $n++;

        return Lead::create(array_merge([
            'full_name' => "Lead $n", 'phone' => "0944 000 00$n", 'phone_normalized' => '96394400000'.$n,
            'business_name' => "Biz $n", 'business_type' => 'beauty_center', 'city' => 'damascus',
            'number_of_branches' => 1, 'source' => 'instagram', 'status' => 'new', 'interests' => ['bookings'],
            'interaction_count' => 1, 'last_interaction_at' => now(),
        ], $over));
    }

    public function test_leads_are_not_public(): void
    {
        $lead = $this->makeLead();

        $this->get('/owner/leads')->assertRedirect(route('owner.login'));
        $this->get('/owner/leads/'.$lead->id)->assertRedirect(route('owner.login'));
        $this->patch('/owner/leads/'.$lead->id.'/status', ['status' => 'lost'])->assertRedirect();
        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_the_email_button_survives_the_login_redirect(): void
    {
        $lead = $this->makeLead();

        $this->get('/owner/leads/'.$lead->id)->assertRedirect(route('owner.login'));
        $this->assertStringEndsWith('/owner/leads/'.$lead->id, session('url.intended'));
    }

    public function test_staff_without_the_permission_cannot_open_leads(): void
    {
        $lead = $this->makeLead();
        $this->ownerWithRole('support');           // dashboard access, but no leads.* permission

        $this->get('/owner/leads')->assertForbidden();
        $this->get('/owner/leads/'.$lead->id)->assertForbidden();
    }

    public function test_view_only_staff_can_read_but_not_change_anything(): void
    {
        $lead = $this->makeLead();
        $this->ownerWithRole('marketing');         // leads.view only

        $this->get('/owner/leads')->assertOk()->assertSee($lead->business_name);
        $this->get('/owner/leads/'.$lead->id)->assertOk();
        $this->patchJson('/owner/leads/'.$lead->id.'/status', ['status' => 'contacted'])->assertForbidden();
        $this->postJson('/owner/leads/'.$lead->id.'/notes', ['body' => 'hello'])->assertForbidden();
        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_a_manager_moves_a_lead_through_the_pipeline_and_the_timeline_records_it(): void
    {
        $lead = $this->makeLead();
        $owner = $this->ownerWithRole('sales_manager');

        $this->patchJson(route('owner.leads.status', $lead), ['status' => 'contacted'])
            ->assertOk()->assertJsonPath('data.status', 'contacted')->assertJsonPath('data.tone', 'info');

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->contacted_at);
        $this->assertSame($owner->id, $lead->contacted_by);

        $this->patchJson(route('owner.leads.status', $lead), ['status' => 'demo_scheduled'])->assertOk();
        $this->patchJson(route('owner.leads.status', $lead), ['status' => 'converted'])->assertOk();
        $this->assertSame($owner->id, $lead->fresh()->contacted_by);   // first contact is not overwritten

        $steps = LeadActivity::where('lead_id', $lead->id)->where('type', LeadActivity::STATUS_CHANGED)->orderBy('id')->get();
        $this->assertSame(['new→contacted', 'contacted→demo_scheduled', 'demo_scheduled→converted'],
            $steps->map(fn ($a) => $a->meta['from'].'→'.$a->meta['to'])->all());
        $this->assertSame($owner->id, $steps->first()->owner_id);

        $this->patchJson(route('owner.leads.status', $lead), ['status' => 'nonsense'])->assertStatus(422);
    }

    public function test_notes_land_in_the_timeline_with_their_author(): void
    {
        $lead = $this->makeLead();
        $owner = $this->ownerWithRole('admin');

        $this->postJson(route('owner.leads.notes.store', $lead), ['body' => "Called the owner.\nWants a demo next week."])->assertOk();
        $this->postJson(route('owner.leads.notes.store', $lead), ['body' => ''])->assertStatus(422);

        $note = $lead->activities()->where('type', LeadActivity::NOTE)->firstOrFail();
        $this->assertSame($owner->id, $note->owner_id);

        $this->get(route('owner.leads.show', $lead))->assertOk()
            ->assertSee('Wants a demo next week.')->assertSee($owner->name);
    }

    public function test_notes_cannot_inject_markup(): void
    {
        $lead = $this->makeLead();
        $this->ownerWithRole('admin');

        $this->postJson(route('owner.leads.notes.store', $lead), ['body' => 'hi <script>alert(1)</script>'])->assertOk();

        $this->get(route('owner.leads.show', $lead))->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_filters_combine(): void
    {
        $this->ownerWithRole('admin');
        $hit   = $this->makeLead(['business_name' => 'HIT', 'city' => 'damascus', 'business_type' => 'beauty_center', 'source' => 'instagram', 'status' => 'new', 'number_of_branches' => 3, 'campaign' => 'launch01', 'area' => 'Mazzeh']);
        $this->makeLead(['business_name' => 'WRONG_CITY', 'city' => 'aleppo', 'business_type' => 'beauty_center', 'source' => 'instagram', 'status' => 'new', 'number_of_branches' => 3, 'campaign' => 'launch01']);
        $this->makeLead(['business_name' => 'WRONG_TYPE', 'city' => 'damascus', 'business_type' => 'spa', 'source' => 'instagram', 'status' => 'new', 'number_of_branches' => 3, 'campaign' => 'launch01']);
        $this->makeLead(['business_name' => 'WRONG_SOURCE', 'city' => 'damascus', 'business_type' => 'beauty_center', 'source' => 'qr', 'status' => 'new', 'number_of_branches' => 3, 'campaign' => 'launch01']);
        $this->makeLead(['business_name' => 'WRONG_STATUS', 'city' => 'damascus', 'business_type' => 'beauty_center', 'source' => 'instagram', 'status' => 'contacted', 'number_of_branches' => 3, 'campaign' => 'launch01']);
        $this->makeLead(['business_name' => 'WRONG_BRANCHES', 'city' => 'damascus', 'business_type' => 'beauty_center', 'source' => 'instagram', 'status' => 'new', 'number_of_branches' => 1, 'campaign' => 'launch01']);
        $this->makeLead(['business_name' => 'WRONG_CAMPAIGN', 'city' => 'damascus', 'business_type' => 'beauty_center', 'source' => 'instagram', 'status' => 'new', 'number_of_branches' => 3, 'campaign' => 'other']);

        $res = $this->get('/owner/leads?'.http_build_query([
            'city' => 'damascus', 'business_type' => 'beauty_center', 'source' => 'instagram', 'status' => 'new',
            'branches' => '3', 'campaign' => 'launch01', 'area' => 'Mazz',
        ]));

        $res->assertOk()->assertSee('HIT');
        foreach (['WRONG_CITY', 'WRONG_TYPE', 'WRONG_SOURCE', 'WRONG_STATUS', 'WRONG_BRANCHES', 'WRONG_CAMPAIGN'] as $miss) {
            $res->assertDontSee($miss);
        }
        $this->assertSame($hit->id, $res->viewData('leads')->first()->id);
    }

    public function test_search_finds_name_business_email_and_any_phone_format(): void
    {
        $this->ownerWithRole('admin');
        $this->makeLead(['full_name' => 'Khaled Noor', 'business_name' => 'Zaytoun Spa', 'email' => 'k@zaytoun.test', 'phone' => '0933 111 222', 'phone_normalized' => '963933111222']);
        $this->makeLead(['full_name' => 'Someone Else', 'business_name' => 'Other']);

        foreach (['Khaled', 'Zaytoun', 'zaytoun.test', '0933111222', '+963 933 111 222', '933111'] as $q) {
            $ids = $this->get('/owner/leads?'.http_build_query(['q' => $q]))->viewData('leads')->pluck('business_name')->all();
            $this->assertSame(['Zaytoun Spa'], $ids, "query: $q");
        }
    }

    public function test_date_range_and_sorting(): void
    {
        $this->ownerWithRole('admin');
        $old = $this->makeLead(['business_name' => 'OLD', 'number_of_branches' => 9]);
        $old->forceFill(['created_at' => now()->subDays(20)])->save();
        $mid = $this->makeLead(['business_name' => 'MID', 'number_of_branches' => 2]);
        $mid->forceFill(['created_at' => now()->subDays(5)])->save();
        $new = $this->makeLead(['business_name' => 'NEW', 'number_of_branches' => 5, 'full_name' => 'Aaa']);

        $names = fn (string $qs) => $this->get('/owner/leads?'.$qs)->viewData('leads')->pluck('business_name')->all();

        $this->assertSame(['NEW', 'MID', 'OLD'], $names('sort=newest'));
        $this->assertSame(['OLD', 'MID', 'NEW'], $names('sort=oldest'));
        $this->assertSame(['OLD', 'NEW', 'MID'], $names('sort=branches'));
        $this->assertSame('NEW', $names('sort=name')[0]);
        $this->assertSame(['MID'], $names('date_from='.now()->subDays(10)->toDateString().'&date_to='.now()->subDays(2)->toDateString()));
    }

    public function test_stats_cover_status_source_type_city_and_conversion(): void
    {
        $this->ownerWithRole('admin');
        $this->makeLead(['status' => 'new', 'source' => 'instagram', 'city' => 'damascus', 'business_type' => 'spa', 'campaign' => 'c1']);
        $this->makeLead(['status' => 'converted', 'source' => 'instagram', 'city' => 'aleppo', 'business_type' => 'spa', 'campaign' => 'c1']);
        $this->makeLead(['status' => 'contacted', 'source' => 'qr', 'city' => 'damascus', 'business_type' => 'clinic']);
        foreach (['v1', 'v2', 'v3', 'v4'] as $v) {
            LeadVisit::create(['visitor_id' => $v, 'page' => 'join', 'source' => 'instagram', 'campaign' => 'c1', 'created_at' => now()]);
        }

        $report = $this->get('/owner/leads')->viewData('report');

        $this->assertSame(3, $report['total']);
        $this->assertSame(1, $report['by_status']['new']);
        $this->assertSame(1, $report['by_status']['converted']);
        $this->assertSame(4, $report['visitors']);
        $this->assertSame(75.0, $report['rate']);
        $ig = collect($report['sources'])->firstWhere('key', 'instagram');
        $this->assertSame(2, $ig['count']);
        $this->assertSame(4, $ig['visitors']);
        $this->assertSame(50.0, $ig['rate']);
        $this->assertSame('دمشق', collect($report['cities'])->firstWhere('key', 'damascus')['label']);
    }

    public function test_csv_export_respects_filters(): void
    {
        $this->ownerWithRole('admin');
        $this->makeLead(['business_name' => 'IN_EXPORT', 'source' => 'qr']);
        $this->makeLead(['business_name' => 'NOT_IN_EXPORT', 'source' => 'instagram']);

        $csv = $this->get('/owner/leads/export?source=qr')->assertOk()->streamedContent();

        $this->assertStringContainsString('IN_EXPORT', $csv);
        $this->assertStringNotContainsString('NOT_IN_EXPORT', $csv);
    }

    public function test_csv_export_neutralises_spreadsheet_formulas(): void
    {
        $this->ownerWithRole('admin');
        $this->makeLead(['business_name' => '=HYPERLINK("http://evil.test","x")', 'full_name' => '@SUM(1+1)', 'phone' => '+963 944 123 456']);

        $csv = $this->get('/owner/leads/export')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@SUM", $csv);
        $this->assertStringContainsString('+963 944 123 456', $csv);          // a phone number is left alone
        $this->assertStringNotContainsString("\n=HYPERLINK", $csv);
    }

    public function test_the_detail_page_shows_everything_and_hides_whatsapp_when_the_number_is_bad(): void
    {
        $this->ownerWithRole('admin');
        $good = $this->makeLead(['whatsapp' => '0944 123 456', 'utm_source' => 'ig', 'utm_medium' => 'story', 'campaign' => 'launch01', 'instagram' => 'lamsa.beauty']);
        $bad  = $this->makeLead(['whatsapp' => 'not a number']);

        $this->get(route('owner.leads.show', $good))->assertOk()
            ->assertSee('https://wa.me/963944123456', false)
            ->assertSee('launch01')->assertSee('story')->assertSee('https://instagram.com/lamsa.beauty', false);

        $this->get(route('owner.leads.show', $bad))->assertOk()->assertDontSee('https://wa.me/', false);
    }

    public function test_the_sidebar_shows_the_new_lead_count_to_people_who_may_see_leads(): void
    {
        $this->makeLead(); $this->makeLead(); $this->makeLead(['status' => 'contacted']);

        $this->ownerWithRole('admin');
        $this->get('/owner/leads')->assertOk()->assertSee('owner/leads', false);
        $this->assertSame(2, Lead::where('status', 'new')->count());
    }

    public function test_permissions_are_in_the_catalogue_and_roles(): void
    {
        $this->assertArrayHasKey('leads.view', config('owner-permissions.catalog.leads.permissions'));
        $this->assertArrayHasKey('leads.manage', config('owner-permissions.catalog.leads.permissions'));
        $this->assertTrue(Owner::factory()->make(['role' => 'sales_manager'])->hasPermission('leads.manage'));
        $this->assertTrue(Owner::factory()->make(['role' => 'marketing'])->hasPermission('leads.view'));
        $this->assertFalse(Owner::factory()->make(['role' => 'marketing'])->hasPermission('leads.manage'));
        $this->assertFalse(Owner::factory()->make(['role' => 'field_sales'])->hasPermission('leads.view'));
    }
}
