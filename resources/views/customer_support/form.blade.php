@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>{{ isset($data) ? 'Edit Customer Support' : 'Add Customer Support' }}</h5>
    </div>

    <div class="card-body">
        <form id="supportForm">
            @csrf
            <input type="hidden" name="id" value="{{ $data->id ?? '' }}">

            <div class="mb-3">
                <label>User Id</label>
                <input type="text" name="user" class="form-control" value="{{ isset($data) && $data->user ? $data->user->uid : '' }}" placeholder="Enter User id" required>
            </div>

            <div class="mb-3">
                <label>Region</label>
                <select name="region" class="form-control" required>
                    <option value="">Select Region</option>

                    @foreach($country as $cntry)
                    <option value="{{ $cntry->nicename }}"
                        {{ isset($data) && $data->region == $cntry->nicename ? 'selected' : '' }}>

                        {{ $cntry->name ?? 'No Name' }}

                    </option>
                    @endforeach

                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                {{ isset($data) ? 'Update' : 'Save' }}
            </button>

            <a href="{{ route('customer_support') }}" class="btn btn-secondary">
                Back
            </a>
        </form>
    </div>
</div>
@endsection


@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $('#supportForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('customer_support.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.status) {
                    Swal.fire('', response.message, 'success')
                        .then(() => {
                            window.location.href = "{{ route('customer_support') }}";
                        });
                }
            }
        });
    });
</script>
@endsection