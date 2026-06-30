@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Voice
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('voice.add') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- Name -->
    <div class="mb-3">
        <label class="form-label fw-bold">Name *</label>
        <input type="text" name="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name') }}">
        @error('name')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <!-- Short Tag -->
    <div class="mb-3">
        <label class="form-label fw-bold">Short Tag *</label>
        <input type="text" name="short_tag"
            class="form-control @error('short_tag') is-invalid @enderror"
            value="{{ old('short_tag') }}">
        @error('short_tag')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <!-- Visibility -->
    <div class="mb-3">
        <label class="form-label fw-bold">Visibility Type</label>
        <select name="visibility_type" id="visibilityType" class="form-control">
            <option value="backend">Backend</option>
            <option value="in_app">In App</option>
        </select>
    </div>

    <!-- In App Fields -->
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

    <!-- Icon -->
    <div class="mb-3">
        <label class="form-label fw-bold">Icon *</label>
        <input type="file" name="icon"
            class="form-control @error('icon') is-invalid @enderror"
            accept="image/*">

        @error('icon')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <!-- GIF -->
    <div class="mb-3">
        <label class="form-label fw-bold">Animation</label>
        <input type="file" name="gif"
            class="form-control @error('gif') is-invalid @enderror">

        @error('gif')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <!-- Status -->
    <div class="mb-3">
        <label class="form-label fw-bold">Status</label>
        <select name="status" class="form-control">
            <option value="1">Enable</option>
            <option value="0">Disable</option>
        </select>
    </div>

    <!-- Buttons -->
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('voice') }}" class="btn btn-secondary">Cancel</a>
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

    document.getElementById('addRow').addEventListener('click', function() {
        const wrapper = document.getElementById('pricingWrapper');
        wrapper.insertAdjacentHTML('beforeend', `
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

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeRow')) {
            e.target.closest('.pricing-row').remove();
        }
    });

    document.getElementById('voice').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('audioPreview');

        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        } else {
            preview.classList.add('d-none');
        }
    });
</script>
@endsection