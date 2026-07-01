@extends('layouts.app')

@section('content')

<style>
    .section-title {
        font-weight: 600;
        margin-bottom: 12px;
        border-left: 4px solid #7367f0;
        padding-left: 10px;
        color: #333;
    }

    .preview-img {
        width: 90px;
        height: 90px;
        object-fit: contain;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 5px;
        background: #f8f9fa;
        margin-top: 8px;
    }

    .color-picker {
        width: 60px;
        height: 42px;
        padding: 2px;
        border-radius: 8px;
        border: 1px solid #ced4da;
        cursor: pointer;
    }

    .color-picker::-webkit-color-swatch {
        border-radius: 6px;
        border: none;
    }

    .color-picker::-webkit-color-swatch-wrapper {
        padding: 0;
    }

    .current-file {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
        display: block;
    }
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Add VIP</h5>
            <p class="text-muted mb-0">Create VIP package and upload required assets.</p>
        </div>

        <a href="{{ route('vip') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card-body">

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

        <form action="{{ route('vip.add') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- BASIC INFO --}}
            <div class="section-title">Basic Info</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        placeholder="Enter VIP name">
                    @error('name')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Days <span class="text-danger">*</span></label>
                    <input type="number"
                        name="days"
                        class="form-control @error('days') is-invalid @enderror"
                        value="{{ old('days') }}"
                        placeholder="Enter days">
                    @error('days')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Need Coins <span class="text-danger">*</span></label>
                    <input type="number"
                        name="needcoins"
                        class="form-control @error('needcoins') is-invalid @enderror"
                        value="{{ old('needcoins') }}"
                        placeholder="Enter coins">
                    @error('needcoins')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            {{-- COLOR --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Background Color</label>

                    @php
                    $selectedColor = old('color', '#6b2dd7');
                    @endphp

                    <div class="d-flex align-items-center gap-2">
                        <input type="color"
                            name="color"
                            id="vip_color"
                            class="color-picker @error('color') is-invalid @enderror"
                            value="{{ $selectedColor }}">

                        <input type="text"
                            id="vip_color_text"
                            class="form-control"
                            value="{{ $selectedColor }}"
                            readonly>
                    </div>

                    @error('color')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="col-md-4">
                    <label class="form-label">Username Color</label>

                    @php
                    $selectedColor = old('username', '#6b2dd7');
                    @endphp

                    <div class="d-flex align-items-center gap-2">
                        <input type="color"
                            name="username"
                            id="username"
                            class="color-picker @error('username') is-invalid @enderror"
                            value="{{ $selectedColor }}">

                        <input type="text"
                            id="username_text"
                            class="form-control"
                            value="{{ $selectedColor }}"
                            readonly>
                    </div>

                    @error('username')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>


            {{-- BADGE / CHAT CARD / USERNAME --}}
            <div class="section-title">Badge / Chat Card / Username</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Badge</label>
                    <input type="file"
                        name="badge"
                        class="form-control image-input @error('badge') is-invalid @enderror"
                        accept="image/*">
                    @error('badge')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <img src="" class="preview-img d-none">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Chat Card</label>
                    <input type="file"
                        name="chat_card"
                        class="form-control image-input @error('chat_card') is-invalid @enderror"
                        accept="image/*">
                    @error('chat_card')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <img src="" class="preview-img d-none">
                </div>

            </div>

            {{-- ENTRY TAG --}}
            <div class="section-title">Entry Tag</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Entry Tag</label>
                    <input type="file"
                        name="entry_tag"
                        class="form-control image-input @error('entry_tag') is-invalid @enderror"
                        accept="image/*">
                    @error('entry_tag')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <img src="" class="preview-img d-none">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Entry Tag Animation</label>
                    <input type="file"
                        name="entry_tag_animation"
                        class="form-control @error('entry_tag_animation') is-invalid @enderror"
                        accept="image/*,.svga">
                    @error('entry_tag_animation')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <small class="current-file">SVGA allowed</small>
                </div>
            </div>

            {{-- ENTRY KEYS --}}
            <div class="section-title">Entry Keys</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Image Key</label>
                    <input type="text"
                        name="img_key"
                        class="form-control @error('img_key') is-invalid @enderror"
                        value="{{ old('img_key') }}"
                        placeholder="Example: user_image">
                    @error('img_key')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Text Key</label>
                    <input type="text"
                        name="text_key"
                        class="form-control @error('text_key') is-invalid @enderror"
                        value="{{ old('text_key') }}"
                        placeholder="Example: user_name">
                    @error('text_key')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Frame Key</label>
                    <input type="text"
                        name="frame_key"
                        class="form-control @error('frame_key') is-invalid @enderror"
                        value="{{ old('frame_key') }}"
                        placeholder="Example: frame">
                    @error('frame_key')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            {{-- IMAGE FRAME --}}
            <div class="section-title">Image Frame</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Image Frame</label>
                    <input type="file"
                        name="image_frame"
                        class="form-control image-input @error('image_frame') is-invalid @enderror"
                        accept="image/*">
                    @error('image_frame')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <img src="" class="preview-img d-none">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Image Frame Animation</label>
                    <input type="file"
                        name="image_frame_animation"
                        class="form-control @error('image_frame_animation') is-invalid @enderror"
                        accept="image/*,.svga">
                    @error('image_frame_animation')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <small class="current-file"> SVGA allowed.</small>
                </div>
            </div>

            {{-- PROFILE FRAME --}}
            <div class="section-title">Profile Frame</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Profile Frame</label>
                    <input type="file"
                        name="profile_frame"
                        class="form-control image-input @error('profile_frame') is-invalid @enderror"
                        accept="image/*">
                    @error('profile_frame')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <img src="" class="preview-img d-none">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Profile Frame Animation</label>
                    <input type="file"
                        name="profile_frame_animation"
                        class="form-control @error('profile_frame_animation') is-invalid @enderror"
                        accept="image/*,.svga">
                    @error('profile_frame_animation')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <small class="current-file">SVGA allowed</small>
                </div>
            </div>

            {{-- VOICE --}}
            <div class="section-title">Entrance Assets</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Entrance Image</label>
                    <input type="file"
                        name="voice_frame"
                        class="form-control image-input @error('voice_frame') is-invalid @enderror"
                        accept="image/*">
                    @error('voice_frame')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <img src="" class="preview-img d-none">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Entrance Animation</label>
                    <input type="file"
                        name="voice_animation"
                        class="form-control @error('voice_animation') is-invalid @enderror"
                        accept="image/*,.svga">
                    @error('voice_animation')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <small class="current-file"> SVGA allowed</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('vip') }}" class="btn btn-outline-warning px-4">
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary px-4">
                    Save VIP
                </button>
            </div>

        </form>
    </div>
</div>

@endsection

@section('js')
<script>
    document.querySelectorAll('.image-input').forEach(function(input) {
        input.addEventListener('change', function() {
            let file = this.files[0];
            let preview = this.parentElement.querySelector('.preview-img');

            if (file && preview) {
                let reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                };

                reader.readAsDataURL(file);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('vip_color');
        const colorText = document.getElementById('vip_color_text');

        if (colorInput && colorText) {
            colorText.value = colorInput.value || '#6b2dd7';

            colorInput.addEventListener('input', function() {
                colorText.value = this.value;
            });
        }
    });
</script>
@endsection