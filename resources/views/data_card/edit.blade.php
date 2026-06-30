@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Edit Data Card
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('data.card.edit', $dataCard->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold"> Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $dataCard->name) }}">
                    @error('name')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Visibility Type</label>
                    <select name="visibility_type" id="visibilityType" class="form-control">
                        <option value="backend" {{ $dataCard->visibility_type === 'backend' ? 'selected' : '' }}>
                            Backend
                        </option>
                        <option value="in_app" {{ $dataCard->visibility_type === 'in_app' ? 'selected' : '' }}>
                            In App
                        </option>
                    </select>

                </div>

                <div id="inAppFields" class="{{ $dataCard->visibility_type === 'in_app' ? '' : 'd-none' }}">

                    @php
                    $needcoins = old('needcoin', $dataCard->needcoin ?? []);
                    $validities = old('validity', $dataCard->validity ?? []);

                    if (!is_array($needcoins)) $needcoins = json_decode($needcoins, true) ?? [];
                    if (!is_array($validities)) $validities = json_decode($validities, true) ?? [];
                    @endphp

                    <!-- <div id="inAppFields"
                        class="{{ old('visibility_type', $dataCard->visibility_type) === 'in_app' ? '' : 'd-none' }}"> -->

                        <div id="pricingWrapper">

                            @forelse($needcoins as $index => $coin)
                            <div class="row g-2 mb-2 pricing-row">
                                <div class="col-5">
                                    <input type="number"
                                        name="needcoin[]"
                                        class="form-control @error('needcoin.' . $index) is-invalid @enderror"
                                        value="{{ $coin }}"
                                        min="1"
                                        placeholder="Coins">

                                    @error('needcoin.' . $index)
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-5">
                                    <input type="number"
                                        name="validity[]"
                                        class="form-control @error('validity.' . $index) is-invalid @enderror"
                                        value="{{ $validities[$index] ?? '' }}"
                                        min="1"
                                        placeholder="Days">

                                    @error('validity.' . $index)
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-2">
                                    <button type="button"
                                        class="btn btn-danger removeRow {{ $index === 0 ? 'd-none' : '' }}">
                                        ×
                                    </button>
                                </div>
                            </div>
                            @empty
                            {{-- fallback if no pricing exists --}}
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
                    <!-- </div> -->

                </div>


                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Picture <span class="text-danger">*</span>
                    </label>

                    <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                    <label for="icon"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="coverPreview"
                            src="{{ $dataCard->icon ? Helper::showImage($dataCard->icon, true) : '' }}"
                            class="position-absolute w-100 h-100 {{ $dataCard->icon ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="coverPlus" class="fs-1 {{ $dataCard->icon ? 'd-none' : '' }}">+</span>
                    </label>

                    @error('icon')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Animation <span class="text-danger">*</span>
                    </label>

                    <input type="file" name="animation" id="animation"
                        class="form-control @error('animation') is-invalid @enderror">

                    @if(!empty($dataCard->gif))
                    <small class="d-block mt-1 text-muted">
                        Current File:
                        <a href="{{ Helper::showImage($dataCard->gif, true) }}" target="_blank">
                            View Animation
                        </a>
                    </small>
                    @endif

                    @error('animation')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>



                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ $dataCard->status == '1' ? 'selected' : '' }}>Enable</option>
                        <option value="0" {{ $dataCard->status == '0' ? 'selected' : '' }}>Disable</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('data.card') }}" class="btn btn-secondary">Cancel</a>
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

        const animationInput = document.getElementById('animation');
        const animationPreview = document.getElementById('animationPreview');
        const animationPlus = document.getElementById('animationPlus');

        animationInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                animationPreview.src = e.target.result;
                animationPreview.classList.remove('d-none');
                animationPlus.classList.add('d-none');
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
</script>

<script>
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

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeRow')) {
            e.target.closest('.pricing-row').remove();
        }
    });
</script>
@endsection