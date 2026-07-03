@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Chat Bubble
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('chat.bubble.add') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold"> Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}">
                    @error('name')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Visibility Type</label>
                    <select name="visibility_type" id="visibilityType" class="form-control">
                        <option value="backend">Backend</option>
                        <option value="in_app">In App</option>
                    </select>

                </div>

                <div id="inAppFields" class="d-none">

                    @if ($errors->has('needcoin.*') || $errors->has('validity.*'))
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->get('needcoin.*') as $messages)
                            @foreach ($messages as $message)
                            <li>{{ $message }}</li>
                            @endforeach
                            @endforeach

                            @foreach ($errors->get('validity.*') as $messages)
                            @foreach ($messages as $message)
                            <li>{{ $message }}</li>
                            @endforeach
                            @endforeach
                        </ul>
                    </div>
                    @endif

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


                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Picture <span class="text-danger">*</span>
                    </label>

                    <div>
                        <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                        <label for="icon"
                            class="border rounded d-flex align-items-center justify-content-center position-relative"
                            style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                            <img id="coverPreview" class="position-absolute w-100 h-100 d-none"
                                style="object-fit:cover;">
                            <span id="coverPlus" class="fs-1">+</span>
                        </label>

                        <small class="d-block mt-1 text-muted">
                            Recommended size: 50 × 50
                        </small>

                        @error('icon')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"> Slice Rect <span class="text-danger">*</span></label>
                    <input type="text" name="slice_rect"
                        class="form-control @error('slice_rect') is-invalid @enderror"
                        value="{{ old('slice_rect') }}">
                    @error('slice_rect')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"> Padding Rect <span class="text-danger">*</span></label>
                    <input type="text" name="padding_rect"
                        class="form-control @error('padding_rect') is-invalid @enderror"
                        value="{{ old('padding_rect') }}">
                    @error('padding_rect')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1">Enable</option>
                        <option value="0">Disable</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('chat.bubble') }}" class="btn btn-secondary">Cancel</a>
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

        const coverInput = document.getElementById('icon');
        const coverPreview = document.getElementById('coverPreview');
        const coverPlus = document.getElementById('coverPlus');

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
</script>
@endsection