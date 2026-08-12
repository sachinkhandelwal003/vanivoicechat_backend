<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\Host;
use App\Models\HostPolicy;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HostWorkExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $country;
    protected $cycle;
    protected $month;

    protected Collection $rows;

    public function __construct($country = null, $cycle = null, $month = null)
    {
        $this->country = $country;
        $this->cycle = $cycle;
        $this->month = $month;
    }

    public function collection()
    {
        //    Get Hosts

        $query = Host::with(['user', 'agency.user',])
            ->where('status', 1)
            ->whereNotNull('agency_id');

        //  Country Filter
        if (!empty($this->country)) {
            $query->where('country_id', $this->country);
        }

        $hosts = $query->get();
        $rows = collect();

        if ($hosts->isEmpty()) {
            $this->rows = $rows;
            return $rows;
        }

        //  Get Months

        // Month selected:
        //      Only selected month
        //  Month not selected:
        //      All available months from first host
        //      creation month to current month


        if (!empty($this->month)) {

            // Selected month only
            $date = Carbon::createFromFormat('Y-m', $this->month);

            $months = collect([$date->copy()->startOfMonth()]);
        } else {

            // No month selected = ALL months
            $firstHost = $hosts->sortBy('created_at')->first();
            $firstMonth = Carbon::parse($firstHost->created_at)->startOfMonth();
            $lastMonth = now()->copy()->startOfMonth();
            $months = collect();
            $currentMonth = $firstMonth->copy();

            while ($currentMonth->lte($lastMonth)) {
                $months->push($currentMonth->copy());
                $currentMonth->addMonth();
            }
        }

        // Group Hosts By Agency
        $agencyHosts = $hosts->groupBy('agency_id');

        //   Generate Month + Cycle + Agency Rows
        foreach ($months as $monthDate) {

            $monthStart = $monthDate->copy()->startOfMonth()->startOfDay();
            $monthEnd = $monthDate->copy()->endOfMonth()->endOfDay();

            foreach ($agencyHosts as $agencyId => $agencyHostList) {

                if (!$agencyId) {
                    continue;
                }

                $agency = $agencyHostList->first()->agency;

                if (!$agency) {
                    continue;
                }

                // 1 - 15 Cycle

                if (empty($this->cycle) || $this->cycle === '1-15') {

                    $cycleStart = $monthStart->copy();
                    $cycleEnd = $monthStart->copy()->day(15)->endOfDay();

                    $row = $this->buildAgencyCycleRow(
                        $agency,
                        $agencyHostList,
                        $cycleStart,
                        $cycleEnd,
                        '1-15'
                    );

                    if ($row) {
                        $rows->push($row);
                    }
                }

                //  16 - End Cycle

                if (empty($this->cycle) || $this->cycle === '16-end') {

                    $cycleStart = $monthStart->copy()->day(16)->startOfDay();
                    $cycleEnd = $monthEnd->copy();

                    $row = $this->buildAgencyCycleRow(
                        $agency,
                        $agencyHostList,
                        $cycleStart,
                        $cycleEnd,
                        '16-' . $monthStart->daysInMonth
                    );

                    if ($row) {
                        $rows->push($row);
                    }
                }
            }
        }

        //    Latest Cycle First
        $rows = $rows->sortByDesc(function ($row) {
            return $row->cycle_start->timestamp;
        })->values();

        $this->rows = $rows;
        return $rows;
    }

    //    Build Agency Cycle Row
    protected function buildAgencyCycleRow($agency, Collection $hosts, Carbon $cycleStart, Carbon $cycleEnd, string $cycleName)
    {
        // Do not calculate future cycle
        if ($cycleStart->gt(now())) {
            return null;
        }

        //  Hosts which existed before cycle ended

        $cycleHosts = $hosts->filter(function ($host) use ($cycleEnd) {
            return Carbon::parse($host->created_at)->lte($cycleEnd);
        });

        if ($cycleHosts->isEmpty()) {
            return null;
        }

        $totalReceived = 0;
        $totalSending = 0;
        $totalHostSalary = 0;
        $totalAgencySalary = 0;
        $highestLevel = 0;

        // Calculate Every Host

        foreach ($cycleHosts as $host) {

            $hostCreatedAt = Carbon::parse($host->created_at);

            // Work Start
            $workStart = $hostCreatedAt->gt($cycleStart) ? $hostCreatedAt->copy() : $cycleStart->copy();

            // Work End
            $workEnd = $cycleEnd->gt(now()) ? now()->copy() : $cycleEnd->copy();
            if ($workStart->gt($workEnd)) {
                continue;
            }

            // Received Gifts

            $received = DB::table('gift_transactions')
                ->where('receiver_id', $host->user_id)
                ->whereBetween('created_at', [$workStart, $workEnd])
                ->sum('total_value');

            // Sending Gifts

            $sending = DB::table('gift_transactions')
                ->where('sender_id', $host->user_id)
                ->whereBetween('created_at', [$workStart, $workEnd])
                ->sum('total_value');

            $totalReceived += $received;
            $totalSending += $sending;

            // Host Policy
            $policy = HostPolicy::where('status', 1)
                ->where('target_value', '<=', $received)
                ->orderByDesc('target_value')
                ->first();

            if ($policy) {

                $level = (int) $policy->level;
                if ($level > $highestLevel) {
                    $highestLevel = $level;
                }
                $totalHostSalary += (float) $policy->host_salary;
                $totalAgencySalary += (float) $policy->agent_commission;
            }
        }

        $now = now();

        return (object) [
            'agency' => $agency,
            'country' => optional($agency->user)->country ?? '-',
            'cycle' => $cycleStart->format('M') . ' ' . $cycleName . ', ' . $cycleStart->year,
            'cycle_start' => $cycleStart->copy(),
            'cycle_end' => $cycleEnd->copy(),
            'received_gift' => (int) $totalReceived,
            'sending_gift' => (int) $totalSending,
            'level' => (int) $highestLevel,
            'host_salary' => (float) $totalHostSalary,
            'agency_salary' => (float) $totalAgencySalary,
            // 'settlement_status' => 'UNSETTLED',
            // 'payment_status' => 'UNPAID',
            'created_at' => $now,
            // 'updated_at' => $now,
        ];
    }

    public function headings(): array
    {
        return [
            'Agency Information',
            'Country',
            'Cycle',
            'Gift Received',
            'Gift Sending',
            'Level',
            'Host Salary',
            'Agency Salary',
            'Total Salary',
            // 'Settlement Status',
            // 'Salary Payment Status',
            'Created At',
            // 'Updated At',
        ];
    }

    public function map($row): array
    {
        $agencyUser = $row->agency->user ?? null;
        $agencyName = $agencyUser ? $agencyUser->name : '-';

        $hostSalary = (float) ($row->host_salary ?? 0);
        $agencySalary = (float) ($row->agency_salary ?? 0);

        $totalSalary = $hostSalary + $agencySalary;
        $level = ($row->level !== null && $row->level !== '')
            ? (string) $row->level : '0';

        return [

            $agencyName,
            $row->country ?? '-',
            $row->cycle ?? '-',
            number_format((int) ($row->received_gift ?? 0), 0, '.', ''),
            number_format((int) ($row->sending_gift ?? 0), 0, '.', ''),
            $level,
            number_format($hostSalary, 2, '.', ''),
            number_format($agencySalary, 2, '.', ''),
            number_format($totalSalary, 2, '.', ''),
            // strtoupper($row->settlement_status ?? 'UNSETTLED'),
            // strtoupper($row->payment_status ?? 'UNPAID'),
            $row->created_at ? Carbon::parse($row->created_at)->timezone('Asia/Kolkata')->format('Y-m-d h:i A') : '-',
            // $row->updated_at ? Carbon::parse($row->updated_at)->timezone('Asia/Kolkata')->format('Y-m-d h:i A') : '-',
        ];
    }
}
