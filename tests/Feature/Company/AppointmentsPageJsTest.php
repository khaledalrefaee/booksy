<?php

namespace Tests\Feature\Company;

use App\Models\Branch;
use App\Models\Company;
use Tests\TestCase;

/**
 * Dumps the JavaScript the appointments page actually ships to the browser.
 *
 * The page inlines its front end inside Blade, so ESLint cannot read the source
 * directly — PHP is interleaved through it. Rendering the page resolves every
 * blade expression and leaves real JavaScript, which CAN be linted.
 *
 *   php artisan test --filter=AppointmentsPageJsTest
 *   npx eslint --no-config-lookup --no-ignore storage/app/lint/*.js
 *
 * This exists to make "function is not defined" impossible to ship while the
 * script still lives in Blade. Once the JS is extracted to its own files this
 * becomes redundant and should be deleted.
 */
class AppointmentsPageJsTest extends TestCase
{
    public function test_it_dumps_rendered_page_javascript_for_linting(): void
    {
        $company = Company::factory()->create();
        Branch::factory()->create(['company_id' => $company->id]);

        $html = $this->actingAs($company, 'company')
            ->get(route('company.appointments.index'))
            ->assertOk()
            ->getContent();

        // inline blocks only — <script src="..."> has no body to lint
        preg_match_all('/<script>(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m[1], 'the page shipped no inline script at all');

        $dir = storage_path('app/lint');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        foreach (glob("$dir/*.js") as $old) {
            unlink($old);
        }

        foreach ($m[1] as $i => $js) {
            file_put_contents("$dir/appointments-block-$i.js", $js);
        }

        $this->assertFileExists("$dir/appointments-block-0.js");
    }
}
