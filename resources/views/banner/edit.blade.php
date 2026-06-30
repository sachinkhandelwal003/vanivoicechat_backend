@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Update Banner
        </div>

        <div class="card-body">

            <!-- @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif -->

            <form action="{{ route('banner.edit', $banner->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">Cover <span class="text-danger">*</span></label>

                    <div class="d-flex gap-4">

                        <div>
                            <input type="file" name="large_banner" class="d-none" id="largeBanner">
                            <label for="largeBanner"
                                class="border rounded d-flex align-items-center justify-content-center position-relative"
                                style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                                <img id="largePreview"
                                    src="{{ $banner->large_banner ? asset('storage/'.$banner->large_banner) : '' }}"
                                    class="position-absolute w-100 h-100 {{ $banner->large_banner ? '' : 'd-none' }}"
                                    style="object-fit:cover;">

                                <span id="largePlus" class="fs-1 {{ $banner->large_banner ? 'd-none' : '' }}">+</span>
                            </label>

                            @error('large_banner')
                            <small class="text-danger d-block text-center">{{ $message }}</small>
                            @enderror
                        </div>

                        <div>
                            <input type="file" name="small_banner" class="d-none" id="smallBanner">
                            <label for="smallBanner"
                                class="border rounded d-flex align-items-center justify-content-center position-relative"
                                style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                                <img id="smallPreview"
                                    src="{{ $banner->small_banner ? asset('storage/'.$banner->small_banner) : '' }}"
                                    class="position-absolute w-100 h-100 {{ $banner->small_banner ? '' : 'd-none' }}"
                                    style="object-fit:cover;">

                                <span id="smallPlus" class="fs-1 {{ $banner->small_banner ? 'd-none' : '' }}">+</span>
                            </label>

                            @error('small_banner')
                            <small class="text-danger d-block text-center">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Jump Type *</label>
                        <select name="jump_type" id="jumpType" class="form-control">
                            <option value="">Select</option>
                            <option value="h5" {{ old('jump_type',$banner->jump)=='h5'?'selected':'' }}>H5</option>
                            <option value="app" {{ old('jump_type',$banner->jump)=='app'?'selected':'' }}>App</option>
                        </select>
                        @error('jump_type') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="h5Box">
                        <label class="form-label fw-bold">H5 Address</label>
                        <input type="text" name="type_address_url"
                            value="{{ old('type_address_url',$banner->address) }}"
                            class="form-control">
                        @error('type_address_url') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="appBox">
                        <label class="form-label fw-bold">Type Address</label>
                        <select name="type_address_app" id="appType" class="form-control">
                            <option value="">Select</option>
                            <option value="personal"
                                {{ old('type_address_app',$banner->type_address_app)=='personal'?'selected':'' }}>
                                Personal Homepage
                            </option>
                            <option value="room"
                                {{ old('type_address_app',$banner->type_address_app)=='room'?'selected':'' }}>
                                Room
                            </option>
                        </select>
                        @error('type_address_app') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="uidBox">
                        <label class="form-label fw-bold">UID</label>
                        <input type="text" name="uid"
                            value="{{ old('uid',$banner->uid) }}"
                            class="form-control">
                        @error('uid') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 mb-3 d-none" id="roomBox">
                        <label class="form-label fw-bold">Room ID</label>
                        <input type="text" name="roomId"
                            value="{{ old('roomId',$banner->room_id) }}"
                            class="form-control">
                        @error('roomId') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Display Space</label>
                        <select name="display_space" class="form-control">
                            <option value="">Select</option>
                            <option value="front page" {{ old('display_space',$banner->display)=='front page'?'selected':'' }}>Front Page</option>
                            <option value="room" {{ old('display_space',$banner->display)=='room'?'selected':'' }}>Room</option>
                            <option value="recharge" {{ old('display_space',$banner->display)=='recharge'?'selected':'' }}>Recharge</option>
                            <option value="event gift" {{ old('display_space',$banner->display)=='event gift'?'selected':'' }}>Event Gift</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Start Time</label>
                        <input type="datetime-local" name="start_time"
                            value="{{ $banner->start_time ? $banner->start_time->format('Y-m-d\TH:i') : '' }}"
                            class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">End Time *</label>

                        <input type="datetime-local" name="end_time"
                            value="{{ $banner->end_time ? $banner->end_time->format('Y-m-d\TH:i') : '' }}"
                            class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Region</label>
                        <select name="region" class="form-control">
                            <option value="">Select</option>
                            @foreach($country as $c)
                            <option value="{{ $c->id }}"
                                {{ old('region',$banner->region)==$c->id?'selected':'' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" maxlength="200">{{ old('description',$banner->description) }}</textarea>
                    </div>
                </div>

                <div class="text-end">
                    <a href="{{ route('banner') }}" class="btn btn-secondary">Cancel</a>
                    <button class="btn btn-primary">Update</button>
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