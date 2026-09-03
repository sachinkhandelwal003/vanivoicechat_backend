<?php

namespace App\Exports;

use App\Models\HostSalarySettlement;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryLogExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $cycle;
    protected $hostUid;
    protected $agencyId;
    protected $countryId;
    protected $status;

    public function __construct(
        $cycle     = null,
        $hostUid   = null,
        $agencyId  = null,
        $countryId = null,
        $status    = null
    ) {
        $this->cycle     = $cycle;
        $this->hostUid   = $hostUid;
        $this->agencyId  = $agencyId;
        $this->countryId = $countryId;
        $this->status    = $status;
    }

    public function query()
    {
        $query = HostSalarySettlement::with(['host.user', 'host.country', 'agency.user'])
            ->latest();

        if (!empty($this->cycle)) {
            $query->where('cycle', $this->cycle);
        }

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->agencyId)) {
            $query->where('agency_id', $this->agencyId);
        }

        if (!empty($this->hostUid)) {
            $uid = $this->hostUid;
            $query->whereHas('host.user', function ($q) use ($uid) {
                $q->where('uid', $uid);
            });
        }

        if (!empty($this->countryId)) {
            $countryId = $this->countryId;
            $query->whereHas('host', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Date & Time',
            'Host Name',
            'Host UID',
            'Country',
            'Agency Name',
            'Agency ID',
            'Cycle',
            'Level',
            'Target Gift Value',
            'Host Salary ($)',
            'Agency Commission ($)',
            'Status',
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $hostUser    = optional($row->host)->user;
        $hostName    = $hostUser ? $hostUser->name  : '-';
        $hostUid     = $hostUser ? $hostUser->uid   : '-';
        $country     = optional(optional($row->host)->country)->name ?? '-';

        $agencyUser  = optional($row->agency)->user;
        $agencyName  = $agencyUser ? $agencyUser->name : '-';
        $agencyId    = $row->agency_id ?? '-';

        $cycle       = $row->month . ' (Cycle ' . $row->cycle . ')';
        $level       = 'Level ' . ($row->level ?? 0);
        $targetValue = number_format((int) ($row->target_value ?? 0), 0, '.', '');
        $hostSalary  = number_format((float) ($row->host_salary ?? 0), 2, '.', '');
        $agencyComm  = number_format((float) ($row->agency_commission ?? 0), 2, '.', '');
        $status      = ucfirst($row->status ?? '-');

        $settledAt = $row->settled_at
            ? Carbon::parse($row->settled_at)->timezone('Asia/Kolkata')->format('d M Y h:i A')
            : '-';

        return [
            $index,
            $settledAt,
            $hostName,
            $hostUid,
            $country,
            $agencyName,
            $agencyId,
            $cycle,
            $level,
            $targetValue,
            $hostSalary,
            $agencyComm,
            $status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '6C3FC8']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
