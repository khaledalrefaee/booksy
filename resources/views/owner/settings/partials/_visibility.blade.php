<x-ps.panel id="visibility" :active="$tab === 'visibility'"
    :action="route('owner.settings.update', 'visibility')"
    :title="__('Public site & footer')"
    :desc="__('Decide what visitors see in the website footer and on the contact pages. Hiding something here never deletes it or turns off internal support.')">

    <x-ps.section :title="__('What visitors can see')" :desc="__('Each switch only affects the public website and API.')">
        <div class="ps-switches">
            <x-ps.switch name="visibility.show_official_email" :desc="__('The official email from the General tab, in the footer.')" />
            <x-ps.switch name="visibility.show_official_phone" :desc="__('The official phone from the General tab, in the footer.')" />
            <x-ps.switch name="visibility.show_social" :desc="__('The active links from the Links tab.')" />
            <x-ps.switch name="visibility.show_support_links" :desc="__('The support channels from the Support tab, on the Contact and Help pages.')" />
            <x-ps.switch name="visibility.contact_link_enabled" :desc="__('The Contact link in the footer. The contact page itself stays reachable.')" />
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Contact button')" :desc="__('Optional wording for the main contact button. Leave empty to keep the default.')">
        <div class="ps-grid">
            <x-ps.field name="visibility.contact_cta_ar" dir="rtl" />
            <x-ps.field name="visibility.contact_cta_en" dir="ltr" />
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Footer')" :desc="__('Leave a field empty to keep the built-in footer wording.')">
        <div class="ps-grid">
            <x-ps.field name="visibility.footer_tagline_ar" dir="rtl" :rows="3" />
            <x-ps.field name="visibility.footer_tagline_en" dir="ltr" :rows="3" />
            <x-ps.field name="visibility.footer_legal_ar" dir="rtl" :rows="2" :hint="__('For example a trademark or intellectual property statement.')" />
            <x-ps.field name="visibility.footer_legal_en" dir="ltr" :rows="2" />
            <x-ps.field name="visibility.copyright_ar" dir="rtl" :hint="__('Write {year} and it becomes the current year automatically.')" data-ps-copy="ar" />
            <x-ps.field name="visibility.copyright_en" dir="ltr" :hint="__('Write {year} and it becomes the current year automatically.')" data-ps-copy="en" />
        </div>
        <div class="ps-preview" aria-label="{{ __('Footer preview') }}">
            <div class="ps-preview-tag" data-ps-prev-tag></div>
            <div class="ps-preview-bar">
                <span data-ps-prev-copy></span>
                <span class="d-flex gap-3 flex-wrap">
                    <a href="#" data-ps-prev-privacy onclick="return false">{{ __('Privacy') }}</a>
                    <a href="#" data-ps-prev-terms onclick="return false">{{ __('Terms') }}</a>
                </span>
            </div>
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Legal pages')" :desc="__('Leave empty to use the built-in Privacy and Terms pages.')">
        <div class="ps-grid">
            <x-ps.field name="visibility.privacy_url" check="url" placeholder="https://… or /privacy" />
            <x-ps.field name="visibility.terms_url" check="url" placeholder="https://… or /terms" />
        </div>
    </x-ps.section>
</x-ps.panel>
