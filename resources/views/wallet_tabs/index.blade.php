@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-1 text-primary fw-bold">
                    <i class="fas fa-wallet me-2"></i>Wallet Tab Settings
                </h5>
                <small class="text-muted">Control the visibility (Show / Hide) of Wallet action tabs on the Mobile App</small>
            </div>
            <div>
                <span class="badge bg-soft-info text-info p-2 rounded-pill">
                    <i class="fas fa-mobile-alt me-1"></i> Mobile App Settings
                </span>
            </div>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-4">

                {{-- Left Column: Interactive Tab Configuration --}}
                <div class="col-lg-7">
                    <div class="card border shadow-sm">
                        <div class="card-header bg-light fw-bold text-dark">
                            <i class="fas fa-sliders-h me-2 text-primary"></i>Wallet Tabs Visibility Controls
                        </div>
                        <div class="card-body">
                            <form action="{{ route('wallet-tabs.update') }}" method="POST" id="walletTabsForm">
                                @csrf

                                <div class="d-flex flex-column gap-3">
                                    @foreach($tabs as $tab)
                                    <div class="card border rounded-3 p-3 position-relative tab-card" id="tab-card-{{ $tab->id }}">
                                        <input type="hidden" name="tabs[{{ $tab->id }}][id]" value="{{ $tab->id }}">

                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle p-3 d-flex align-items-center justify-content-center"
                                                     style="width: 50px; height: 50px; background-color: {{ $tab->tab_key === 'exchange' ? '#fff4e5' : ($tab->tab_key === 'transfer' ? '#e8f2ff' : '#ffe8e8') }}; color: {{ $tab->tab_key === 'exchange' ? '#ff9800' : ($tab->tab_key === 'transfer' ? '#2196f3' : '#e91e63') }}; font-size: 1.25rem;">
                                                    <i class="{{ $tab->icon_class ?? 'fas fa-cog' }}"></i>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <h6 class="mb-0 fw-bold">{{ $tab->tab_name }}</h6>
                                                        <span class="badge {{ $tab->status ? 'bg-success' : 'bg-danger' }} status-badge-{{ $tab->id }}">
                                                            {{ $tab->status ? 'Visible in App' : 'Hidden in App' }}
                                                        </span>
                                                    </div>
                                                    <small class="text-muted">Key: <code>{{ $tab->tab_key }}</code></small>
                                                </div>
                                            </div>

                                            <div class="form-check form-switch form-switch-md">
                                                <input class="form-check-input toggle-tab-switch"
                                                       type="checkbox"
                                                       role="switch"
                                                       id="switch-{{ $tab->id }}"
                                                       data-id="{{ $tab->id }}"
                                                       data-key="{{ $tab->tab_key }}"
                                                       name="tabs[{{ $tab->id }}][status]"
                                                       value="1"
                                                       {{ $tab->status ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold ms-2 cursor-pointer" for="switch-{{ $tab->id }}">
                                                    <span class="switch-label-text-{{ $tab->id }}">{{ $tab->status ? 'Show' : 'Hide' }}</span>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- <hr class="my-3">

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Tab Display Name</label>
                                                <input type="text" class="form-control form-control-sm" name="tabs[{{ $tab->id }}][tab_name]" value="{{ $tab->tab_name }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Sub Title / Description</label>
                                                <input type="text" class="form-control form-control-sm" name="tabs[{{ $tab->id }}][sub_title]" value="{{ $tab->sub_title }}">
                                            </div>
                                        </div> --}}
                                    </div>
                                    @endforeach
                                </div>

                                {{-- <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div> --}}
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right Column: App Live Preview Mockup --}}
                <div class="col-lg-5">
                    <div class="card border shadow-sm">
                        <div class="card-header bg-light fw-bold text-dark">
                            <i class="fas fa-mobile-alt me-2 text-primary"></i>App Mobile Wallet Screen Preview
                        </div>
                        <div class="card-body d-flex justify-content-center bg-light py-4">

                            {{-- Phone Frame --}}
                            <div class="phone-mockup border shadow rounded-4 bg-white p-3" style="width: 320px; min-height: 520px; font-family: sans-serif;">
                                {{-- Phone Header --}}
                                <div class="text-center py-2 border-bottom mb-3">
                                    <span class="fw-bold text-dark fs-6"><i class="fas fa-chevron-left me-2 text-muted" style="font-size: 0.8rem;"></i> Wallet</span>
                                </div>

                                {{-- Wallet Balance Banner --}}
                                <div class="rounded-4 p-4 mb-4 text-white position-relative shadow-sm" style="background: linear-gradient(135deg, #6b3ba7 0%, #a259ff 100%); overflow: hidden;">
                                    <div class="small opacity-75 fw-semibold mb-1">Wallet Balance — USD</div>
                                    <div class="h2 fw-bold mb-0">$245.60</div>
                                    <div class="position-absolute end-0 bottom-0 p-3 opacity-25">
                                        <i class="fas fa-wallet fa-3x"></i>
                                    </div>
                                </div>

                                {{-- Action Tabs Container --}}
                                <div class="text-muted small fw-bold mb-2">ACTIONS AVAILABLE IN APP</div>
                                <div class="row g-2 id="app-preview-tabs-container">
                                    @foreach($tabs as $tab)
                                    <div class="col-4 preview-tab-col-{{ $tab->tab_key }}" style="{{ $tab->status ? '' : 'display: none !important;' }}">
                                        <div class="card border-0 rounded-4 text-center p-2 shadow-sm h-100" style="background-color: #f8f9fa;">
                                            <div class="mx-auto rounded-3 p-2 mb-2 d-flex align-items-center justify-content-center"
                                                 style="width: 38px; height: 38px; background-color: {{ $tab->tab_key === 'exchange' ? '#fff4e5' : ($tab->tab_key === 'transfer' ? '#e8f2ff' : '#ffe8e8') }}; color: {{ $tab->tab_key === 'exchange' ? '#ff9800' : ($tab->tab_key === 'transfer' ? '#2196f3' : '#e91e63') }};">
                                                <i class="{{ $tab->icon_class ?? 'fas fa-circle' }}"></i>
                                            </div>
                                            <div class="fw-bold text-dark small" style="font-size: 0.75rem;">{{ $tab->tab_name }}</div>
                                            <div class="text-muted" style="font-size: 0.65rem;">{{ $tab->sub_title }}</div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                {{-- Hidden Notice --}}
                                <div class="mt-4 p-3 rounded-3 bg-light text-center border">
                                    <small class="text-muted d-block">
                                        <i class="fas fa-info-circle me-1 text-primary"></i>
                                        Tabs toggled to <strong class="text-danger">Hide</strong> will immediately disappear from the Mobile App Wallet screen.
                                    </small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // Handle instant toggle status switch change via AJAX
    $('.toggle-tab-switch').on('change', function() {
        let switchElem = $(this);
        let tabId = switchElem.data('id');
        let tabKey = switchElem.data('key');
        let isChecked = switchElem.is(':checked') ? 1 : 0;

        // Update switch label and badge immediately
        $('.switch-label-text-' + tabId).text(isChecked ? 'Show' : 'Hide');
        let statusBadge = $('.status-badge-' + tabId);

        if (isChecked) {
            statusBadge.removeClass('bg-danger').addClass('bg-success').text('Visible in App');
            $('.preview-tab-col-' + tabKey).removeClass('d-none').show();
        } else {
            statusBadge.removeClass('bg-success').addClass('bg-danger').text('Hidden in App');
            $('.preview-tab-col-' + tabKey).addClass('d-none').hide();
        }

        // Send AJAX request
        $.ajax({
            url: "{{ route('wallet-tabs.toggle-status') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: tabId,
                status: isChecked
            },
            success: function(response) {
                if (response.status) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Error updating status');
                    }
                }
            },
            error: function(xhr) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to update tab status');
                }
            }
        });
    });

});
</script>
@endpush
