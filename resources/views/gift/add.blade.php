@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Gift
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('gift.add') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">Gift Type <span class="text-danger">*</span></label><br>

                    <label class="me-3">
                        <input type="radio" name="gift_type" value="ordinary" checked> Ordinary gift
                    </label>

                    <label class="me-3">
                        <input type="radio" name="gift_type" value="luxury"> Luxury gift
                    </label>

                    <label>
                        <input type="radio" name="gift_type" value="hand_painted"> Hand-painted gift
                    </label>
                    @error('gift_type')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Logo</label><br>

                    <label class="me-3">
                        <input type="radio" name="logo" value="gift"> Gift
                    </label>

                    <label class="me-3">
                        <input type="radio" id="logoLucky" name="logo" value="lucky"> Lucky
                    </label>

                    <label class="me-3">
                        <input type="radio" name="logo" value="cp"> CP
                    </label>

                    <label class="me-3">
                        <input type="radio" name="logo" value="national"> National
                    </label>

                    <label>
                        <input type="radio" name="logo" value="activity"> Activity
                    </label>
                    @error('logo')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}">
                    @error('name')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Cover <span class="text-danger">*</span>
                    </label>

                    <div>
                        <input type="file" name="cover" id="cover" class="d-none" accept="image/*">

                        <label for="cover"
                            class="border rounded d-flex align-items-center justify-content-center position-relative"
                            style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                            <img id="coverPreview" class="position-absolute w-100 h-100 d-none"
                                style="object-fit:cover;">
                            <span id="coverPlus" class="fs-1">+</span>
                        </label>

                        <small class="d-block mt-1 text-muted">
                            Recommended size: 50 × 50
                        </small>

                        @error('cover')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Price <span class="text-danger">*</span></label>
                    <input type="text" name="price"
                        class="form-control @error('price') is-invalid @enderror"
                        value="{{ old('price') }}">
                    @error('price')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div id="animationSection" class="border rounded p-3 mb-3 d-none">

                    <label class="form-label fw-bold">Animation Type</label><br>
                    <label class="me-3"><input type="radio" name="animation_type" value="gif" checked> GIF</label>
                    <label><input type="radio" name="animation_type" value="svga"> SVGA</label>

                    <div id="gifSection" class="mt-3">
                        <label class="form-label">GIF Image</label>
                        <input type="file" name="gif_image" class="form-control" accept=".gif">
                    </div>

                    <div id="svgaSection" class="mt-3 d-none">
                        <label class="form-label fw-bold">SVGA File</label>

                        <input type="text"
                            name="svga_path"
                            id="svgaPath"
                            class="form-control mb-2"
                            placeholder="Enter full SVGA URL (https://...)">

                        <input type="file"
                            id="svgaFile"
                            name="svga_file"
                            class="d-none"
                            accept=".svga">

                        <button type="button"
                            class="btn btn-outline-secondary btn-sm"
                            id="svgaBrowse" style="color: grey !important;">
                            Browse from computer
                        </button>

                        <small class="text-muted d-block mt-1">
                            Paste URL OR upload .svga file
                        </small>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Animation Duration (seconds)</label>
                        <input type="number" step="0.01" name="animation_duration"
                            class="form-control" value="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Select Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('gift') }}" class="btn btn-secondary">Cancel</a>
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


        const coverInput = document.getElementById('cover');
        const coverPreview = document.getElementById('coverPreview');
        const coverPlus = document.getElementById('coverPlus');

        coverInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                coverPreview.src = e.target.result;
                coverPreview.classList.remove('d-none');
                // coverPlus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

        const giftTypeRadios = document.querySelectorAll('input[name="gift_type"]');
        const animationSection = document.getElementById('animationSection');
        const luckyCheckbox = document.getElementById('logoLucky');

        giftTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'luxury') {
                    animationSection.classList.remove('d-none');
                    luckyCheckbox.checked = false;
                    luckyCheckbox.disabled = true;
                } else {
                    animationSection.classList.add('d-none');
                    luckyCheckbox.disabled = false;
                }
            });
        });

        const animationRadios = document.querySelectorAll('input[name="animation_type"]');
        const gifSection = document.getElementById('gifSection');
        const svgaSection = document.getElementById('svgaSection');

        animationRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'gif') {
                    gifSection.classList.remove('d-none');
                    svgaSection.classList.add('d-none');
                } else {
                    gifSection.classList.add('d-none');
                    svgaSection.classList.remove('d-none');
                }
            });
        });

        document.getElementById('svgaBrowse').addEventListener('click', () => {
            document.getElementById('svgaFile').click();
        });

        document.getElementById('svgaFile').addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('svgaPath').value = '';
            }
        });

    });
</script>

@endsection