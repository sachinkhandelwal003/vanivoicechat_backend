@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Add App Rule</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('app-rules.store') }}" method="POST" id="addRuleForm">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Heading <span class="text-danger">*</span></label>
                    <input type="text" name="heading" class="form-control"
                        placeholder="Enter rule heading" value="{{ old('heading') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                      <select name="type" class="form-control">
                        <option value="" selected>Select Type</option>
                        <option value="family" selected>Family</option>
                        <option value="redenvelop">Redenvelope</option>
                        <option value="treasure">Treasure</option>
                        <option value="vip">Vip</option>
                        <option value="svip">Svip</option>
                    </select>
                    {{-- <input type="text" name="type" class="form-control"
                        placeholder="Enter rule Type" value="{{ old('type') }}" required>
                        <span style="color: red;">Note:Do not give space</span> --}}

                </div>

                <div class="mb-3">
                    <label class="form-label">Rule <span class="text-danger">*</span></label>
                    <textarea name="rule" id="rule" class="form-control" rows="8"
                        placeholder="Enter rule content">{{ old('rule') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('app-rules.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Save Rule
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
    $('#rule').summernote({
    height: 200
});
</script>
@endpush
