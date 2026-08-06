@extends('layouts.app')

@section('title', 'Agency Team Contribution')

@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="mb-0">
                        Team Contribution
                    </h4>

                    <small class="text-muted">
                        Agency :
                        <strong>{{ $agency->user->name ?? '-' }}</strong>
                        |
                        Month :
                        <strong>{{ $month }}</strong>
                        |
                        Cycle :
                        <strong>{{ $cycle == 1 ? '01-15' : '16-End' }}</strong>
                    </small>
                </div>

                <a href="{{ route('agency-team-work.show', $agency->id) }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i>
                    Back
                </a>

            </div>

            <div class="card-body">

                @php

                    $teamTarget = 0;
                    $hostSalary = 0;
                    $agencyCommission = 0;
                    $totalSalary = 0;

                @endphp

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th width="60">
                                    #
                                </th>

                                <th>
                                    Host
                                </th>

                                <th>
                                    Country
                                </th>

                                <th class="text-center">
                                    Target
                                </th>

                                <th class="text-center">
                                    Level
                                </th>

                                <th class="text-center">
                                    Host Salary
                                </th>

                                <th class="text-center">
                                    Agency Commission
                                </th>

                                <th class="text-center">
                                    Total Salary
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($data as $key => $row)
                                @php

                                    $teamTarget += $row['target'];
                                    $hostSalary += $row['host_salary'];
                                    $agencyCommission += $row['agency_commission'];
                                    $totalSalary += $row['total_salary'];

                                @endphp

                                <tr>

                                    <td>

                                        {{ $key + 1 }}

                                    </td>

                                    <td>

                                        <div class="d-flex align-items-center">

                                            @php
                                                $image = $row['user']->image
                                                    ? Helper::showImage($row['user']->image, true)
                                                    : asset('assets/img/avatar.png');
                                            @endphp

                                            <img src="{{ $image }}" width="45" height="45"
                                                class="rounded-circle me-2">

                                            <div>

                                                <strong>

                                                    {{ $row['user']->name }}

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    UID :
                                                    {{ $row['user']->id }}

                                                </small>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        {{ $row['country']->nicename ?? '-' }}

                                    </td>

                                    <td class="text-center">

                                        <strong>

                                            {{ number_format($row['target']) }}

                                        </strong>

                                    </td>

                                    <td class="text-center">

                                        <span class="badge bg-primary">

                                            {{ $row['level'] }}

                                        </span>

                                    </td>

                                    <td class="text-center">

                                        {{ number_format($row['host_salary'], 2) }}

                                    </td>

                                    <td class="text-center">

                                        {{ number_format($row['agency_commission'], 2) }}

                                    </td>

                                    <td class="text-center">

                                        <strong>

                                            {{ number_format($row['total_salary'], 2) }}

                                        </strong>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center">

                                        No Record Found

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                        @if (count($data))
                            <tfoot class="table-light">

                                <tr>

                                    <th colspan="3">

                                        Team Total

                                    </th>

                                    <th class="text-center">

                                        {{ number_format($teamTarget) }}

                                    </th>

                                    <th></th>

                                    <th class="text-center">

                                        {{ number_format($hostSalary, 2) }}

                                    </th>

                                    <th class="text-center">

                                        {{ number_format($agencyCommission, 2) }}

                                    </th>

                                    <th class="text-center">

                                        {{ number_format($totalSalary, 2) }}

                                    </th>

                                </tr>

                            </tfoot>
                        @endif

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection
