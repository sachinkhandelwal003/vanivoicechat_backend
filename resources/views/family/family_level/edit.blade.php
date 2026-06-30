@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Edit Family Rank Level
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('family.level.edit', $fRankLevel->id) }}" method="POST" enctype="multipart/form-data">
                @csrf


                <div class="mb-3">
                    <label class="form-label fw-bold">Rank Level <span class="text-danger">*</span></label>
                    <input type="text" name="level"
                        class="form-control @error('level') is-invalid @enderror"
                        value="{{ old('level',$fRankLevel->level) }}">
                    @error('level')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Level Badge <span class="text-danger">*</span>
                    </label>

                    <div>
                        <input type="file" name="badge" id="icon" class="d-none" accept="image/*">

                        <label for="icon"
                            class="border rounded d-flex align-items-center justify-content-center position-relative"
                            style="width:150px;height:120px;cursor:pointer;overflow:hidden;background:#f8f9fa;">

                            @php
                            $badgeImage = $fRankLevel->badge ?? null;
                            @endphp

                            <img id="coverPreview"
                                src="{{ $badgeImage ? asset('storage/'.$badgeImage) : '' }}"
                                class="position-absolute w-100 h-100 {{ $badgeImage ? '' : 'd-none' }}"
                                style="object-fit:cover;">

                            <span id="coverPlus"
                                class="fs-1 fw-bold text-muted {{ $badgeImage ? 'd-none' : '' }}">+</span>
                        </label>

                        <small class="d-block mt-1 text-muted">
                            Recommended size: 50 × 50
                        </small>

                        @error('badge')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>




                <div class="mb-3">
                    <label class="form-label fw-bold">Required Points <span class="text-danger">*</span></label>
                    <input type="text" name="required_points"
                        class="form-control @error('required_points') is-invalid @enderror"
                        value="{{ old('required_points',$fRankLevel->required_points) }}">
                    @error('required_points')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('family.level',$fRankLevel->family_rank_id) }}" class="btn btn-secondary">Cancel</a>
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