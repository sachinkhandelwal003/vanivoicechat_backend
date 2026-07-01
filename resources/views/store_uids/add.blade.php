@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Id
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('store.uid.add') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Rank --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Rank</label>
                    <select name="rank" id="rank" class="form-control">
                        @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}">{{ $rank->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pattern --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Pattern</label>
                    <select name="pattern" id="pattern" class="form-control">
                        @foreach($patterns as $pattern)
                        <option value="{{ $pattern->id }}">{{ $pattern->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- UID --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Unique Id <span class="text-danger">*</span></label>
                    <input type="text" name="uid"
                        class="form-control @error('uid') is-invalid @enderror"
                        value="{{ old('uid') }}">
                    @error('uid')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Visibility --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Visibility Type</label>
                    <select name="visibility_type" id="visibilityType" class="form-control">
                        <option value="backend">Backend</option>
                        <option value="in_app">In App</option>
                    </select>
                </div>

                {{-- In-App Pricing --}}
                <div id="inAppFields" class="d-none">

                    <div id="pricingWrapper">
                        <div class="row g-2 mb-2 pricing-row">
                            <div class="col-5">
                                <input type="number" name="needcoin[]" class="form-control"
                                    placeholder="Coins" min="1">
                            </div>
                            <div class="col-5">
                                <input type="number" name="validity[]" class="form-control"
                                    placeholder="Days" min="1">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-danger removeRow d-none">×</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                        + Add Option
                    </button>
                </div>

                {{-- Icon --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Picture <span class="text-danger">*</span></label>

                    <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                    <label for="icon"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                        <img id="coverPreview" class="position-absolute w-100 h-100 d-none" style="object-fit:cover;">
                        <span id="coverPlus" class="fs-1">+</span>
                    </label>

                    @error('icon')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Rank Badge --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Rank Badge <span class="text-danger">*</span></label>
                    <input type="file" name="rank_badge" id="rank_badge" class="d-none" accept="image/*">
                    <label for="rank_badge"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                        <img id="rankPreview" class="position-absolute w-100 h-100 d-none" style="object-fit:cover;">
                        <span id="rankPlus" class="fs-1">+</span>
                    </label>

                    @error('rank_badge')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3 col-4">
                    <label class="form-label fw-bold">Rank Badge Color <span class="text-danger">*</span></label>
                    <input type="color" name="rank_badge_color"
                        class="form-control @error('rank_badge_color') is-invalid @enderror"
                        value="{{ old('rank_badge_color') }}">
                    @error('rank_badge_color')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1">Enable</option>
                        <option value="0">Disable</option>
                    </select>
                </div>

                {{-- Submit --}}
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('store.uid') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Icon Preview
        const iconInput = document.getElementById('icon');
        const coverPreview = document.getElementById('coverPreview');
        const coverPlus = document.getElementById('coverPlus');

        iconInput.addEventListener('change', function() {
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

        // Rank Badge Preview
        const rankInput = document.getElementById('rank_badge');
        const rankPreview = document.getElementById('rankPreview');
        const rankPlus = document.getElementById('rankPlus');

        rankInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                rankPreview.src = e.target.result;
                rankPreview.classList.remove('d-none');
                rankPlus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

        // Toggle Pricing Fields
        const visibilityType = document.getElementById('visibilityType');
        const inAppFields = document.getElementById('inAppFields');

        function toggleInAppFields() {
            if (visibilityType.value === 'in_app') {
                inAppFields.classList.remove('d-none');
            } else {
                inAppFields.classList.add('d-none');
            }
        }

        visibilityType.addEventListener('change', toggleInAppFields);
        toggleInAppFields();
    });

    // Add Pricing Rows
    document.getElementById('addRow').addEventListener('click', function() {
        const wrapper = document.getElementById('pricingWrapper');
        wrapper.insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 pricing-row">
            <div class="col-5">
                <input type="number" name="needcoin[]" class="form-control" min="1" placeholder="Coins">
            </div>
            <div class="col-5">
                <input type="number" name="validity[]" class="form-control" min="1" placeholder="Days">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger removeRow">×</button>
            </div>
        </div>
    `);
    });

    // Remove Row
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeRow')) {
            e.target.closest('.pricing-row').remove();
        }
    });
</script>
@endsection