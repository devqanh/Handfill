@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    <div class="widget meta-boxes">
                        <div class="widget-title">
                            <h4>{{ trans('plugins/e-wallet::e-wallet.name') }}</h4>
                        </div>
                        <div class="widget-body">
                            <p>{{ trans('plugins/e-wallet::e-wallet.description') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
