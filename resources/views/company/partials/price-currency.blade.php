@php
    $currencies      = config('booksy.currencies');
    $defaultCurrency = config('booksy.default_currency', 'SYP');
    $selectedCurrency = old('currency', $currentCurrency ?? $defaultCurrency);
    $locale = app()->getLocale();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="price">
            {{ __('Price') }} <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            {{-- Currency selector --}}
            <button type="button"
                    class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1 px-3"
                    data-bs-toggle="dropdown" aria-expanded="false"
                    id="currency-btn"
                    style="min-width:90px;">
                <span id="currency-symbol" class="fw-semibold">
                    {{ $currencies[$selectedCurrency]['symbol'] ?? $selectedCurrency }}
                </span>
                <span id="currency-code" class="text-muted small">{{ $selectedCurrency }}</span>
            </button>

            <ul class="dropdown-menu shadow-sm" style="min-width:220px; max-height:280px; overflow-y:auto;">
                @foreach ($currencies as $code => $info)
                <li>
                    <a class="dropdown-item d-flex justify-content-between align-items-center py-2 currency-option
                               {{ $code === $selectedCurrency ? 'active' : '' }}"
                       href="#" data-code="{{ $code }}" data-symbol="{{ $info['symbol'] }}">
                        <span>
                            <span class="fw-semibold me-1">{{ $info['symbol'] }}</span>
                            {{ $locale === 'ar' ? $info['name_ar'] : $info['name_en'] }}
                        </span>
                        <small class="text-muted ms-2">{{ $code }}</small>
                    </a>
                </li>
                @endforeach
            </ul>

            <input type="hidden" name="currency" id="currency-input" value="{{ $selectedCurrency }}">

            <input type="number" id="price" name="price"
                   value="{{ old('price', $currentPrice ?? 0) }}"
                   class="form-control @error('price') is-invalid @enderror"
                   min="0" step="0.01" required>
        </div>
        @error('price')    <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        @error('currency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold" for="duration_minutes">
            {{ __('Duration (minutes)') }} <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number" id="duration_minutes" name="duration_minutes"
                   value="{{ old('duration_minutes', $currentDuration ?? 30) }}"
                   class="form-control @error('duration_minutes') is-invalid @enderror"
                   min="1" max="1440" required>
            <span class="input-group-text">{{ __('min') }}</span>
        </div>
        @error('duration_minutes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    document.querySelectorAll('.currency-option').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            var code   = this.dataset.code;
            var symbol = this.dataset.symbol;

            document.getElementById('currency-input').value  = code;
            document.getElementById('currency-symbol').textContent = symbol;
            document.getElementById('currency-code').textContent   = code;

            document.querySelectorAll('.currency-option').forEach(function (o) {
                o.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
})();
</script>
@endpush
@endonce
