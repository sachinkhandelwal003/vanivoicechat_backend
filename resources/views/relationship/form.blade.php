@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <!-- 🔥 SAME HEADER (AS INDEX) -->
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($item) ? 'Edit' : 'Add' }} Relationship Item
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('relationship.item') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- 🔥 BODY -->
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
            action="{{ isset($item) ? route('relationship.item.update', $item->id) : route('relationship.item.add') }}"
            method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">

                <!-- NAME -->
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $item->name ?? '') }}">
                </div>

                <!-- TYPE -->
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control" id="type">
                        @foreach(['CP','brother','sister','confident'] as $type)
                        <option value="{{ $type }}"
                            {{ old('type', $item->type ?? '') == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- COINS -->
                <div class="col-md-12">
                    <label class="form-label">Required Coins</label>
                    <input type="number" name="required_coins" class="form-control"
                        value="{{ old('required_coins', $item->required_coins ?? '') }}">
                </div>

                @php
                $uploadFields = [
                ['label' => 'Icon', 'name' => 'icon'],
                ['label' => 'GIF', 'name' => 'gif'],
                ['label' => 'Ring', 'name' => 'ring'],
                ['label' => 'Avatar', 'name' => 'avatar'],
                ['label' => 'Frame', 'name' => 'frame'],
                ['label' => 'Badge', 'name' => 'badge'],
                ['label' => 'Background', 'name' => 'background'],
                ];
                @endphp

                @foreach($uploadFields as $field)
                <div class="col-md-4 {{ $field['name'] === 'ring' ? 'ring-field-wrapper' : '' }}">
                    <div class="border rounded-3 p-3">

                        <label class="form-label">{{ $field['label'] }}</label>

                        <input type="file" name="{{ $field['name'] }}"
                            class="d-none image-input" id="{{ $field['name'] }}">

                        <label for="{{ $field['name'] }}"
                            class="upload-box w-100 d-flex align-items-center justify-content-center text-center"
                            style="height:140px; cursor:pointer; border:2px dashed #ccc;">

                            <img
                                src="{{ isset($item) && $item->{$field['name']} 
                                    ? asset('storage/'.$item->{$field['name']}) 
                                    : '' }}"
                                class="preview-image {{ isset($item) && $item->{$field['name']} ? '' : 'd-none' }}"
                                style="width:100%; height:100%; object-fit:cover;">

                            <div class="upload-placeholder {{ isset($item) && $item->{$field['name']} ? 'd-none' : '' }}">
                                <small>Click to upload</small>
                            </div>

                        </label>

                    </div>
                </div>
                @endforeach

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($item) ? 'Update' : 'Save' }}
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


    const ringTypes = @json($ringTypes ?? []);
    const typeSelect = document.getElementById('type');
    const ringWrapper = document.querySelector('.ring-field-wrapper');

    const currentItemId = @json($item->id ?? null);
    const currentItemType = @json(strtolower($item->type ?? ''));
    const currentItemHasRing = @json(!empty($item->ring));

    function toggleRingField() {
        const selectedType = (typeSelect.value || '').toLowerCase();

        // Edit mode: agar current item ka same type hai aur current item me ring already hai
        if (currentItemId && selectedType === currentItemType && currentItemHasRing) {
            ringWrapper.style.display = 'block';
            return;
        }

        // Agar selected type me pehle se kisi item me ring hai to hide
        if (ringTypes.includes(selectedType)) {
            ringWrapper.style.display = 'none';
        } else {
            ringWrapper.style.display = 'block';
        }
    }

    typeSelect.addEventListener('change', toggleRingField);
    toggleRingField();
</script>
@endsection