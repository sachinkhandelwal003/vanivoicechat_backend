@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($level) ? 'Edit' : 'Add' }} Level
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('levels') }}" class="btn btn-outline-secondary">
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
            action="{{ isset($level) ? route('levels.add', $level->id) : route('levels.add') }}"
            method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="row g-4">

                <!-- TYPE -->
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        <option value="wealth" {{ old('type', $level->type ?? '') == 'wealth' ? 'selected' : '' }}>Wealth</option>
                        <option value="charm" {{ old('type', $level->type ?? '') == 'charm' ? 'selected' : '' }}>Charm</option>
                    </select>
                </div>

                <!-- LEVEL -->
                <div class="col-md-4">
                    <label class="form-label">Level</label>
                    <input type="text" name="level" class="form-control"
                        value="{{ old('level', $level->level ?? '') }}">
                </div>

                <!-- REQUIRED EXP -->
                <div class="col-md-4">
                    <label class="form-label">Required Exp</label>
                    <input type="number" name="required_exp" class="form-control"
                        value="{{ old('required_exp', $level->required_exp ?? '') }}">
                </div>

                <!-- ICON -->
                <div class="col-md-6">
                    <div class="border rounded-3 p-3">
                        <label class="form-label">Icon</label>

                        <input type="file" name="icon" class="d-none image-input" id="icon">

                        <label for="icon" class="upload-box w-100 text-center"
                            style="height:140px; cursor:pointer; border:2px dashed #ccc; display:flex; align-items:center; justify-content:center;">

                            <img src="{{ isset($level) && $level->icon ? asset('storage/'.$level->icon) : '' }}"
                                class="preview-image {{ isset($level) && $level->icon ? '' : 'd-none' }}"
                                style="width:100%; height:100%; object-fit:cover;">

                            <div class="upload-placeholder {{ isset($level) && $level->icon ? 'd-none' : '' }}">
                                <small>Click to upload</small>
                            </div>

                        </label>
                    </div>
                </div>

                <!-- ENTRY EFFECT -->
                <div class="col-md-6">
                    <div class="border rounded-3 p-3">
                        <label class="form-label">Entry Effect</label>

                        <input type="file" name="entry_effect" class="d-none image-input" id="entry_effect">

                        <label for="entry_effect" class="upload-box w-100 text-center"
                            style="height:140px; cursor:pointer; border:2px dashed #ccc; display:flex; align-items:center; justify-content:center;">

                            <img src="{{ isset($level) && $level->entry_effect ? asset('storage/'.$level->entry_effect) : '' }}"
                                class="preview-image {{ isset($level) && $level->entry_effect ? '' : 'd-none' }}"
                                style="width:100%; height:100%; object-fit:cover;">

                            <div class="upload-placeholder {{ isset($level) && $level->entry_effect ? 'd-none' : '' }}">
                                <small>Click to upload</small>
                            </div>

                        </label>
                    </div>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($level) ? 'Update' : 'Save' }}
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
@endsection