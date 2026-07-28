@props(['status'])

{{--
    One badge for an appointment status, everywhere.

    Replaces the hand-written match() → bootstrap-colour blocks that were
    copy-pasted across the owner panel; colour and label now come from
    App\Enums\AppointmentStatus, so a new status needs no template edits.
--}}

@php $status = $status instanceof \App\Enums\AppointmentStatus ? $status : \App\Enums\AppointmentStatus::from($status); @endphp

<span {{ $attributes->merge([
        'class' => 'badge rounded-pill px-3 py-2',
        'style' => "background:{$status->color()};color:#fff;",
    ]) }}>
    {{ $status->label() }}
</span>
