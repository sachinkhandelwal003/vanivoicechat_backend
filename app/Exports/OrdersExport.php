<?php
namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $start;
    protected $end;
    protected $orders;
    protected $totalAmount;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end   = $end;

        // Orders fetch
        $this->orders = Order::with('appUser')
            ->whereBetween('created_at', [
                $this->start . " 00:00:00",
                $this->end . " 23:59:59"
            ])
            ->get();

        // SUM of all totals
        $this->totalAmount = $this->orders->sum('grand_total');
    }

    public function collection()
    {
        return $this->orders;
    }

    public function map($row): array
    {
        return [
            $row->order_number,
            $row->appUser->name ?? 'N/A',
            $row->grand_total,
            Carbon::parse($row->created_at)->format('d-m-Y H:i'),
            $row->delivery_status,
        ];
    }

    public function headings(): array
    {
        return [
            'Order Number',
            'Customer Name',
            'Total Amount',
            'Date',
            'Status'
        ];
    }

    // 🔥 Add TOTAL row using AfterSheet
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet;

                // Last row
                $lastRow = count($this->orders) + 2;

                // TOTAL row data
                $sheet->setCellValue('A' . $lastRow, 'TOTAL');
                $sheet->setCellValue('C' . $lastRow, $this->totalAmount);

                // Bold total row
                $sheet->getStyle('A' . $lastRow . ':E' . $lastRow)->getFont()->setBold(true);

                // Background highlight
                $sheet->getStyle('A' . $lastRow . ':E' . $lastRow)
                    ->getFill()->setFillType('solid')
                    ->getStartColor()->setARGB('FFE8E8E8');
            }
        ];
    }
}
