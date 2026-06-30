@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($agency) ? 'Edit' : 'Add' }} Agency
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('agency') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ isset($agency) ? route('agency.save', $agency->id) : route('agency.save') }}"
            method="POST">
            @csrf

            <div class="row g-4">

                <!-- USER UID -->
                <div class="col-md-6">
                    <label class="form-label">User UID *</label>
                    <input type="text" name="user_uid" class="form-control"
                        value="{{ old('user_uid', $agency->user->uid ?? '') }}">
                </div>

                <!-- BIND BD + BD INPUT INLINE -->
                <div class="col-md-6">
                    <label class="form-label d-block">Bind BD</label>

                    <div class="d-flex align-items-center gap-3">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bindBD" name="is_bd_bound" value="1"
                                {{ old('is_bd_bound', $agency->is_bd_bound ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="bindBD">Yes</label>
                        </div>

                        <div id="bdField" class="flex-grow-1 {{ old('is_bd_bound', $agency->is_bd_bound ?? false) ? '' : 'd-none' }}">
                            <input type="text" name="bd_user_uid" class="form-control"
                                placeholder="Enter BD User UID"
                                value="{{ old('bd_user_uid') }}">
                        </div>

                    </div>
                </div>

                <!-- COUNTRY -->
                <div class="col-md-6">
                    <label class="form-label">Country *</label>
                    <select name="country_id" class="form-control">
                        <option value="">-- Select Country --</option>
                        @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                            {{ old('country_id', $agency->country_id ?? '') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- WHATSAPP -->
                <div class="col-md-6">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp_number" class="form-control"
                        value="{{ old('whatsapp_number', $agency->whatsapp_number ?? '') }}">
                </div>

                <!-- BRIEFING -->
                <div class="col-md-12">
                    <label class="form-label">Briefing</label>
                    <textarea name="briefing" class="form-control">{{ old('briefing', $agency->briefing ?? '') }}</textarea>
                </div>

                <!-- STATUS -->
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $agency->status ?? '') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $agency->status ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($agency) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@section('js')
<script>
    function toggleBD() {
        if ($('#bindBD').is(':checked')) {
            $('#bdField').removeClass('d-none');
        } else {
            $('#bdField').addClass('d-none');
        }
    }

    $('#bindBD').on('change', toggleBD);

    toggleBD();
</script>
@endsection