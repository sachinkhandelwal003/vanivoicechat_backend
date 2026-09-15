@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white fw-bold d-flex align-items-center justify-content-between py-3">
            <span class="fs-6"><i class="fas fa-edit me-2"></i>Edit Coin Package</span>
            <a href="{{ route('coin.package') }}" class="btn btn-sm btn-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>

        <div class="card-body p-4">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Please check the errors below:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('coin.package.edit', $coin->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 mb-3">
                    <!-- Coin Amount -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Coin Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-coins text-warning"></i></span>
                            <input type="number" name="coin" min="1"
                                class="form-control @error('coin') is-invalid @enderror"
                                value="{{ old('coin', $coin->coins) }}" required>
                        </div>
                        @error('coin')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Target Country -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Country <span class="text-muted">(Optional for region targeting)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-globe text-primary"></i></span>
                            <select name="country_id" class="form-select @error('country_id') is-invalid @enderror">
                                <option value="">🌐 All Countries (Global Default)</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ old('country_id', $coin->country_id) == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }} ({{ $country->nicename }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="text-muted">Select specific country or leave as "All Countries" for global availability.</small>
                        @error('country_id')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <!-- Price -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag text-success"></i></span>
                            <input type="number" step="0.01" min="0" name="price"
                                class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price', $coin->price) }}" required>
                        </div>
                        @error('price')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Currency Symbol -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Currency Icon / Symbol <span class="text-danger">*</span></label>
                        <div class="input-group mb-1">
                            <span class="input-group-text"><i class="fas fa-money-bill-wave text-info"></i></span>
                            <input type="text" name="currency_symbol" id="currency_symbol_input"
                                class="form-control @error('currency_symbol') is-invalid @enderror"
                                value="{{ old('currency_symbol', $coin->currency_symbol ?? '$') }}" maxlength="10" required>
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                            <small class="fw-bold text-dark me-1">Quick Select:</small>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="$" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">$ (USD)</button>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="₹" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">₹ (INR)</button>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="€" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">€ (EUR)</button>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="£" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">£ (GBP)</button>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="৳" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">৳ (BDT)</button>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="AED" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">AED</button>
                            <button type="button" class="btn btn-sm currency-btn" data-symbol="SAR" style="color: #1e293b; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px;">SAR</button>
                        </div>
                        @error('currency_symbol')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Bonus Percent -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Bonus Percent (%)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-percent text-info"></i></span>
                            <input type="number" step="0.01" min="0" max="100" name="bonus_percent"
                                class="form-control @error('bonus_percent') is-invalid @enderror"
                                value="{{ old('bonus_percent', $coin->bonus_percent) }}">
                        </div>
                        @error('bonus_percent')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-toggle-on text-primary"></i></span>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', $coin->status) == '1' ? 'selected' : '' }}>Enable</option>
                                <option value="0" {{ old('status', $coin->status) == '0' ? 'selected' : '' }}>Disable</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Coin Icon Upload -->
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Coin Icon
                    </label>

                    <div class="d-flex align-items-start gap-3">
                        <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                        <label for="icon"
                            class="border rounded d-flex align-items-center justify-content-center position-relative bg-light shadow-sm"
                            style="width:120px;height:120px;cursor:pointer;overflow:hidden;transition: all 0.2s;">

                            <img id="coverPreview"
                                src="{{ $coin->icon ? Helper::showImage($coin->icon, true) : '' }}"
                                class="position-absolute w-100 h-100 {{ $coin->icon ? '' : 'd-none' }}"
                                style="object-fit:cover;">

                            <div id="coverPlus" class="text-center text-muted {{ $coin->icon ? 'd-none' : '' }}">
                                <i class="fas fa-plus fs-2"></i>
                                <small class="d-block mt-1" style="font-size:10px;">Click to Upload</small>
                            </div>
                        </label>

                        <div>
                            <small class="d-block text-muted mb-1">
                                <i class="fas fa-info-circle me-1"></i> Leave blank to keep current icon.
                            </small>
                            <small class="d-block text-muted">
                                Recommended size: 50 × 50 pixels
                            </small>
                            @error('icon')
                            <small class="text-danger d-block mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('coin.package') }}" class="btn btn-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fas fa-save me-1"></i> Update Package
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const coverInput = document.getElementById('icon');
        const coverPreview = document.getElementById('coverPreview');
        const coverPlus = document.getElementById('coverPlus');
        const currencyInput = document.getElementById('currency_symbol_input');

        if (coverInput) {
            coverInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    coverPreview.src = e.target.result;
                    coverPreview.classList.remove('d-none');
                    coverPlus.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        }

        // Quick select currency symbol buttons
        document.querySelectorAll('.currency-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const symbol = this.getAttribute('data-symbol');
                if (currencyInput && symbol) {
                    currencyInput.value = symbol;
                }
            });
        });
    });
</script>
@endsection