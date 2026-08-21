@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">

            <div class="card-header fw-bold">
                Add Game
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('game.add') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">

                        <!-- Game Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Game Name <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Enter game name">

                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Slug <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="slug" id="slug"
                                class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}"
                                placeholder="ludo">

                            @error('slug')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- SUD Game ID -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                SUD Game ID
                            </label>

                            <input type="text" name="sud_game_id"
                                class="form-control @error('sud_game_id') is-invalid @enderror"
                                value="{{ old('sud_game_id') }}" placeholder="Enter SUD Game ID">

                            @error('sud_game_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Game Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Game Type
                            </label>

                            <input type="text" name="sud_game_type"
                                class="form-control @error('sud_game_type') is-invalid @enderror"
                                value="{{ old('sud_game_type') }}" placeholder="Ludo / Carrom / Teen Patti">

                            @error('sud_game_type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">
                                Description
                            </label>

                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                                placeholder="Enter game description">{{ old('description') }}</textarea>

                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Entry Coins -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">
                                Entry Coins <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="entry_coins"
                                class="form-control @error('entry_coins') is-invalid @enderror"
                                value="{{ old('entry_coins') }}" placeholder="100">

                            @error('entry_coins')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Min Coins -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">
                                Min Coins
                            </label>

                            <input type="number" name="min_coins"
                                class="form-control @error('min_coins') is-invalid @enderror"
                                value="{{ old('min_coins') }}" placeholder="100">

                            @error('min_coins')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Max Coins -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">
                                Max Coins
                            </label>

                            <input type="number" name="max_coins"
                                class="form-control @error('max_coins') is-invalid @enderror"
                                value="{{ old('max_coins') }}" placeholder="10000">

                            @error('max_coins')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Sort Order
                            </label>

                            <input type="number" name="sort_order"
                                class="form-control @error('sort_order') is-invalid @enderror"
                                value="{{ old('sort_order', 0) }}">

                            @error('sort_order')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Featured -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Featured
                            </label>

                            <select name="is_featured" class="form-control">
                                <option value="0">No</option>
                                <option value="1" {{ old('is_featured') == 1 ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <!-- Icon Upload -->
                        <div class="col-md-6 mb-4">

                            <label class="form-label fw-bold">
                                Game Icon <span class="text-danger">*</span>
                            </label>

                            <div>

                                <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                                <label for="icon"
                                    class="border rounded d-flex align-items-center justify-content-center position-relative"
                                    style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                                    <img id="iconPreview" class="position-absolute w-100 h-100 d-none"
                                        style="object-fit:cover;">

                                    <span id="iconPlus" class="fs-1">+</span>

                                </label>

                                <small class="d-block mt-1 text-muted">
                                    Recommended size : 100 × 100
                                </small>

                                @error('icon')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                            </div>

                        </div>

                        <!-- Banner Upload -->
                        <div class="col-md-6 mb-4">

                            <label class="form-label fw-bold">
                                Game Banner
                            </label>

                            <div>

                                <input type="file" name="banner" id="banner" class="d-none" accept="image/*">

                                <label for="banner"
                                    class="border rounded d-flex align-items-center justify-content-center position-relative"
                                    style="width:220px;height:120px;cursor:pointer;overflow:hidden;">

                                    <img id="bannerPreview" class="position-absolute w-100 h-100 d-none"
                                        style="object-fit:cover;">

                                    <span id="bannerPlus" class="fs-1">+</span>

                                </label>

                                <small class="d-block mt-1 text-muted">
                                    Recommended size : 600 × 300
                                </small>

                                @error('banner')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>

                        <select name="status" class="form-control">
                            <option value="1">Enable</option>
                            <option value="0">Disable</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-end gap-2">

                        <a href="{{ route('game') }}" class="btn btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Save Game
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection


@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Auto Slug
            const name = document.querySelector('input[name="name"]');
            const slug = document.getElementById('slug');

            name.addEventListener('keyup', function() {

                slug.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');

            });

            // Image Preview
            function preview(inputId, imageId, plusId) {

                const input = document.getElementById(inputId);
                const preview = document.getElementById(imageId);
                const plus = document.getElementById(plusId);

                input.addEventListener('change', function() {

                    const file = this.files[0];

                    if (!file) return;

                    const reader = new FileReader();

                    reader.onload = function(e) {

                        preview.src = e.target.result;

                        preview.classList.remove('d-none');

                        plus.classList.add('d-none');

                    }

                    reader.readAsDataURL(file);

                });

            }

            preview('icon', 'iconPreview', 'iconPlus');

            preview('banner', 'bannerPreview', 'bannerPlus');

        });
    </script>
@endsection
