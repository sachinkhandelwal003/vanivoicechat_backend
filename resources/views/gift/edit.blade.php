@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Update Gift
        </div>

        <div class="card-body">
            <form action="{{ route('gift.edit', $gift->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="fw-bold">Gift Type *</label><br>
                    @foreach(['ordinary','luxury','hand_painted'] as $type)
                    <label class="me-3">
                        <input type="radio" name="gift_type" value="{{ $type }}"
                            {{ old('gift_type',$gift->gift_type) === $type ? 'checked' : '' }}>
                        {{ ucfirst(str_replace('_',' ',$type)) }}
                    </label>
                    @endforeach
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Logo *</label><br>
                    @foreach(['gift','lucky','cp','national','activity'] as $logo)
                    <label class="me-3">
                        <input type="radio" name="logo" value="{{ $logo }}"
                            {{ old('logo',$gift->cover_type) === $logo ? 'checked' : '' }}
                            {{ $logo === 'lucky' ? 'id=logoLucky' : '' }}>
                        {{ ucfirst($logo) }}
                    </label>
                    @endforeach
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Name *</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name',$gift->name) }}">
                </div>

                <div class="mb-4">
                    <label class="fw-bold">Cover *</label><br>
                    <input type="file" name="cover" id="cover" class="d-none" accept="image/*">
                    <label for="cover"
                        class="border rounded d-flex align-items-center justify-content-center"
                        style="width:150px;height:120px;cursor:pointer">
                        <img id="coverPreview"
                            src="{{ Helper::showImage($gift->cover,true) }}"
                            style="width:100%;height:100%;object-fit:cover">
                    </label>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Price *</label>
                    <input type="text" name="price" class="form-control"
                        value="{{ old('price',$gift->price) }}">
                </div>

                <div id="animationSection"
                    class="border rounded p-3 mb-3 {{ $gift->gift_type === 'luxury' ? '' : 'd-none' }}">

                    <label class="fw-bold">Animation Type</label><br>
                    <label class="me-3">
                        <input type="radio" name="animation_type" value="gif"
                            {{ $gift->animation_type === 'gif' ? 'checked' : '' }}>
                        GIF
                    </label>
                    <label>
                        <input type="radio" name="animation_type" value="svga"
                            {{ $gift->animation_type === 'svga' ? 'checked' : '' }}>
                        SVGA
                    </label>

                    <div id="gifSection"
                        class="mt-3 {{ $gift->animation_type === 'gif' ? '' : 'd-none' }}">
                        <input type="file" name="gif_image" class="form-control mb-2">
                        <input type="text" name="gif_path" class="form-control"
                            value="{{ $gift->animation_type === 'gif' ? $gift->file_path : '' }}">
                    </div>

                    <div id="svgaSection"
                        class="mt-3 {{ $gift->animation_type === 'svga' ? '' : 'd-none' }}">

                        <input type="text"
                            name="svga_path"
                            id="svgaPath"
                            class="form-control mb-2"
                            value="{{ $gift->animation_type === 'svga' ? $gift->file_path : '' }}"
                            placeholder="Enter full SVGA URL">

                        <input type="file" id="svgaFile" name="svga_file" class="d-none" accept=".svga">

                        <button type="button" id="svgaBrowse"
                            class="btn btn-outline-secondary btn-sm" style="color: grey !important;">
                            Browse from computer
                        </button>
                    </div>

                    <div class="mt-3">
                        <label>Animation Duration</label>
                        <input type="number" step="0.01" name="animation_duration"
                            class="form-control"
                            value="{{ old('animation_duration',$gift->animation_duration) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ $gift->status ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$gift->status ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <button class="btn btn-primary">Update</button>
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


        const giftRadios = document.querySelectorAll('input[name="gift_type"]');
        const animationSection = document.getElementById('animationSection');
        const luckyRadio = document.getElementById('logoLucky');

        giftRadios.forEach(r => {
            r.addEventListener('change', () => {
                if (r.value === 'luxury') {
                    animationSection.classList.remove('d-none');
                    if (luckyRadio) {
                        luckyRadio.checked = false;
                        luckyRadio.disabled = true;
                    }
                } else {
                    animationSection.classList.add('d-none');
                    if (luckyRadio) luckyRadio.disabled = false;
                }
            });
        });

        const animRadios = document.querySelectorAll('input[name="animation_type"]');
        const gifSection = document.getElementById('gifSection');
        const svgaSection = document.getElementById('svgaSection');

        animRadios.forEach(r => {
            r.addEventListener('change', () => {
                gifSection.classList.toggle('d-none', r.value !== 'gif');
                svgaSection.classList.toggle('d-none', r.value !== 'svga');
            });
        });

        document.getElementById('svgaBrowse')?.addEventListener('click', () => {
            document.getElementById('svgaFile').click();
        });

        document.getElementById('svgaFile')?.addEventListener('change', function() {
            if (this.files.length) {
                document.getElementById('svgaPath').value = this.files[0].name;
            }
        });

    });
</script>
@endsection