@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Edit VIP</h5>
                <p class="text-muted mb-0">Update VIP package and assets.</p>
            </div>

            <a href="{{ route('vip') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card-body">

            <form action="{{ route('vip.edit', $vip->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- BASIC INFO --}}
                <div class="section-title">Basic Info</div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $vip->name) }}">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label>Days</label>
                        <input type="number" name="days" class="form-control @error('days') is-invalid @enderror"
                            value="{{ old('days', $vip->days) }}">
                        @error('days')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label>Coins</label>
                        <input type="number" name="needcoins" class="form-control @error('needcoins') is-invalid @enderror"
                            value="{{ old('needcoins', $vip->needcoins) }}">
                        @error('needcoins')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- COLOR --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label>Background Color</label>
                        @php
                            $color = old('color', $vip->color ?? '#6b2dd7');
                        @endphp

                        <div class="d-flex gap-2">
                            <input type="color" name="color" id="vip_color" class="color-picker"
                                value="{{ $color }}">
                            <input type="text" id="vip_color_text" class="form-control" value="{{ $color }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label>Username Color</label>
                        @php
                            $username = old('username', $vip->username ?? '#6b2dd7');
                        @endphp

                        <div class="d-flex gap-2">
                            <input type="color" name="username" id="username" class="color-picker"
                                value="{{ $username }}">
                            <input type="text" id="username_text" class="form-control" value="{{ $username }}"
                                readonly>
                        </div>
                    </div>
                </div>

                {{-- IMAGE FIELD FUNCTION --}}
                @php
                    function showImg($path)
                    {
                        return $path ? asset('storage/' . $path) : '';
                    }
                @endphp

                {{-- BADGE --}}
                <div class="section-title">Title Tag / Badge / Chat Card</div>

                <div class="row mb-3">

                    <div class="col-md-4">
                        <label>Title</label>
                        <input type="file" name="title_tag" class="form-control image-input">
                        @if ($vip->title_tag)
                            <img src="{{ showImg($vip->title_tag) }}" class="preview-img" width="400">
                        @endif
                    </div>


                    <div class="col-md-4">
                        <label>Badge</label>
                        <input type="file" name="badge" class="form-control image-input">
                        @if ($vip->badge)
                            <img src="{{ showImg($vip->badge) }}" class="preview-img">
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label>Chat Card</label>
                        <input type="file" name="chat_card" class="form-control image-input">
                        @if ($vip->chat_card)
                            <img src="{{ showImg($vip->chat_card) }}" class="preview-img">
                        @endif
                    </div>

                </div>

                {{-- ENTRY TAG --}}
                <div class="section-title">Entry Tag</div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <label>Entry Tag</label>
                        <input type="file" name="entry_tag" class="form-control image-input">
                        @if ($vip->entry_tag)
                            <img src="{{ showImg($vip->entry_tag) }}" class="preview-img">
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label>Entry Animation</label>
                        <input type="file" name="entry_tag_animation" class="form-control">
                        @if ($vip->entry_tag_animation)
                            <small class="text-muted">Current: {{ basename($vip->entry_tag_animation) }}</small>
                        @endif
                    </div>

                </div>

                {{-- KEYS --}}
                <div class="section-title">Entry Keys</div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" name="img_key" class="form-control"
                            value="{{ old('img_key', $vip->img_key) }}" placeholder="Image Key">
                    </div>

                    <div class="col-md-4">
                        <input type="text" name="text_key" class="form-control"
                            value="{{ old('text_key', $vip->text_key) }}" placeholder="Text Key">
                    </div>

                    <div class="col-md-4">
                        <input type="text" name="frame_key" class="form-control"
                            value="{{ old('frame_key', $vip->frame_key) }}" placeholder="Frame Key">
                    </div>
                </div>

                {{-- IMAGE FRAME --}}
                <div class="section-title">Image Frame</div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <input type="file" name="image_frame" class="form-control image-input">
                        @if ($vip->image_frame)
                            <img src="{{ showImg($vip->image_frame) }}" class="preview-img">
                        @endif
                    </div>

                    <div class="col-md-6">
                        <input type="file" name="image_frame_animation" class="form-control">
                        @if ($vip->image_frame_animation)
                            <small>Current: {{ basename($vip->image_frame_animation) }}</small>
                        @endif
                    </div>

                </div>

                {{-- PROFILE --}}
                <div class="section-title">Profile Frame</div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <input type="file" name="profile_frame" class="form-control image-input">
                        @if ($vip->profile_frame)
                            <img src="{{ showImg($vip->profile_frame) }}" class="preview-img">
                        @endif
                    </div>

                    <div class="col-md-6">
                        <input type="file" name="profile_frame_animation" class="form-control">
                        @if ($vip->profile_frame_animation)
                            <small>Current: {{ basename($vip->profile_frame_animation) }}</small>
                        @endif
                    </div>

                </div>

                {{-- VOICE --}}
                <div class="section-title">Entrance</div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <input type="file" name="voice_frame" class="form-control image-input">
                        @if ($vip->voice_frame)
                            <img src="{{ showImg($vip->voice_frame) }}" class="preview-img">
                        @endif
                    </div>

                    <div class="col-md-6">
                        <input type="file" name="voice_animation" class="form-control">
                        @if ($vip->voice_animation)
                            <small>Current: {{ basename($vip->voice_animation) }}</small>
                        @endif
                    </div>

                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Update VIP</button>
                </div>

            </form>
        </div>
    </div>
@endsection
