<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Company;
use App\Models\OtpCode;
use App\Services\WhatsappService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

/**
 * End-to-end cover for the company mobile auth API (routes/api.php → company).
 * Mail is faked and the WhatsApp/SMS transport is mocked, so nothing is sent.
 */
class CompanyAuthApiTest extends TestCase
{
    /** @var array<int, array{phone:string, type:string, channel:?string}> */
    private array $phoneSends = [];

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // Keep the real country routing (channelFor) but never hit a gateway.
        $whatsapp = Mockery::mock(WhatsappService::class)->makePartial();
        $whatsapp->shouldReceive('send')->andReturnUsing(function ($phone, $msg, $cid = null, $aid = null, $type = 'general', $channel = null) {
            $this->phoneSends[] = compact('phone', 'type', 'channel');

            return true;
        });
        $this->app->instance(WhatsappService::class, $whatsapp);

        $this->category = Category::query()->create([
            'slug' => 'salon-'.uniqid(), 'name_en' => 'Salon', 'name_ar' => 'صالون', 'sort_order' => 1,
        ]);
    }

    private function registerPayload(array $override = []): array
    {
        return array_merge([
            'name_en'     => 'Glow Studio',
            'name_ar'     => 'استوديو جلو',
            'owner_name'  => 'Khaled',
            'email'       => 'owner@glow.test',
            'phone'       => '+963991234567',
            'category_id' => $this->category->id,
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'terms'                 => '1',
        ], $override);
    }

    /** Newest code sent for this phone. */
    private function latestCode(string $phone): string
    {
        return OtpCode::query()->where('phone', $phone)->latest('id')->value('code');
    }

    private function wrong(string $code): string
    {
        return $code === '0000' ? '1111' : '0000';
    }

    private function verifiedCompany(array $attrs = []): Company
    {
        return Company::factory()->create(array_merge([
            'email'             => 'live@glow.test',
            'phone'             => '+963998887777',
            'password'          => Hash::make('secret123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'status'            => 'active',
        ], $attrs));
    }

    private function assertEnvelope(TestResponse $res, bool $status): void
    {
        $res->assertJsonStructure(['status', 'message', 'data'])->assertJsonPath('status', $status);
    }

    public function test_categories_list_is_public_and_ids_register_a_company(): void
    {
        $res = $this->getJson('/api/company/categories?lang=ar');

        $res->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['categories' => [['id', 'slug', 'name', 'name_en', 'name_ar', 'icon', 'icon_url', 'image']]]])
            ->assertJsonPath('data.categories.0.id', $this->category->id)
            ->assertJsonPath('data.categories.0.name', 'صالون');

        $this->postJson('/api/company/register', $this->registerPayload(['category_id' => $res->json('data.categories.0.id')]))
            ->assertCreated();
    }

    public function test_register_sends_one_code_to_both_email_and_phone(): void
    {
        $res = $this->postJson('/api/company/register', $this->registerPayload());

        $res->assertCreated();
        $this->assertEnvelope($res, true);
        $res->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('data.phone_verified', false)
            ->assertJsonPath('data.email', 'ow***@glow.test')
            ->assertJsonPath('data.phone', '*********4567')
            ->assertJsonPath('data.phone_channel', 'sms')
            ->assertJsonPath('data.dev_code', null)          // APP_ENV=testing, not local
            ->assertJsonMissingPath('data.dev_email_code')
            ->assertJsonMissingPath('data.token');

        $company = Company::find($res->json('data.company_id'));
        $this->assertSame('pending', $company->status);
        $this->assertFalse($company->isVerified());
        $this->assertNull($company->email_verified_at);
        $this->assertTrue(Hash::check('secret123', $company->password));

        // Exactly one code row, and that same code went to both inboxes.
        $this->assertSame(1, OtpCode::where('phone', $company->phone)->count());
        $code = $this->latestCode($company->phone);
        $this->assertStringNotContainsString('"'.$code.'"', $res->getContent());

        Mail::assertSent(\App\Mail\VerificationCodeMail::class, 1);
        $this->assertCount(1, $this->phoneSends);
        $this->assertSame('sms', $this->phoneSends[0]['channel']);
    }

    public function test_register_returns_the_code_in_local_dev_only(): void
    {
        $this->app['env'] = 'local';

        $res  = $this->postJson('/api/company/register', $this->registerPayload());
        $code = $this->latestCode('+963991234567');

        $res->assertCreated()->assertJsonPath('data.dev_code', $code);
    }

    public function test_register_validation_uses_the_error_envelope(): void
    {
        $this->verifiedCompany(['email' => 'owner@glow.test']);

        $res = $this->postJson('/api/company/register', $this->registerPayload(['phone' => '0999', 'terms' => '0']));

        $res->assertStatus(422);
        $this->assertEnvelope($res, false);
        $res->assertJsonValidationErrors(['email', 'phone', 'terms']);
    }

    public function test_register_requires_a_matching_password_confirmation(): void
    {
        $this->postJson('/api/company/register', $this->registerPayload(['password_confirmation' => null]))
            ->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->postJson('/api/company/register', $this->registerPayload(['password_confirmation' => 'different1']))
            ->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->assertSame(0, Company::where('email', 'owner@glow.test')->count());
    }

    public function test_register_rejects_a_phone_that_is_already_registered(): void
    {
        $this->verifiedCompany(['phone' => '+963991234567']);

        $this->postJson('/api/company/register', $this->registerPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonMissingValidationErrors(['email']);
    }

    public function test_verify_with_the_single_code_marks_email_and_phone_verified(): void
    {
        $id   = $this->postJson('/api/company/register', $this->registerPayload())->json('data.company_id');
        $code = $this->latestCode('+963991234567');

        $this->postJson('/api/company/verify', ['company_id' => $id])
            ->assertStatus(422)->assertJsonValidationErrors(['code']);

        $this->postJson('/api/company/verify', ['company_id' => $id, 'code' => $this->wrong($code)])
            ->assertStatus(422)->assertJsonPath('status', false);
        $this->assertFalse(Company::find($id)->isVerified());

        $res = $this->postJson('/api/company/verify', ['company_id' => $id, 'code' => $code]);
        $res->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.company.id', $id)
            ->assertJsonPath('data.company.verified', true)
            ->assertJsonPath('data.company.email_verified', true)
            ->assertJsonPath('data.company.phone_verified', true)
            ->assertJsonPath('data.company.category.name', 'Salon')
            ->assertJsonMissingPath('data.company.password')
            ->assertJsonMissingPath('data.company.api_token');

        $fresh = Company::find($id);
        $this->assertNotNull($fresh->email_verified_at);
        $this->assertNotNull($fresh->phone_verified_at);

        $this->withToken($res->json('data.token'))->getJson('/api/company/me')
            ->assertOk()->assertJsonPath('data.company.email', 'owner@glow.test');

        // Already verified → 409.
        $this->postJson('/api/company/verify', ['company_id' => $id, 'code' => $code])->assertStatus(409);
    }

    public function test_verify_burns_codes_after_too_many_wrong_attempts(): void
    {
        $id   = $this->postJson('/api/company/register', $this->registerPayload())->json('data.company_id');
        $code = $this->latestCode('+963991234567');
        $bad  = ['company_id' => $id, 'code' => $this->wrong($code)];

        foreach (range(1, 4) as $i) {
            $this->postJson('/api/company/verify', $bad)->assertStatus(422);
        }
        $this->postJson('/api/company/verify', $bad)->assertStatus(429)->assertJsonPath('status', false);

        // The real code is dead now — a new one must be requested.
        $this->postJson('/api/company/verify', ['company_id' => $id, 'code' => $code])->assertStatus(422);
    }

    public function test_resend_enforces_cooldown_then_sends_a_new_code_to_both(): void
    {
        $id = $this->postJson('/api/company/register', $this->registerPayload())->json('data.company_id');

        $this->postJson('/api/company/resend', ['company_id' => $id])
            ->assertStatus(429)
            ->assertJsonPath('status', false)
            ->assertJsonStructure(['data' => ['retry_after']]);

        $this->travel(61)->seconds();

        $this->postJson('/api/company/resend', ['company_id' => $id])
            ->assertOk()->assertJsonPath('data.phone_channel', 'sms');
        $this->assertCount(2, $this->phoneSends);
        Mail::assertSent(\App\Mail\VerificationCodeMail::class, 2);

        $this->postJson('/api/company/verify', ['company_id' => $id, 'code' => $this->latestCode('+963991234567')])
            ->assertOk();
    }

    public function test_login_flow(): void
    {
        $this->verifiedCompany();

        $this->postJson('/api/company/login', ['email' => 'live@glow.test', 'password' => 'wrong-pass'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);

        $res = $this->postJson('/api/company/login', ['email' => 'live@glow.test', 'password' => 'secret123']);
        $res->assertOk()->assertJsonPath('data.token_type', 'Bearer')->assertJsonPath('data.company.email', 'live@glow.test');
        $token = $res->json('data.token');

        $this->withToken($token)->postJson('/api/company/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/company/me')->assertStatus(401)->assertJsonPath('status', false);
    }

    public function test_login_of_unverified_account_asks_for_verification(): void
    {
        $this->verifiedCompany(['phone_verified_at' => null, 'status' => 'pending']);

        $this->postJson('/api/company/login', ['email' => 'live@glow.test', 'password' => 'secret123'])
            ->assertStatus(403)
            ->assertJsonPath('data.verification_required', true)
            ->assertJsonMissingPath('data.token');
    }

    public function test_login_of_suspended_account_is_refused(): void
    {
        $this->verifiedCompany(['status' => 'suspended']);

        $this->postJson('/api/company/login', ['email' => 'live@glow.test', 'password' => 'secret123'])
            ->assertStatus(403)->assertJsonPath('status', false);
    }

    public function test_forgot_password_does_not_reveal_unknown_accounts(): void
    {
        $this->verifiedCompany();

        $known   = $this->postJson('/api/company/password/forgot', ['method' => 'email', 'value' => 'live@glow.test']);
        $unknown = $this->postJson('/api/company/password/forgot', ['method' => 'email', 'value' => 'nobody@glow.test']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        $this->assertSame($known->json('data'), $unknown->json('data'));

        $this->postJson('/api/company/password/forgot', ['method' => 'pigeon', 'value' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['method']);
        $this->postJson('/api/company/password/forgot', ['method' => 'email', 'value' => 'not-an-email'])
            ->assertStatus(422)->assertJsonValidationErrors(['value']);
    }

    public function test_full_password_reset_by_sms_with_one_time_token(): void
    {
        $company  = $this->verifiedCompany();
        $oldToken = $this->postJson('/api/company/login', ['email' => 'live@glow.test', 'password' => 'secret123'])->json('data.token');

        // Phone accepted without the leading "+".
        $this->postJson('/api/company/password/forgot', ['method' => 'sms', 'value' => '963998887777'])
            ->assertOk()->assertJsonPath('data.method', 'sms')->assertJsonPath('data.dev_code', null);
        $this->assertSame('password_reset', end($this->phoneSends)['type']);

        $code = $this->latestCode($company->phone);

        $verify = $this->postJson('/api/company/password/verify', ['method' => 'sms', 'value' => '963998887777', 'code' => $code]);
        $verify->assertOk()->assertJsonStructure(['data' => ['reset_token', 'expires_in']]);
        $resetToken = $verify->json('data.reset_token');

        $this->postJson('/api/company/password/reset', [
            'reset_token' => $resetToken, 'password' => 'brand-new-1', 'password_confirmation' => 'mismatch',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->postJson('/api/company/password/reset', [
            'reset_token' => $resetToken, 'password' => 'brand-new-1', 'password_confirmation' => 'brand-new-1',
        ])->assertOk()->assertJsonPath('status', true);

        $this->assertTrue(Hash::check('brand-new-1', $company->fresh()->password));

        // One-time: the same token is dead.
        $this->postJson('/api/company/password/reset', [
            'reset_token' => $resetToken, 'password' => 'another-1', 'password_confirmation' => 'another-1',
        ])->assertStatus(422);

        // Old device session revoked; new password signs in.
        $this->app['auth']->forgetGuards();
        $this->withToken($oldToken)->getJson('/api/company/me')->assertStatus(401);
        $this->postJson('/api/company/login', ['email' => 'live@glow.test', 'password' => 'brand-new-1'])->assertOk();
    }

    public function test_failed_logins_do_not_exhaust_the_forgot_password_throttle(): void
    {
        foreach (range(1, 6) as $i) {
            $this->postJson('/api/company/login', ['email' => 'x@glow.test', 'password' => 'nope'])->assertStatus(422);
        }

        $this->postJson('/api/company/password/forgot', ['method' => 'email', 'value' => 'x@glow.test'])->assertOk();
    }

    public function test_web_panel_verify_and_reset_still_work_on_the_shared_services(): void
    {
        // Web sign-up → verify screen → correct code → dashboard.
        $this->post('/company/register', $this->registerPayload())->assertRedirect(route('company.verify.notice'));
        $this->post('/company/verify', ['code' => $this->latestCode('+963991234567')])
            ->assertRedirect(route('company.dashboard'));
        $this->assertTrue(Company::where('email', 'owner@glow.test')->first()->isVerified());

        // Web forgot (email) → reset with the code.
        $this->post('/company/logout');
        $this->travel(61)->seconds();
        $this->post('/company/forgot-password', ['channel' => 'email', 'email' => 'owner@glow.test'])
            ->assertRedirect(route('company.password.reset'));
        $this->post('/company/reset-password', ['code' => $this->latestCode('+963991234567'), 'password' => 'web-new-pass'])
            ->assertRedirect(route('company.login'));
        $this->assertTrue(Hash::check('web-new-pass', Company::where('email', 'owner@glow.test')->first()->password));
    }

    private function signedInToken(Company $company): string
    {
        return $this->postJson('/api/company/login', ['email' => $company->email, 'password' => 'secret123'])->json('data.token');
    }

    public function test_profile_update_changes_only_sent_fields_and_uploads_logo(): void
    {
        Storage::fake('public');
        $company = $this->verifiedCompany(['name_en' => 'Old Name', 'name_ar' => 'قديم']);
        $token   = $this->signedInToken($company);

        $res = $this->withToken($token)->post('/api/company/profile', [
            'name_en' => 'New Name',
            'logo'    => UploadedFile::fake()->image('logo.png', 300, 300),
        ], ['Accept' => 'application/json']);

        $res->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.company.name_en', 'New Name')
            ->assertJsonPath('data.company.name_ar', 'قديم')          // untouched
            ->assertJsonPath('data.company.email', 'live@glow.test'); // untouched

        $path = $company->fresh()->logo;
        $this->assertStringStartsWith('companies/logos/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith($path, $res->json('data.company.logo'));
    }

    public function test_profile_update_validates_unique_email_and_phone(): void
    {
        $this->verifiedCompany(['email' => 'taken@glow.test', 'phone' => '+963911111111']);
        $company = $this->verifiedCompany();
        $token   = $this->signedInToken($company);

        $this->withToken($token)->postJson('/api/company/profile', [
            'email' => 'taken@glow.test', 'phone' => '+963911111111', 'name_en' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['email', 'phone', 'name_en']);

        // Keeping your own email/phone is fine.
        $this->withToken($token)->postJson('/api/company/profile', [
            'email' => 'live@glow.test', 'phone' => '+963998887777',
        ])->assertOk();
    }

    public function test_logo_upload_replaces_old_file_and_delete_removes_it(): void
    {
        Storage::fake('public');
        $company = $this->verifiedCompany();
        $token   = $this->signedInToken($company);

        $this->withToken($token)->post('/api/company/logo', [], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['logo']);

        $this->withToken($token)->post('/api/company/logo', [
            'logo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['logo']);

        $this->withToken($token)->post('/api/company/logo', ['logo' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json'])->assertOk();
        $first = $company->fresh()->logo;

        $this->withToken($token)->post('/api/company/logo', ['logo' => UploadedFile::fake()->image('b.jpg')], ['Accept' => 'application/json'])->assertOk();
        $second = $company->fresh()->logo;

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->withToken($token)->deleteJson('/api/company/logo')
            ->assertOk()->assertJsonPath('data.company.logo', null);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($company->fresh()->logo);
    }

    public function test_profile_endpoints_require_a_token(): void
    {
        $this->postJson('/api/company/profile', ['name_en' => 'x'])->assertStatus(401);
        $this->postJson('/api/company/logo')->assertStatus(401);
        $this->deleteJson('/api/company/logo')->assertStatus(401);
    }

    public function test_reset_code_is_rejected_when_wrong(): void
    {
        $this->verifiedCompany();

        $this->postJson('/api/company/password/verify', ['method' => 'email', 'value' => 'live@glow.test', 'code' => '12'])
            ->assertStatus(422)->assertJsonValidationErrors(['code']);
        $this->postJson('/api/company/password/verify', ['method' => 'email', 'value' => 'ghost@glow.test', 'code' => '1234'])
            ->assertStatus(422)->assertJsonPath('status', false);
    }
}
