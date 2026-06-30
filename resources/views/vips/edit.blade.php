@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Edit VIP</h5>
                <p class="text-muted mb-0">Update VIP package details and assets.</p>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(104, 'can_add'))
                    <a href="{{ route('vip') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Back
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="card-body p-4">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('vip.edit', $vip->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-4">

                    <div class="col-12">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                            class="form-control rounded-3 @error('name') is-invalid @enderror"
                            value="{{ old('name', $vip->name) }}"
                            placeholder="Enter VIP name">
                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12 row mt-3">
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold">Days <span class="text-danger">*</span></label>
                            <input type="number" name="day"
                                class="form-control rounded-3 @error('day') is-invalid @enderror"
                                value="{{ old('day', $vip->days) }}"
                                placeholder="Enter days">
                            @error('day')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold">Coins <span class="text-danger">*</span></label>
                            <input type="number" name="coin"
                                class="form-control rounded-3 @error('coin') is-invalid @enderror"
                                value="{{ old('coin', $vip->needcoins) }}"
                                placeholder="Enter coins">
                            @error('coin')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Background Color</label>
                            @php
                            $selectedColor = old('color', $vip->color ?? '#6b2dd7');
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <input type="color"
                                    name="color"
                                    id="vip_color"
                                    class="color-picker" value="{{ $selectedColor }}">

                                <input type="text"
                                    id="vip_color_text"
                                    class="form-control"
                                    value="{{ $selectedColor }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold">Name Color <span class="text-danger">*</span></label>
                            <input type="text" name="name_color"
                                class="form-control rounded-3 @error('name_color') is-invalid @enderror"
                                value="{{ old('name_color', $vip->username) }}"
                                placeholder="Enter name color">
                            @error('name_color')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="my-4"></div>

                    @php
                    $uploadFields = [
                    [
                    'label' => 'Badge',
                    'name' => 'badge',
                    'value' => $vip->badge ?? null,
                    ],
                    [
                    'label' => 'Entry Tag',
                    'name' => 'entry_tag',
                    'value' => $vip->entry_tag ?? null,
                    ],
                    [
                    'label' => 'Chat Card',
                    'name' => 'chat_card',
                    'value' => $vip->chat_card ?? null,
                    ],
                    [
                    'label' => 'Avatar',
                    'name' => 'avatar',
                    'value' => $vip->image_frame ?? null,
                    ],
                    [
                    'label' => 'Frame',
                    'name' => 'frame',
                    'value' => $vip->profile_frame ?? null,
                    ],
                    ];
                    @endphp

                    @foreach($uploadFields as $field)
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                            <label class="form-label fw-semibold mb-3">
                                {{ $field['label'] }}
                            </label>

                            <input type="file"
                                name="{{ $field['name'] }}"
                                id="{{ $field['name'] }}"
                                class="d-none image-input @error($field['name']) is-invalid @enderror"
                                accept="image/png,image/jpeg,image/jpg,image/webp">

                            <label for="{{ $field['name'] }}"
                                class="upload-box w-100 rounded-4 border border-2 border-dashed d-flex flex-column align-items-center justify-content-center text-center position-relative overflow-hidden bg-white"
                                style="height: 220px; cursor: pointer;">

                                @if(!empty($field['value']))
                                <img src="{{ Helper::showImage($field['value'], true) }}"
                                    alt="Preview"
                                    class="preview-image position-absolute top-0 start-0 w-100 h-100"
                                    style="object-fit: cover;">
                                <div class="upload-placeholder px-3 d-none">
                                    <div class="upload-icon mb-2">+</div>
                                    <h6 class="mb-1 fw-bold">Click to change</h6>
                                    <p class="text-muted small mb-1">PNG, JPG, JPEG, WEBP</p>
                                </div>
                                @else
                                <img src=""
                                    alt="Preview"
                                    class="preview-image d-none position-absolute top-0 start-0 w-100 h-100"
                                    style="object-fit: cover;">
                                <div class="upload-placeholder px-3">
                                    <div class="upload-icon mb-2">+</div>
                                    <h6 class="mb-1 fw-bold">Click to upload</h6>
                                    <p class="text-muted small mb-1">PNG, JPG, JPEG, WEBP</p>
                                </div>
                                @endif
                            </label>

                            <div class="file-name small text-muted mt-2 text-truncate">
                                {{ !empty($field['value']) ? basename($field['value']) : 'No file selected' }}
                            </div>

                            @error($field['name'])
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    @endforeach

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-2">
                    <a href="{{ route('vip') }}"
                        class="btn rounded-3 px-4"
                        style="border:1px solid #f59e0b; color:#f59e0b; background:#fff;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Update VIP</button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection

@section('css')
<style>
    .upload-box {
        transition: all 0.25s ease;
    }

    .upload-box:hover {
        border-color: #0d6efd !important;
        background: #f8fbff;
        transform: translateY(-2px);
    }

    .upload-icon {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        background: #e9f2ff;
        color: #0d6efd;
        font-size: 30px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    .preview-image {
        z-index: 2;
    }

    .upload-placeholder {
        z-index: 1;
    }

    .color-picker {
        width: 60px;
        height: 42px;
        padding: 2px;
        border-radius: 8px;
        border: 1px solid #ced4da;
        cursor: pointer;
    }

    /* Chrome / Edge */
    .color-picker::-webkit-color-swatch {
        border-radius: 6px;
        border: none;
    }

    .color-picker::-webkit-color-swatch-wrapper {
        padding: 0;
    }

    /* Firefox */
    .color-picker::-moz-color-swatch {
        border-radius: 6px;
        border: none;
    }
</style>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInputs = document.querySelectorAll('.image-input');

        imageInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                const card = this.closest('.border.rounded-4');

                const previewImage = card.querySelector('.preview-image');
                const placeholder = card.querySelector('.upload-placeholder');
                const fileName = card.querySelector('.file-name');

                if (!file) {
                    return;
                }

                fileName.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('vip_color');
        const colorText = document.getElementById('vip_color_text');

        colorText.value = colorInput.value;

        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('vip_color');
        const colorText = document.getElementById('vip_color_text');

        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
    });
</script>
@endsection