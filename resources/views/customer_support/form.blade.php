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

                {{-- User --}}
                <div class="mb-3">
                    <label class="form-label">User ID</label>
                    <input type="text" name="user" class="form-control"
                        value="{{ isset($data) && $data->user ? $data->user->uid : '' }}" placeholder="Enter User UID"
                        required>
                </div>

                {{-- Region --}}
                <div class="mb-3">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-control" required>
                        <option value="">Select Region</option>

                        @foreach ($country as $cntry)
                            <option value="{{ $cntry->nicename }}"
                                {{ isset($data) && $data->region == $cntry->nicename ? 'selected' : '' }}>
                                {{ $cntry->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row">

                    {{-- Start Time --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control"
                            value="{{ isset($data) ? \Carbon\Carbon::parse($data->start_time)->format('h:i A') : '' }}"
                            placeholder="Select Start Time" required>
                    </div>

                    {{-- End Time --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control"
                            value="{{ isset($data) ? \Carbon\Carbon::parse($data->end_time)->format('h:i A') : '' }}"
                            placeholder="Select End Time" required>
                    </div>

                </div>

                <div class="row">

                    {{-- Priority --}}
                    {{-- <div class="col-md-6 mb-3">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority" class="form-control" min="1"
                            value="{{ $data->priority ?? 1 }}" required>
                    </div> --}}

                    {{-- Status --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="1" {{ !isset($data) || $data->status ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ isset($data) && !$data->status ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                    </div>

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
        $(document).ready(function() {

            $('#supportForm').on('submit', function(e) {

                e.preventDefault();

                $.ajax({

                    url: "{{ route('customer_support.store') }}",
                    type: "POST",
                    data: $(this).serialize(),

                    beforeSend: function() {

                        $('button[type="submit"]').prop('disabled', true);

                    },

                    success: function(response) {

                        $('button[type="submit"]').prop('disabled', false);

                        if (response.status) {

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {

                                window.location.href =
                                    "{{ route('customer_support') }}";

                            });

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });

                        }

                    },

                    error: function(xhr) {

                        $('button[type="submit"]').prop('disabled', false);

                        let message = 'Something went wrong.';

                        if (xhr.responseJSON) {

                            if (xhr.responseJSON.message) {

                                message = xhr.responseJSON.message;

                            } else if (xhr.responseJSON.errors) {

                                message = Object.values(xhr.responseJSON.errors)
                                    .flat()
                                    .join('\n');
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });

                    }

                });

            });

        });
    </script>
@endsection
