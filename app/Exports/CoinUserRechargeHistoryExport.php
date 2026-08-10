<?php

namespace App\Exports;

use App\Models\AppUser;
use App\Models\CoinRechargeHistory;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CoinUserRechargeHistoryExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    protected $filters;

    protected $users;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = CoinRechargeHistory::query()
            ->whereIn('transaction_type', [
                'user_recharge',
                'merchant_to_user',
            ]);

        /*
        |--------------------------------------------------------------------------
        | User ID
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['user_id'])) {

            $userId = $this->filters['user_id'];

            $query->where('user_id', $userId);
        }

        /*
        |--------------------------------------------------------------------------
        | User UID
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['user_uid'])) {

            $user = AppUser::where('uid', $this->filters['user_uid'])->first();

            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sender ID
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['sender_id'])) {

            $query->where('seller_id', $this->filters['sender_id']);
        }

        /*
        |--------------------------------------------------------------------------
        | Sender UID
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['sender_uid'])) {

            $sender = AppUser::where('uid', $this->filters['sender_uid'])->first();

            if ($sender) {
                $query->where('seller_id', $sender->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Role
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['role'])) {

            if ($this->filters['role'] === 'merchant') {

                $query->where('transaction_type', 'merchant_to_user');
            } elseif ($this->filters['role'] === 'coinseller') {

                $query->where('transaction_type', 'user_recharge');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Country
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['country'])) {

            $query->whereHas('user', function ($q) {
                $q->where('country', $this->filters['country']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Specific Date
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['date'])) {

            $query->whereDate(
                'created_at',
                $this->filters['date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Month
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['month'])) {

            $query->whereMonth(
                'created_at',
                Carbon::parse($this->filters['month'] . '-01')->month
            );

            $query->whereYear(
                'created_at',
                Carbon::parse($this->filters['month'] . '-01')->year
            );
        }

        $histories = $query
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Load Sender + Receiver
        |--------------------------------------------------------------------------
        */

        $userIds = $histories
            ->flatMap(function ($row) {
                return [
                    $row->seller_id,
                    $row->user_id,
                ];
            })
            ->filter()
            ->unique()
            ->values();

        $this->users = AppUser::whereIn('id', $userIds)
            ->select(
                'id',
                'uid',
                'name',
                'country'
            )
            ->get()
            ->keyBy('id');

        return $histories;
    }

    public function headings(): array
    {
        return [
            'Recharge By',
            'Sender Name',
            // 'Sender ID',
            'Sender UID',
            'User Name',
            // 'User ID',
            'User UID',
            'Country',
            'Coins',
            'Transaction Type',
            'Remark',
            'Recharge Date',
        ];
    }

    public function map($row): array
    {
        $sender = $this->users->get($row->seller_id);
        $user = $this->users->get($row->user_id);

        return [
            $row->transaction_type === 'merchant_to_user'
                ? 'Merchant'
                : 'Coin Seller',

            $sender?->name ?? '-',

            // $sender?->id ?? '-',

            $sender?->uid ?? '-',

            $user?->name ?? '-',

            // $user?->id ?? $row->user_id ?? '-',

            $user?->uid ?? $row->user_uid ?? '-',

            $user?->country ?? '-',

            $row->coin ?? 0,

            $row->transaction_type ?? '-',

            $row->remark ?? '-',

            $row->created_at
                ? Carbon::parse($row->created_at)
                ->timezone('Asia/Kolkata')
                ->format('Y-m-d h:i A')
                : '-',
        ];
    }
}
