@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($bd) ? 'Edit' : 'Add' }} BD
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('bd-user') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ isset($bd) ? route('bd-user.save', $bd->id) : route('bd-user.save') }}"
            method="POST">
            @csrf

            <div class="row g-4">

                <!-- BD USER UID -->
                <div class="col-md-6">
                    <label class="form-label">BD User UID *</label>
                    <input type="text" name="user_uid" class="form-control"
                        placeholder="Enter BD User UID"
                        value="{{ old('user_uid', $bd->user->uid ?? '') }}">
                </div>

                <!-- BIND ADMIN + ADMIN INPUT INLINE -->
                <div class="col-md-6">
                    <label class="form-label d-block">Bind Admin</label>

                    <div class="d-flex align-items-center gap-3">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bindAdmin" name="is_admin_bound" value="1"
                                {{ old('is_admin_bound', $bd->is_admin_bound ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="bindAdmin">Yes</label>
                        </div>

                        <div id="adminField" class="flex-grow-1 {{ old('is_admin_bound', $bd->is_admin_bound ?? false) ? '' : 'd-none' }}">
                            <input type="text" name="admin_uid" class="form-control"
                                placeholder="Enter Admin UID"
                                value="{{ old('admin_uid') }}">
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
                            {{ old('country_id', $bd->country_id ?? '') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- WHATSAPP -->
                <div class="col-md-6">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp_number" class="form-control"
                        value="{{ old('whatsapp_number', $bd->whatsapp_number ?? '') }}">
                </div>

                <!-- BRIEFING -->
                <div class="col-md-12">
                    <label class="form-label">Briefing</label>
                    <textarea name="briefing" class="form-control">{{ old('briefing', $bd->briefing ?? '') }}</textarea>
                </div>

                <!-- STATUS -->
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $bd->status ?? '') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $bd->status ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($bd) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@section('js')
<script>
    function toggleAdmin() {
        if ($('#bindAdmin').is(':checked')) {
            $('#adminField').removeClass('d-none');
        } else {
            $('#adminField').addClass('d-none');
        }
    }

    $('#bindAdmin').on('change', toggleAdmin);

    toggleAdmin();
</script>
@endsection