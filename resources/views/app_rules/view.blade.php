@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">View Rule</h4>

            <a href="{{ route('app-rules.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <label class="fw-bold">Heading</label>
                <div class="form-control bg-light">
                    {{ $rule->heading }}
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold">Type</label>
                <div>
                    <span class="badge bg-primary text-capitalize">
                        {{ $rule->type }}
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold">Rule Content</label>

                <div class="border rounded p-3 bg-white rule-content">
                    {!! $rule->rule !!}
                </div>
            </div>

        </div>
    </div>

</div>

<style>
    .rule-content {
        min-height: 250px;
        line-height: 1.7;
        font-size: 15px;
    }

    .rule-content img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin: 10px 0;
    }

    .rule-content h1,
    .rule-content h2,
    .rule-content h3 {
        margin-top: 15px;
        font-weight: 700;
    }

    .rule-content p {
        margin-bottom: 10px;
    }
</style>
@endsection