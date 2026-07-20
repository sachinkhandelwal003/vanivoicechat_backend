@extends('layouts.app')

@section('content')

<style>
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

    .svga-preview {
        width: 120px;
        height: 120px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #f8f9fa;
        margin-top: 8px;
    }

    .section-title {
        font-weight: 600;
        margin-bottom: 10px;
        border-left: 4px solid #7367f0;
        padding-left: 10px;
    }
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
        <h5>{{ isset($svip) ? 'Edit' : 'Add' }} SVIP</h5>
        <a href="{{ route('svip') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card-body">

        <form action="{{ isset($svip) ? route('svip.update',$svip->id) : route('svip.add') }}"
            method="POST" enctype="multipart/form-data">
            @csrf

            {{-- BASIC --}}
            <div class="section-title">Basic Info</div>
            <div class="row mb-3">

                <div class="col-md-4">
                    <label>Name</label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name',$svip->name ?? '') }}">
                    @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-md-4">
                    <label>Coins</label>
                    <input type="number" name="need_coins"
                        class="form-control @error('need_coins') is-invalid @enderror"
                        value="{{ old('need_coins',$svip->need_coins ?? 0) }}">
                    @error('need_coins') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-md-4 mt-2">
                    <label>Days</label>
                    <input type="number" name="days"
                        class="form-control @error('days') is-invalid @enderror"
                        value="{{ old('days',$svip->days ?? '') }}">
                    @error('days') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                  <div class="col-md-4">
                    <label>Admin Limit</label>
                    <input type="number" name="admin_limit"
                        class="form-control @error('admin_limit') is-invalid @enderror"
                        value="{{ old('admin_limit',$svip->admin_limit ?? '') }}">
                    @error('admin_limit') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

            </div>

            {{-- COLOR --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Background Color</label>

                    @php
                    $selectedColor = old('color', $svip->color ?? '#6b2dd7');
                    @endphp

                    <div class="d-flex align-items-center gap-2">
                        <input type="color"
                            name="color"
                            id="svip_color"
                            class="form-control form-control-color @error('color') is-invalid @enderror"
                            value="{{ $selectedColor }}"
                            style="width:60px;">

                        <input type="text"
                            id="svip_color_text"
                            class="form-control"
                            value="{{ $selectedColor }}"
                            readonly>
                    </div>

                    @error('color') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            {{-- MEDAL --}}
            <div class="section-title">Medal / Title / Bubble</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Medal</label>
                    <input type="file" name="medal"
                        class="form-control image-input @error('medal') is-invalid @enderror">
                    @error('medal') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->medal ? asset('storage/'.$svip->medal):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->medal?'':'d-none' }}">
                </div>

                <div class="col-md-6">
                    <label>Medal Animation</label>
                    <input type="file" name="medal_gif"
                        class="form-control image-input @error('medal_gif') is-invalid @enderror">
                    @error('medal_gif') <small class="text-danger">{{ $message }}</small> @enderror

                    @if(isset($svip) && $svip->medal_gif)
                    <small class="text-muted d-block mt-1">
                        Current file: {{ basename($svip->medal_gif) }}
                    </small>
                    @endif

                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Title</label>
                    <input type="file" name="title"
                        class="form-control image-input @error('title') is-invalid @enderror">
                    @error('title') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->title ? asset('storage/'.$svip->title):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->title?'':'d-none' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Bubble</label>
                    <input type="file" name="bubble"
                        class="form-control image-input @error('bubble') is-invalid @enderror">
                    @error('bubble') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->bubble ? asset('storage/'.$svip->bubble):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->bubble?'':'d-none' }}">
                </div>
            </div>

            {{-- HEADWEAR --}}
            <div class="section-title">Headwear</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Headwear</label>
                    <input type="file" name="headwear"
                        class="form-control image-input @error('headwear') is-invalid @enderror">
                    @error('headwear') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->headwear ? asset('storage/'.$svip->headwear):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->headwear?'':'d-none' }}">
                </div>

                <div class="col-md-6">
                    <label>Headwear Animation</label>
                    <input type="file" name="headwear_animation"
                        class="form-control image-input @error('headwear_animation') is-invalid @enderror">
                    @error('headwear_animation') <small class="text-danger">{{ $message }}</small> @enderror

                    @if(isset($svip) && $svip->headwear_animation)
                    <small class="text-muted d-block mt-1">
                        Current file: {{ basename($svip->headwear_animation) }}
                    </small>
                    @endif
                </div>
            </div>

            {{-- ENTRY --}}
            <div class="section-title">Entry Assets</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Entry</label>
                    <input type="file" name="entry"
                        class="form-control image-input @error('entry') is-invalid @enderror">
                    @error('entry') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->entry ? asset('storage/'.$svip->entry):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->entry?'':'d-none' }}">
                </div>

                <div class="col-md-6">
                    <label>Entry Animation</label>
                    <input type="file" name="entry_animation"
                        class="form-control image-input @error('entry_animation') is-invalid @enderror">
                    @error('entry_animation') <small class="text-danger">{{ $message }}</small> @enderror
                    @if(isset($svip) && $svip->entry_animation)
                    <small class="text-muted d-block mt-1">
                        Current file: {{ basename($svip->entry_animation) }}
                    </small>
                    @endif
                </div>
            </div>

            {{-- KEYS --}}
            <div class="section-title">Entry Keys</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Image Key</label>
                    <input type="text" name="img_key"
                        class="form-control @error('img_key') is-invalid @enderror"
                        value="{{ old('img_key',$svip->img_key ?? '') }}">
                    @error('img_key') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-md-4">
                    <label>Text Key</label>
                    <input type="text" name="text_key"
                        class="form-control @error('text_key') is-invalid @enderror"
                        value="{{ old('text_key',$svip->text_key ?? '') }}">
                    @error('text_key') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-md-4">
                    <label>Frame Key</label>
                    <input type="text" name="frame_key"
                        class="form-control @error('frame_key') is-invalid @enderror"
                        value="{{ old('frame_key',$svip->frame_key ?? '') }}">
                    @error('frame_key') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="section-title">Entrance Assets</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Vehicle Image</label>
                    <input type="file" name="entrance_image"
                        class="form-control image-input @error('entrance_image') is-invalid @enderror">
                    @error('entrance_image') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->entrance_image ? asset('storage/'.$svip->entrance_image):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->entrance_image?'':'d-none' }}">
                </div>

                <div class="col-md-6">
                    <label>Vehicle Animation</label>
                    <input type="file" name="entrance_animation"
                        class="form-control image-input @error('entrance_animation') is-invalid @enderror">
                    @error('entrance_animation') <small class="text-danger">{{ $message }}</small> @enderror
                    @if(isset($svip) && $svip->entrance_animation)
                    <small class="text-muted d-block mt-1">
                        Current file: {{ basename($svip->entrance_animation) }}
                    </small>
                    @endif
                </div>
            </div>

            <div class="section-title">Voice Assets</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Voice Image</label>
                    <input type="file" name="voice_image"
                        class="form-control image-input @error('voice_image') is-invalid @enderror">
                    @error('voice_image') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->voice_image ? asset('storage/'.$svip->voice_image):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->voice_image?'':'d-none' }}">
                </div>

                <div class="col-md-6">
                    <label>Voice Animation</label>
                    <input type="file" name="voice_animation"
                        class="form-control image-input @error('voice_animation') is-invalid @enderror">
                    @error('voice_animation') <small class="text-danger">{{ $message }}</small> @enderror
                    @if(isset($svip) && $svip->voice_animation)
                    <small class="text-muted d-block mt-1">
                        Current file: {{ basename($svip->voice_animation) }}
                    </small>
                    @endif
                </div>
            </div>

            <div class="section-title">Profile Card Assets</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Profile Card Image</label>
                    <input type="file" name="profile_card"
                        class="form-control image-input @error('profile_card') is-invalid @enderror">
                    @error('profile_card') <small class="text-danger">{{ $message }}</small> @enderror

                    <img src="{{ isset($svip)&&$svip->profile_card ? asset('storage/'.$svip->profile_card):'' }}"
                        class="preview-img {{ isset($svip)&&$svip->profile_card?'':'d-none' }}">
                </div>

                <div class="col-md-6">
                    <label>Profile Card Animation</label>
                    <input type="file" name="profile_animation"
                        class="form-control image-input @error('profile_animation') is-invalid @enderror">
                    @error('profile_animation') <small class="text-danger">{{ $message }}</small> @enderror
                    @if(isset($svip) && $svip->profile_animation)
                    <small class="text-muted d-block mt-1">
                        Current file: {{ basename($svip->profile_animation) }}
                    </small>
                    @endif
                </div>
            </div>

            {{-- PRIVILEGES --}}
            <div class="section-title">Privileges</div>

            <div class="row mb-3">
                @foreach($privileges as $p)
                <div class="col-md-3 mb-2">

                    <label class="d-flex align-items-center gap-2">
                        <input type="checkbox"
                            name="privileges[]"
                            value="{{ $p->id }}"
                            {{ in_array($p->id,$selectedPrivileges ?? []) ? 'checked' : '' }}>

                        {{ $p->name }}
                    </label>

                </div>
                @endforeach
            </div>

            {{-- validation error --}}
            @error('privileges')
            <small class="text-danger">{{ $message }}</small>
            @enderror

            <div class="text-end">
                <button class="btn btn-primary">
                    {{ isset($svip) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>
    </div>
</div>

@endsection

@section('js')

<script src="https://cdn.jsdelivr.net/npm/svgaplayerweb@2.3.1/build/svga.min.js"></script>

@section('js')
<script>
    document.querySelectorAll('.image-input').forEach(input => {
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
        const colorInput = document.getElementById('svip_color');
        const colorText = document.getElementById('svip_color_text');

        if (colorInput && colorText) {
            colorText.value = colorInput.value;

            colorInput.addEventListener('input', function() {
                colorText.value = this.value;
            });
        }
    });
</script>
@endsection

@endsection
