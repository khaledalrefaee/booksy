<x-ps.panel id="support" :active="$tab === 'support'"
    :action="route('owner.settings.update', 'support')"
    :title="__('Support & customer service')"
    :desc="__('Edit how customers reach you without touching code. Hiding a channel only hides it from visitors — its data stays saved.')">

    <x-ps.section :title="__('Customer-facing channels')" :desc="__('Shown on the Contact and Help pages when switched on. Each has its own switch.')">
        <div class="ps-channel">
            <x-ps.field name="support.email" check="email" placeholder="support@domain.com" />
            <div class="ps-chan-switch"><x-ps.switch name="support.email_visible" :title="__('Visible')" :inline="true" /></div>
        </div>
        <div class="ps-channel">
            <x-ps.field name="support.phone" check="phone" placeholder="+963 11 123 4567" :hint="__('Include the country code.')" />
            <div class="ps-chan-switch"><x-ps.switch name="support.phone_visible" :title="__('Visible')" :inline="true" /></div>
        </div>
        <div class="ps-channel">
            <div class="ps-sec-body" style="gap:16px">
                <x-ps.field name="support.whatsapp" check="wa" placeholder="+963 944 123 456" :hint="__('The chat link is built from this number automatically.')" />
                <x-ps.field name="support.whatsapp_link" check="url" placeholder="https://wa.me/…" :hint="__('Optional — only if you want a specific link (for example with a pre-filled message). It overrides the automatic one.')" />
            </div>
            <div class="ps-chan-switch"><x-ps.switch name="support.whatsapp_visible" :title="__('Visible')" :inline="true" /></div>
        </div>
        <div class="ps-channel">
            <x-ps.field name="support.help_center_url" check="url" placeholder="https://… or /help" />
            <div class="ps-chan-switch"><x-ps.switch name="support.help_center_visible" :title="__('Visible')" :inline="true" /></div>
        </div>
        <div class="ps-channel">
            <x-ps.field name="support.faq_url" check="url" placeholder="https://… or /help#faq" />
            <div class="ps-chan-switch"><x-ps.switch name="support.faq_visible" :title="__('Visible')" :inline="true" /></div>
        </div>
        <div class="ps-channel">
            <x-ps.field name="support.contact_url" check="url" placeholder="https://… or /contact" :hint="__('Where the Contact the GlowRez team button leads.')" />
            <div class="ps-chan-switch"><x-ps.switch name="support.contact_visible" :title="__('Visible')" :inline="true" /></div>
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Availability')" :desc="__('When customer service answers. Shown next to the channels above.')">
        <div class="ps-grid">
            <x-ps.field name="support.hours_from" />
            <x-ps.field name="support.hours_to" />
        </div>
        @php $days = (array) old('support.days', $v['support.days'] ?? []); @endphp
        <div class="ps-field">
            <span class="ps-label" id="ps-days-l">{{ __('Working days') }}</span>
            <div class="ps-chips" role="group" aria-labelledby="ps-days-l">
                <input type="hidden" name="support[days][]" value=""> {{-- lets "no day selected" be saved --}}
                @foreach($weekdays as $d)
                    <label class="ps-chip"><input type="checkbox" name="support[days][]" value="{{ $d }}" @checked(in_array($d, $days, true))><span>{{ __(ucfirst($d)) }}</span></label>
                @endforeach
            </div>
            @error('support.days')<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="ps-grid">
            <x-ps.field name="support.default_message_ar" dir="rtl" :rows="3" :hint="__('The greeting customers see when they contact support.')" />
            <x-ps.field name="support.default_message_en" dir="ltr" :rows="3" />
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Internal — team only')" :badge="__('Never public')" :desc="__('For the GlowRez team. These values never appear on the website or in any public API.')">
        <div class="ps-grid">
            <x-ps.field name="support.internal_email" check="email" placeholder="team@domain.com" :hint="__('Messages sent from the website contact form are delivered here.')" />
            <x-ps.field name="support.internal_phone" check="phone" placeholder="+963 944 123 456" />
            <x-ps.field name="support.internal_notes" class="ps-span-2" :rows="3" :hint="__('For example escalation steps or who is on call.')" />
        </div>
    </x-ps.section>
</x-ps.panel>
