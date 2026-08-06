@extends('layouts.app')

@section('content')

    <div class="card mb-3">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-1">Agency Team Salary</h5>

                    <div class="text-muted">

                        <strong>Agency :</strong>
                        {{ $agency->user->name ?? '-' }}

                        &nbsp;&nbsp;|

                        <strong>UID :</strong>
                        {{ $agency->user->uid ?? '-' }}

                    </div>

                </div>

                <a href="{{ route('agency-team-work') }}" class="btn btn-outline-secondary">

                    <i class="fas fa-arrow-left me-1"></i>
                    Back

                </a>

            </div>

        </div>

        <div class="card-body">

            @if (count($cycles))
                <div class="table-responsive">

                    <table class="table table-bordered table-striped align-middle">

                        <thead class="bg-light">

                            <tr>

                                <th>Month</th>

                                <th>Cycle</th>

                                <th>Hosts</th>

                                <th>Team Points</th>

                                <th>Level</th>

                                <th>Host Salary</th>

                                <th>Agency Commission</th>

                                <th>Total Salary</th>

                                <th>Status</th>

                                <th width="120">Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($cycles as $cycle)
                                <tr>

                                    <td>
                                        {{ $cycle['month'] }}
                                    </td>

                                    <td>
                                        {{ $cycle['cycle'] }}
                                    </td>

                                    <td>
                                        {{ $cycle['host_count'] }}
                                    </td>

                                    <td>

                                        {{ number_format($cycle['team_points']) }}

                                    </td>

                                    <td>

                                        {{ $cycle['target_level'] }}

                                    </td>

                                    <td>

                                        ₹ {{ number_format($cycle['host_salary'], 2) }}

                                    </td>

                                    <td>

                                        ₹ {{ number_format($cycle['agency_commission'], 2) }}

                                    </td>

                                    <td>

                                        <strong>

                                            ₹ {{ number_format($cycle['total_salary'], 2) }}

                                        </strong>

                                    </td>

                                    <td>

                                        @if ($cycle['status'] == 'Settled')
                                            <span class="badge bg-success">

                                                Settled

                                            </span>
                                        @else
                                            <span class="badge bg-warning">

                                                Unsettled

                                            </span>
                                        @endif

                                    </td>

                                    <td>

                                        <a href="{{ route('agency-team-work.details', [
                                            'agency' => $agency->id,
                                            'month' => $cycle['month'],
                                            'cycle' => $cycle['cycle_no'],
                                        ]) }}"
                                            class="btn btn-primary btn-sm">

                                            <i class="fas fa-eye"></i>
                                            Details

                                        </a>

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>
            @else
                <div class="alert alert-warning mb-0">

                    No Team Work Found.

                </div>
            @endif

        </div>

    </div>

@endsection
