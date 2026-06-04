<form class="form-horizontal" action="{{ $updateRoute ?? '#' }}" method="POST">
    @csrf
    <input type="hidden" name="payment_method" value="tap">

    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-form-label">{{ __('Sandbox Mode') }}</label>
        </div>
        <div class="col-md-8">
            <label class="switch">
                <input type="checkbox" name="TAP_SANDBOX_MODE"
                    @if(config('tap.sandbox')) checked @endif>
                <span class="slider round"></span>
            </label>
            <span class="text-muted fs-12">{{ __('Enable for testing') }}</span>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-form-label">{{ __('Merchant ID') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="TAP_MERCHANT_ID"
                value="{{ config('tap.merchant_id') }}"
                placeholder="{{ __('Enter Tap Merchant ID') }}" required>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-form-label">{{ __('Secret Key') }}</label>
        </div>
        <div class="col-md-8">
            <input type="password" class="form-control" name="TAP_SECRET_KEY"
                value="{{ config('tap.secret_key') }}"
                placeholder="{{ __('Enter Tap Secret Key') }}" required>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-form-label">{{ __('Public Key') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="TAP_PUBLIC_KEY"
                value="{{ config('tap.public_key') }}"
                placeholder="{{ __('Enter Tap Public Key') }}">
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-form-label">{{ __('Currency') }}</label>
        </div>
        <div class="col-md-8">
            <select class="form-control" name="TAP_CURRENCY">
                @foreach(['SAR' => 'Saudi Riyal', 'AED' => 'UAE Dirham', 'KWD' => 'Kuwaiti Dinar', 'BHD' => 'Bahraini Dinar', 'QAR' => 'Qatari Riyal', 'OMR' => 'Omani Riyal', 'USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound', 'EGP' => 'Egyptian Pound', 'JOD' => 'Jordanian Dinar'] as $code => $name)
                    <option value="{{ $code }}" @if(config('tap.currency') === $code) selected @endif>
                        {{ __($name) }} ({{ $code }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group mb-0 text-right">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </div>
</form>
