@extends('layouts.app')

@section('content')
<style>
    .privilege-card {
        display: block;
        border: 1px solid #ddd;
        border-radius: 12px;
        cursor: pointer;
        transition: 0.3s;
        background: #fff;
        position: relative;
    }

    .privilege-card input {
        position: absolute;
        opacity: 0;
    }

    .privilege-card .card-body {
        padding: 20px 10px;
    }

    .privilege-card .icon {
        font-size: 20px;
        color: #999;
    }

    .privilege-card .title {
        font-size: 14px;
        font-weight: 500;
    }

    .privilege-card:hover {
        border-color: #7367f0;
        transform: translateY(-3px);
    }

    .privilege-card input:checked+.card-body {
        background: linear-gradient(135deg, #7367f0, #9c8cff);
        color: #fff;
        border-radius: 12px;
    }

    .privilege-card input:checked+.card-body .icon {
        color: #fff;
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

<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($svip) ? 'Edit' : 'Add' }} SVIP
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('svip') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form
            action="{{ isset($svip) ? route('svip.update', $svip->id) : route('svip.add') }}"
            method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="row g-4">

                <!-- NAME -->
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $svip->name ?? '') }}">
                </div>

                <!-- COINS -->
                <div class="col-md-3">
                    <label class="form-label">Coins</label>
                    <input type="number" name="need_coins" class="form-control"
                        value="{{ old('need_coins', $svip->need_coins ?? '') }}">
                </div>

                <!-- DAYS -->
                <div class="col-md-3">
                    <label class="form-label">Days</label>
                    <input type="number" name="days" class="form-control"
                        value="{{ old('days', $svip->days ?? '') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Background Color</label>
                    @php
                    $selectedColor = old('color', $svip->color ?? '#6b2dd7');
                    @endphp
                    <div class="d-flex align-items-center gap-2">
                        <input type="color"
                            name="color"
                            id="svip_color"
                            class="color-picker" value="{{ $selectedColor }}">

                        <input type="text"
                            id="svip_color_text"
                            class="form-control"
                            value="{{ $selectedColor }}"
                            readonly>
                    </div>
                </div>

                @php
                $uploadFields = [
                ['label' => 'Medal', 'name' => 'medal'],
                ['label' => 'Medal GIF', 'name' => 'medal_gif'],
                ['label' => 'Title', 'name' => 'title'],
                ['label' => 'Bubble', 'name' => 'bubble'],
                ['label' => 'Headwear', 'name' => 'headwear'],
                ['label' => 'Entry', 'name' => 'entry'],
                ];
                @endphp

                @foreach($uploadFields as $field)
                <div class="col-md-4">
                    <div class="border rounded-3 p-3">

                        <label class="form-label">{{ $field['label'] }}</label>

                        <input type="file" name="{{ $field['name'] }}"
                            class="d-none image-input" id="{{ $field['name'] }}">

                        <label for="{{ $field['name'] }}"
                            class="upload-box w-100 d-flex align-items-center justify-content-center text-center"
                            style="height:140px; cursor:pointer; border:2px dashed #ccc;">

                            <img
                                src="{{ isset($svip) && $svip->{$field['name']} 
                                    ? asset('storage/'.$svip->{$field['name']}) 
                                    : '' }}"
                                class="preview-image {{ isset($svip) && $svip->{$field['name']} ? '' : 'd-none' }}"
                                style="width:100%; height:100%; object-fit:cover;">

                            <div class="upload-placeholder {{ isset($svip) && $svip->{$field['name']} ? 'd-none' : '' }}">
                                <small>Click to upload</small>
                            </div>

                        </label>

                    </div>
                </div>
                @endforeach

                <!-- PRIVILEGES -->
                <div class="col-md-12">
                    <label class="form-label fw-bold mb-2">Privileges</label>

                    <div class="row">
                        @forelse($privileges as $p)
                        <div class="col-md-3 mb-3">

                            <label class="privilege-card w-100">
                                <input type="checkbox" name="privileges[]" value="{{ $p->id }}"
                                    {{ in_array($p->id, $selectedPrivileges) ? 'checked' : '' }}>

                                <div class="card-body text-center">
                                    <div class="icon mb-2">

                                        @if($p->icon)
                                        <img src="{{ asset('storage/'.$p->icon) }}" width="40" height="40" style="object-fit:contain;">
                                        @else
                                        <i class="fa fa-star"></i>
                                        @endif

                                    </div>
                                    <div class="title">{{ $p->name }}</div>
                                </div>
                            </label>

                        </div>
                        @empty
                        <div class="col-md-12">
                            <p class="text-danger">No privileges found</p>
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($svip) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@section('js')
<script>
    document.querySelectorAll('.image-input').forEach(input => {
        input.addEventListener('change', function() {
            let file = this.files[0];
            let container = this.closest('.border');
            let preview = container.querySelector('.preview-image');
            let placeholder = container.querySelector('.upload-placeholder');

            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('svip_color');
        const colorText = document.getElementById('svip_color_text');

        colorText.value = colorInput.value;

        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('svip_color');
        const colorText = document.getElementById('svip_color_text');

        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
    });
</script>
@endsection