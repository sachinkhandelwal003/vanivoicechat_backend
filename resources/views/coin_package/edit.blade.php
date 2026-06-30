@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Edit Coin Packages
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('coin.package.edit', $coin->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold"> Coin <span class="text-danger">*</span></label>
                    <input type="text" name="coin"
                        class="form-control @error('coin') is-invalid @enderror"
                        value="{{ old('coin', $coin->coins) }}">
                    @error('coin')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"> Price <span class="text-danger">*</span></label>
                    <input type="text" name="price"
                        class="form-control @error('price') is-invalid @enderror"
                        value="{{ old('price', $coin->price) }}">
                    @error('price')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                 <div class="mb-3">
                    <label class="form-label fw-bold">Bonus Percent <span class="text-danger">*</span></label>
                    <input type="text" name="bonus_percent"
                        class="form-control @error('bonus_percent') is-invalid @enderror"
                        value="{{ old('bonus_percent', $coin->bonus_percent) }}">
                    @error('bonus_percent')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Coin Icon <span class="text-danger">*</span>
                    </label>

                    <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                    <label for="icon"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="coverPreview"
                            src="{{ $coin->icon ? Helper::showImage($coin->icon, true) : '' }}"
                            class="position-absolute w-100 h-100 {{ $coin->icon ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="coverPlus" class="fs-1 {{ $coin->icon ? 'd-none' : '' }}">+</span>
                    </label>

                    @error('icon')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ $coin->status == '1' ? 'selected' : '' }}>Enable</option>
                        <option value="0" {{ $coin->status == '0' ? 'selected' : '' }}>Disable</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('coin.package') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const coverInput = document.getElementById('icon');
        const coverPreview = document.getElementById('coverPreview');
        const coverPlus = document.getElementById('coverPlus');

        coverInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                coverPreview.src = e.target.result;
                coverPreview.classList.remove('d-none');
                coverPlus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

    });
</script>
@endsection