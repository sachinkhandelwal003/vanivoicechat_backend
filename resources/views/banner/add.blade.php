@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Banner
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('banner.add') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Cover <span class="text-danger">*</span>
                    </label>

                    <div class="d-flex gap-4"> 
                        <div>
                            <input type="file" name="large_banner" class="d-none" id="largeBanner" accept="image/*">
                            <label for="largeBanner"
                                class="border rounded d-flex align-items-center justify-content-center position-relative"
                                style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                                <img id="largePreview" class="position-absolute w-100 h-100 d-none"
                                    style="object-fit:cover;">
                                <span id="largePlus" class="fs-1">+</span>
                            </label>

                            <small class="d-block text-center mt-1">Large banner</small>

                            @error('large_banner')
                            <small class="text-danger d-block text-center">{{ $message }}</small>
                            @enderror
                        </div>
                        <div>
                            <input type="file" name="small_banner" class="d-none" id="smallBanner" accept="image/*">

                            <label for="smallBanner"
                                class="border rounded d-flex align-items-center justify-content-center position-relative"
                                style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                                <img id="smallPreview" class="position-absolute w-100 h-100 d-none"
                                    style="object-fit:cover;">
                                <span id="smallPlus" class="fs-1">+</span>
                            </label>

                            <small class="d-block text-center mt-1">Small banner</small>

                            @error('small_banner')
                            <small class="text-danger d-block text-center">{{ $message }}</small>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            Jump Type <span class="text-danger">*</span>
                        </label>

                        <select name="jump_type" id="jumpType"
                            class="form-control @error('jump_type') is-invalid @enderror">
                            <option value="">Jump type</option>
                            <option value="h5" {{ old('jump_type')=='h5'?'selected':'' }}>H5</option>
                            <option value="app" {{ old('jump_type')=='app'?'selected':'' }}>App</option>
                        </select>

                        @error('jump_type')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="h5Box">
                        <label class="form-label fw-bold">Type Address (H5)</label>

                        <input type="text" name="type_address_url"
                            value="{{ old('type_address_url') }}"
                            class="form-control @error('type_address_url') is-invalid @enderror"
                            placeholder="Enter content address">

                        @error('type_address_url')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="appBox">
                        <label class="form-label fw-bold">Type Address</label>

                        <select name="type_address_app" id="appType"
                            class="form-control @error('type_address_app') is-invalid @enderror">
                            <option value="">Select option</option>
                            <option value="personal" {{ old('type_address_app')=='personal'?'selected':'' }}>
                                Enter Personal Homepage
                            </option>
                            <option value="room" {{ old('type_address_app')=='room'?'selected':'' }}>
                                Enter The Room
                            </option>
                        </select>

                        @error('type_address_app')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="uidBox">
                        <label class="form-label fw-bold">UID</label>

                        <input type="text" name="uid"
                            value="{{ old('uid') }}"
                            class="form-control @error('uid') is-invalid @enderror"
                            placeholder="Enter UID">

                        @error('uid')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="roomBox">
                        <label class="form-label fw-bold">Room ID</label>

                        <input type="text" name="roomId"
                            value="{{ old('roomId') }}"
                            class="form-control @error('roomId') is-invalid @enderror"
                            placeholder="Enter Room ID">

                        @error('roomId')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Display Space</label>

                        <select name="display_space"
                            class="form-control @error('display_space') is-invalid @enderror">
                            <option value="">Select type</option>
                            <option value="front page" {{ old('display_space')=='front page'?'selected':'' }}>Front Page</option>
                            <option value="room" {{ old('display_space')=='room'?'selected':'' }}>Room</option>
                            <option value="recharge" {{ old('display_space')=='recharge'?'selected':'' }}>Recharge</option>
                            <option value="event gift" {{ old('display_space')=='event gift'?'selected':'' }}>Event Gift</option>
                        </select>

                        @error('display_space')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Start Time</label>

                        <input type="datetime-local" name="start_time"
                            value="{{ old('start_time') }}"
                            class="form-control @error('start_time') is-invalid @enderror">

                        @error('start_time')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            End Time <span class="text-danger">*</span>
                        </label>

                        <input type="datetime-local" name="end_time"
                            value="{{ old('end_time') }}"
                            class="form-control @error('end_time') is-invalid @enderror">

                        @error('end_time')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Area</label>

                        <select name="region"
                            class="form-control @error('region') is-invalid @enderror">
                            <option value="">Please select a region</option>
                            @foreach ($country as $data)
                            <option value="{{ $data->id }}"
                                {{ old('region')==$data->id?'selected':'' }}>
                                {{ $data->name }}
                            </option>
                            @endforeach
                        </select>

                        @error('region')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Description</label>

                        <textarea name="description"
                            class="form-control @error('description') is-invalid @enderror"
                            maxlength="200"
                            placeholder="Describe (max 200 characters)">{{ old('description') }}</textarea>

                        @error('description')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('banner') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>

    function previewImage(input, preview, plus) {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                plus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    previewImage(
        document.getElementById('largeBanner'),
        document.getElementById('largePreview'),
        document.getElementById('largePlus')
    );

    previewImage(
        document.getElementById('smallBanner'),
        document.getElementById('smallPreview'),
        document.getElementById('smallPlus')
    );

    document.addEventListener('DOMContentLoaded', function() {
        const jumpType = document.getElementById('jumpType');
        const appType = document.getElementById('appType');

        function resetBoxes() {
            ['h5Box', 'appBox', 'uidBox', 'roomBox']
            .forEach(id => document.getElementById(id).classList.add('d-none'));
        }

        function handleJump() {
            resetBoxes();
            if (jumpType.value === 'h5') {
                h5Box.classList.remove('d-none');
            }
            if (jumpType.value === 'app') {
                appBox.classList.remove('d-none');
                handleApp();
            }
        }

        function handleApp() {
            uidBox.classList.add('d-none');
            roomBox.classList.add('d-none');

            if (appType.value === 'personal') {
                uidBox.classList.remove('d-none');
            }
            if (appType.value === 'room') {
                roomBox.classList.remove('d-none');
            }
        }

        jumpType.addEventListener('change', handleJump);
        appType.addEventListener('change', handleApp);

        handleJump();
    });
</script>
@endsection