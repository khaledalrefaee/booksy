<?php

namespace Tests\Unit;

use App\Enums\CustomerTier;
use PHPUnit\Framework\TestCase;

class CustomerTierTest extends TestCase
{
    public function test_a_customer_with_no_visits_is_new(): void
    {
        $this->assertSame(CustomerTier::New, CustomerTier::resolve(null, 0));
    }

    public function test_a_few_visits_makes_a_regular(): void
    {
        $this->assertSame(CustomerTier::Regular, CustomerTier::resolve(null, 1));
        $this->assertSame(CustomerTier::Regular, CustomerTier::resolve(null, 4));
    }

    public function test_enough_visits_makes_a_loyal_customer(): void
    {
        $this->assertSame(CustomerTier::Loyal, CustomerTier::resolve(null, CustomerTier::LOYAL_AT));
        $this->assertSame(CustomerTier::Loyal, CustomerTier::resolve(null, 40));
    }

    public function test_a_manual_tag_beats_the_visit_count(): void
    {
        // The whole point of the tag column: an owner marking someone VIP must
        // outrank whatever their history says.
        $this->assertSame(CustomerTier::Vip, CustomerTier::resolve('vip', 0));
        $this->assertSame(CustomerTier::Vip, CustomerTier::resolve('vip', 100));
    }

    public function test_an_unrecognised_tag_falls_back_to_the_visit_count(): void
    {
        // customers.tag is a free-text column; junk in it must not break the badge.
        $this->assertSame(CustomerTier::New, CustomerTier::resolve('platinum', 0));
        $this->assertSame(CustomerTier::Regular, CustomerTier::resolve('', 2));
    }

    public function test_every_tier_has_an_icon_and_a_distinct_colour(): void
    {
        $colors = [];

        foreach (CustomerTier::cases() as $case) {
            $this->assertNotEmpty($case->iconPath(), "{$case->value} has no icon.");
            $this->assertStringContainsString('<svg', $case->svg());
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $case->color());
            $colors[] = $case->color();
        }

        $this->assertSame(count($colors), count(array_unique($colors)), 'Two tiers share a colour.');
    }
}
