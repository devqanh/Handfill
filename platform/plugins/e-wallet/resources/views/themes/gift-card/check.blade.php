@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::gift-card.check_balance'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list">
            <div class="bb-customer-card">
                <div class="bb-customer-card-header">
                    <h4 class="bb-customer-card-title mb-0">
                        <x-core::icon name="ti ti-gift" class="me-2" />
                        {{ trans('plugins/e-wallet::gift-card.check_balance') }}
                    </h4>
                </div>
                <div class="bb-customer-card-body">
                    <form id="gift-card-check-form" action="{{ route('public.gift-card.check.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">{{ trans('plugins/e-wallet::gift-card.code') }}</label>
                            <input type="text"
                                   class="form-control form-control-lg text-uppercase"
                                   id="code"
                                   name="code"
                                   placeholder="GC-XXXX-XXXX-XXXXX"
                                   required
                                   maxlength="50"
                                   autocomplete="off">
                            <div class="form-text">{{ trans('plugins/e-wallet::gift-card.code_help') }}</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <x-core::icon name="ti ti-search" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.check_balance') }}
                        </button>
                    </form>

                    <div id="balance-result" class="mt-4" style="display: none;">
                        <hr>
                        <div class="text-center py-3">
                            <h5>{{ trans('plugins/e-wallet::gift-card.your_balance') }}</h5>
                            <p class="display-5 text-success mb-3" id="balance-amount"></p>
                            <p class="text-muted" id="balance-expires"></p>
                            @guest('customer')
                                <a href="{{ route('customer.login') }}?redirect={{ urlencode(route('customer.e-wallet.gift-card.redeem')) }}" class="btn btn-success">
                                    <x-core::icon name="ti ti-login" class="me-1" />
                                    {{ trans('plugins/e-wallet::gift-card.login_to_redeem') }}
                                </a>
                            @else
                                <a href="#" id="redeem-link" class="btn btn-success">
                                    <x-core::icon name="ti ti-wallet" class="me-1" />
                                    {{ trans('plugins/e-wallet::gift-card.redeem_now') }}
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div id="error-result" class="mt-4" style="display: none;">
                        <div class="alert alert-danger mb-0" id="error-message"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
<script>
document.getElementById('gift-card-check-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const resultDiv = document.getElementById('balance-result');
    const errorDiv = document.getElementById('error-result');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ trans('plugins/e-wallet::gift-card.checking') }}';
    resultDiv.style.display = 'none';
    errorDiv.style.display = 'none';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;

        if (data.error) {
            document.getElementById('error-message').textContent = data.message;
            errorDiv.style.display = 'block';
        } else {
            document.getElementById('balance-amount').textContent = data.data.formatted_balance;

            const expiresAt = data.data.expires_at;
            if (expiresAt) {
                const expiresDate = new Date(expiresAt);
                document.getElementById('balance-expires').textContent =
                    '{{ trans('plugins/e-wallet::gift-card.expires') }}: ' + expiresDate.toLocaleDateString();
            } else {
                document.getElementById('balance-expires').textContent = '{{ trans('plugins/e-wallet::gift-card.no_expiry') }}';
            }

            @auth('customer')
            document.getElementById('redeem-link').href = '{{ route('customer.e-wallet.gift-card.redeem') }}?code=' + encodeURIComponent(document.getElementById('code').value);
            @endauth

            resultDiv.style.display = 'block';
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
        document.getElementById('error-message').textContent = '{{ trans('plugins/e-wallet::gift-card.errors.check_failed') }}';
        errorDiv.style.display = 'block';
    });
});
</script>
@endpush
