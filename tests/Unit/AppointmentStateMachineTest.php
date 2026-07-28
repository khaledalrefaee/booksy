<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus as S;
use App\Enums\TransitionActor as A;
use App\States\AppointmentStateMachine as SM;
use PHPUnit\Framework\TestCase;

class AppointmentStateMachineTest extends TestCase
{
    public function test_every_status_has_an_entry_in_the_map(): void
    {
        $map = SM::map();

        foreach (S::cases() as $case) {
            $this->assertArrayHasKey(
                $case->value,
                $map,
                "Status {$case->value} is missing from the transition map, so nothing could ever leave it.",
            );
        }
    }

    public function test_every_transition_target_is_a_real_status(): void
    {
        foreach (SM::map() as $from => $targets) {
            foreach (array_keys($targets) as $to) {
                $this->assertNotNull(
                    S::tryFrom($to),
                    "Transition {$from} → {$to} points at a status that does not exist.",
                );
            }
        }
    }

    public function test_terminal_statuses_have_no_way_out(): void
    {
        foreach (S::cases() as $case) {
            if (! $case->isTerminal()) {
                continue;
            }

            $this->assertSame(
                [],
                SM::map()[$case->value],
                "Terminal status {$case->value} should not offer any transition.",
            );
        }
    }

    public function test_the_happy_path_is_walkable_end_to_end(): void
    {
        $path = [
            S::Pending, S::Confirmed, S::Arrived,
            S::InProgress, S::AwaitingPayment, S::Completed,
        ];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue(
                SM::can($path[$i], $path[$i + 1]),
                "The main flow breaks at {$path[$i]->value} → {$path[$i + 1]->value}.",
            );
        }
    }

    public function test_reception_can_close_a_booking_without_the_check_in_steps(): void
    {
        // Salons that do not track arrivals must not be forced through them.
        $this->assertTrue(SM::canBy(S::Confirmed, S::Completed, A::Company));
        $this->assertTrue(SM::canBy(S::Confirmed, S::AwaitingPayment, A::Company));
    }

    public function test_a_customer_cannot_cancel_on_the_salons_behalf(): void
    {
        $this->assertTrue(SM::canBy(S::Confirmed, S::CancelledByCustomer, A::Customer));
        $this->assertFalse(SM::canBy(S::Confirmed, S::CancelledBySalon, A::Customer));
    }

    public function test_a_customer_cannot_complete_or_check_in_an_appointment(): void
    {
        $this->assertFalse(SM::canBy(S::Confirmed, S::Arrived, A::Customer));
        $this->assertFalse(SM::canBy(S::AwaitingPayment, S::Completed, A::Customer));
    }

    public function test_salon_cancellation_demands_a_reason(): void
    {
        $this->assertTrue(SM::requiresReason(S::Confirmed, S::CancelledBySalon));
        $this->assertFalse(SM::requiresReason(S::Confirmed, S::CancelledByCustomer));
    }

    public function test_a_no_show_can_be_corrected(): void
    {
        // Reception forgets to check people in; late arrivals happen.
        $this->assertTrue(SM::canBy(S::NoShow, S::Arrived, A::Company));
    }

    public function test_no_show_is_never_reachable_from_a_customer_already_on_site(): void
    {
        foreach (S::cases() as $case) {
            if (! $case->isOnSite()) {
                continue;
            }

            $this->assertFalse(
                SM::can($case, S::NoShow),
                "{$case->value} means the customer is here — it must not lead to no_show.",
            );
        }
    }

    public function test_cancellations_and_no_shows_release_their_slot(): void
    {
        $blocking = S::blocking();

        $this->assertNotContains(S::NoShow, $blocking);
        $this->assertNotContains(S::CancelledByCustomer, $blocking);
        $this->assertNotContains(S::CancelledBySalon, $blocking);
        $this->assertNotContains(S::Draft, $blocking, 'An unfinished draft holds nothing.');

        $this->assertContains(S::InProgress, $blocking);
        $this->assertContains(S::Arrived, $blocking);
    }

    public function test_allowed_moves_never_include_the_status_itself(): void
    {
        foreach (S::cases() as $case) {
            $this->assertNotContains(
                $case,
                SM::allowedFor($case, A::Company),
                "{$case->value} lists itself as a next step.",
            );
        }
    }

    /**
     * label() is deliberately left out: it goes through the translator, which
     * needs a booted container. Its wiring is covered where the page renders.
     */
    public function test_every_status_carries_usable_presentation_data(): void
    {
        foreach (S::cases() as $case) {
            $this->assertMatchesRegularExpression(
                '/^#[0-9a-f]{6}$/i',
                $case->color(),
                "{$case->value} has no valid hex colour.",
            );
            $this->assertNotEmpty($case->icon(), "{$case->value} has no icon.");
        }
    }

    public function test_every_status_has_a_distinct_colour(): void
    {
        $colors = array_map(fn (S $s) => $s->color(), S::cases());

        $this->assertSame(
            count($colors),
            count(array_unique($colors)),
            'Two statuses share a colour, so the calendar cannot tell them apart.',
        );
    }
}
