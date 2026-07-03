@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Edit Store UID
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('store.uid.edit', $storeUid->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Rank --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Rank</label>
                    <select name="rank" class="form-control">
                        @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}" {{ $storeUid->rank_id == $rank->id ? 'selected' : '' }}>
                            {{ $rank->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pattern --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Pattern</label>
                    <select name="pattern" class="form-control">
                        @foreach($patterns as $pattern)
                        <option value="{{ $pattern->id }}" {{ $storeUid->pattern_id == $pattern->id ? 'selected' : '' }}>
                            {{ $pattern->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- UID --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Unique ID</label>
                    <input type="text" name="uid" class="form-control"
                        value="{{ old('uid', $storeUid->unique_id) }}">
                </div>

                {{-- Visibility --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Visibility Type</label>
                    <select name="visibility_type" id="visibilityType" class="form-control">
                        <option value="backend" {{ $storeUid->visibility_type == 'backend' ? 'selected' : '' }}>
                            Backend
                        </option>
                        <option value="in_app" {{ $storeUid->visibility_type == 'in_app' ? 'selected' : '' }}>
                            In App
                        </option>
                    </select>
                </div>

                {{-- Pricing --}}
                @php
                    $needCoins = $storeUid->needcoin ?? [];
                    $validity = $storeUid->validity ?? [];

                    if (!is_array($needCoins)) $needCoins = json_decode($needCoins, true) ?? [];
                    if (!is_array($validity)) $validity = json_decode($validity, true) ?? [];
                @endphp

                <div id="inAppFields" class="{{ $storeUid->visibility_type == 'in_app' ? '' : 'd-none' }}">

                    <div id="pricingWrapper">
                        @forelse($needCoins as $index => $coin)
                        <div class="row g-2 mb-2 pricing-row">
                            <div class="col-5">
                                <input type="number" name="needcoin[]" class="form-control"
                                    value="{{ $coin }}" min="1">
                            </div>
                            <div class="col-5">
                                <input type="number" name="validity[]" class="form-control"
                                    value="{{ $validity[$index] ?? '' }}" min="1">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-danger removeRow {{ $index == 0 ? 'd-none' : '' }}">×</button>
                            </div>
                        </div>
                        @empty
                        <div class="row g-2 mb-2 pricing-row">
                            <div class="col-5">
                                <input type="number" name="needcoin[]" class="form-control" min="1">
                            </div>
                            <div class="col-5">
                                <input type="number" name="validity[]" class="form-control" min="1">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-danger removeRow d-none">×</button>
                            </div>
                        </div>
                        @endforelse
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                        + Add Option
                    </button>
                </div>

                {{-- Badge --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Picture</label>

                    <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                    <label for="icon" class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="coverPreview"
                            src="{{ $storeUid->badge ? asset('storage/'.$storeUid->badge) : '' }}"
                            class="position-absolute w-100 h-100 {{ $storeUid->badge ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="coverPlus" class="fs-1 {{ $storeUid->badge ? 'd-none' : '' }}">+</span>
                    </label>
                </div>

                {{-- Rank Badge --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Rank Badge</label>

                    <input type="file" name="rank_badge" id="rank_badge" class="d-none" accept="image/*">

                    <label for="rank_badge" class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="rankPreview"
                            src="{{ $storeUid->rank_badge ? asset('storage/'.$storeUid->rank_badge) : '' }}"
                            class="position-absolute w-100 h-100 {{ $storeUid->rank_badge ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="rankPlus" class="fs-1 {{ $storeUid->rank_badge ? 'd-none' : '' }}">+</span>
                    </label>
                </div>

                 <div class="mb-3 col-4">
                    <label class="form-label fw-bold">Rank Badge Color <span class="text-danger">*</span></label>
                    <input type="color" name="rank_badge_color"
                        class="form-control @error('rank_badge_color') is-invalid @enderror"
                        value="{{ old('rank_badge_color', $storeUid->rank_badge_color) }}">
                    @error('rank_badge_color')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ $storeUid->status == 1 ? 'selected' : '' }}>Enable</option>
                        <option value="0" {{ $storeUid->status == 0 ? 'selected' : '' }}>Disable</option>
                    </select>
                </div>

                {{-- Submit --}}
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('store.uid') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Toggle Pricing
    const visibilityType = document.getElementById('visibilityType');
    const inAppFields = document.getElementById('inAppFields');

    function togglePricing() {
        visibilityType.value === 'in_app'
            ? inAppFields.classList.remove('d-none')
            : inAppFields.classList.add('d-none');
    }

    visibilityType.addEventListener('change', togglePricing);
    togglePricing();

    // Add pricing row
    document.getElementById('addRow').addEventListener('click', function() {
        document.getElementById('pricingWrapper').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 pricing-row">
            <div class="col-5">
                <input type="number" name="needcoin[]" class="form-control" min="1">
            </div>
            <div class="col-5">
                <input type="number" name="validity[]" class="form-control" min="1">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger removeRow">×</button>
            </div>
        </div>
        `);
    });

    // Remove row
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeRow')) {
            e.target.closest('.pricing-row').remove();
        }
    });

    // Image Preview - Badge
    document.getElementById('icon').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            coverPreview.src = e.target.result;
            coverPreview.classList.remove('d-none');
            coverPlus.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });

    // Image Preview - Rank Badge
    document.getElementById('rank_badge').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            rankPreview.src = e.target.result;
            rankPreview.classList.remove('d-none');
            rankPlus.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endsection
