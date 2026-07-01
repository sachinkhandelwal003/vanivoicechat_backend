@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">

        <div class="d-flex align-items-center">

            <i class="fas fa-wallet fa-2x text-primary me-3"></i>

            <div>

                <h4 class="mb-0">Manual Money</h4>

                <small class="text-muted">
                    Credit / Deduct Wallet Balance
                </small>

            </div>

        </div>

    </div>

    <div class="card-body">

        {{-- Tabs --}}

        <ul class="nav nav-tabs mb-4">

            <li class="nav-item">

                <a class="nav-link"
                    href="{{ route('manual-transfer.index') }}">

                    <i class="fas fa-list me-1"></i>

                    Manual Money List

                </a>

            </li>

            <li class="nav-item">

                <a class="nav-link active" href="{{ route('manual-transfer.form') }}">

                    <i class="fas fa-plus-circle me-1"></i>

                    Add

                </a>

            </li>

        </ul>

        <form id="manualMoneyForm"
            action="{{ route('manual-transfer.save') }}"
            method="POST">

            @csrf

            <input
                type="hidden"
                name="type"
                id="type"
                value="credit">

            <div class="row mb-4">

                <div class="col-md-6">

                    <button
                        type="button"
                        id="creditBtn"
                        class="btn btn-primary w-100 btn-lg active-btn">

                        <i class="fas fa-plus me-2"></i>

                        Credit

                    </button>

                </div>

                <div class="col-md-6">

                    <button
                        type="button"
                        id="deductBtn"
                        class="btn btn-outline-danger w-100 btn-lg">

                        <i class="fas fa-minus me-2"></i>

                        Deduct

                    </button>

                </div>

            </div>

            <div class="mb-3">

                <label class="form-label">

                    User UID

                    <span class="text-danger">*</span>

                </label>

                <input
                    type="text"
                    id="uid"
                    name="uid"
                    class="form-control"
                    placeholder="Enter User UID">

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Amount

                    <span class="text-danger">*</span>

                </label>

                <input
                    type="number"
                    step="0.01"
                    id="amount"
                    name="amount"
                    class="form-control"
                    placeholder="Enter Amount">

            </div>

            <div class="mb-4">

                <label class="form-label">

                    Reason

                </label>

                <textarea
                    id="reason"
                    name="reason"
                    rows="4"
                    class="form-control"
                    placeholder="Enter reason..."></textarea>

            </div>

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-success">

                    <i class="fas fa-save me-2"></i>

                    Save

                </button>

                <a
                    href="{{ route('manual-transfer.index') }}"
                    class="btn btn-light">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

@endsection


@push('styles')

<style>
    .active-btn {

        background: #4f46e5 !important;

        color: #fff !important;

        border-color: #4f46e5 !important;

    }
</style>

@endpush


@push('scripts')

<script>
    $("#creditBtn").click(function() {

        $("#type").val("credit");

        $(this)
            .removeClass("btn-outline-primary")
            .addClass("btn-primary active-btn");

        $("#deductBtn")
            .removeClass("btn-danger active-btn")
            .addClass("btn-outline-danger");

    });



    $("#deductBtn").click(function() {

        $("#type").val("deduct");

        $(this)
            .removeClass("btn-outline-danger")
            .addClass("btn-danger active-btn");

        $("#creditBtn")
            .removeClass("btn-primary active-btn")
            .addClass("btn-outline-primary");

    });

    $("#manualMoneyForm").submit(function(e) {

        e.preventDefault();

        $.ajax({

            url: $(this).attr("action"),

            type: "POST",

            data: $(this).serialize(),

            beforeSend: function() {

                $("button[type='submit']").prop("disabled", true);

            },

            success: function(res) {

                $("button[type='submit']").prop("disabled", false);

                if (res.status) {

                    toastr.success(res.message);

                    setTimeout(function() {
                        window.location.href = "{{ route('manual-transfer.index') }}";
                    }, 1000);

                } else {

                    toastr.error(res.message);

                }

            },
            error: function(xhr) {

                $("button[type='submit']").prop("disabled", false);

                showErrorSnackbar(
                    xhr.responseJSON?.message ?? "Something went wrong."
                );

            }

        });

    });
</script>

@endpush