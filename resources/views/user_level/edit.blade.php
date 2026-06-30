@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Edit User level
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('user.level.edit', $userLevel->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">Grade <span class="text-danger">*</span></label>
                    <input type="number" name="grade"
                        class="form-control @error('grade') is-invalid @enderror"
                        value="{{ old('grade', $userLevel->grade) }}">
                    @error('grade')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $userLevel->name) }}">
                    @error('name')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Level Experience Cap <span class="text-danger">*</span></label>
                    <input type="number" name="experience_cap"
                        class="form-control @error('experience_cap') is-invalid @enderror"
                        value="{{ old('experience_cap', $userLevel->experience_cap) }}">
                    @error('experience_cap')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nickname Color <span class="text-danger">*</span></label>

                    <div class="d-flex align-items-center gap-3">
                        <input type="color" id="colorPicker" class="form-control form-control-color"
                            value="{{ old('nickname_color', $userLevel->nickname_color ?? '#000000') }}"
                            style="width:60px;height:45px;">

                        <input type="text" name="nickname_color" id="colorValue"
                            class="form-control @error('nickname_color') is-invalid @enderror"
                            value="{{ old('nickname_color', $userLevel->nickname_color) }}"
                            placeholder="#ff0000 or rgb(255,0,0)">
                    </div>

                    @error('nickname_color')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Icon</label>

                    <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                    <label for="icon"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="previewIcon"
                            src="{{ $userLevel->icon ? asset('storage/'.$userLevel->icon) : '' }}"
                            class="position-absolute w-100 h-100 {{ $userLevel->icon ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="plusIcon" class="fs-1 {{ $userLevel->icon ? 'd-none' : '' }}">+</span>
                    </label>

                    @error('icon')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Avatar Corner</label>

                    <input type="file" name="avatar_corner" id="avatar_corner" class="d-none" accept="image/*">

                    <label for="avatar_corner"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="previewAvatar"
                            src="{{ $userLevel->avatar_corner ? asset('storage/'.$userLevel->avatar_corner) : '' }}"
                            class="position-absolute w-100 h-100 {{ $userLevel->avatar_corner ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="plusAvatar" class="fs-1 {{ $userLevel->avatar_corner ? 'd-none' : '' }}">+</span>
                    </label>

                    @error('avatar_corner')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- <div class="mb-4">
                    <label class="form-label fw-bold">Background</label>

                    <input type="file" name="background_image" id="background_image" class="d-none" accept="image/*">

                    <label for="background_image"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="previewBackground"
                            src="{{ $userLevel->background_image ? asset('storage/'.$userLevel->background_image) : '' }}"
                            class="position-absolute w-100 h-100 {{ $userLevel->background_image ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="plusBackground" class="fs-1 {{ $userLevel->background_image ? 'd-none' : '' }}">+</span>
                    </label>

                    @error('background_image')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div> -->

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('user.level') }}" class="btn btn-secondary">Cancel</a>
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

        function previewImage(inputId, previewId, plusId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const plus = document.getElementById(plusId);

            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    plus.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        }

        previewImage('icon', 'previewIcon', 'plusIcon');
        previewImage('avatar_corner', 'previewAvatar', 'plusAvatar');
        previewImage('background_image', 'previewBackground', 'plusBackground');


        // Color Picker 
        const colorPicker = document.getElementById('colorPicker');
        const colorValue = document.getElementById('colorValue');

        colorPicker.addEventListener('input', function() {
            colorValue.value = this.value;
            colorValue.style.color = this.value;
        });

        colorValue.addEventListener('input', function() {
            colorPicker.value = this.value;
        });

    });
</script>

@endsection