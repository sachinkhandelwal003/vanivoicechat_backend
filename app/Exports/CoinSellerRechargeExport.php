<?php

namespace App\Exports;

use App\Models\AppUser;
use App\Models\CoinSellerTransaction;
use App\Models\CoinRechargeHistory;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CoinSellerRechargeExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    public function collection()
    {
        // ADMIN -> SELLER
        // sender_id      = users.id
        // receiver_id    = app_users.id

        $adminRecharge = CoinSellerTransaction::with([
            'sender:id,name',
            'receiver:id,name,uid',
        ])
            ->where('transaction_type', 'recharge')
            ->where('sender_type', 'admin')
            ->get()
            ->map(function ($row) {

                return [
                    'recharge_by' => 'Admin',
                    'sender_name' => $row->sender?->name ?? 'Admin',
                    'receiver_name' => $row->receiver?->name ?? '-',
                    'receiver_uid' => $row->receiver?->uid ?? '-',
                    'receiver_type' => $this->getSellerType($row->receiver_id),
                    'coins' => $row->coins ?? 0,
                    // 'balance_before' => $row->balance_before ?? '-',
                    // 'balance_after' => $row->balance_after ?? '-',
                    'remark' => $row->remark ?? 'Recharge by admin',
                    'created_at' => $row->created_at,
                ];
            });


        // |MERCHANT -> SELLER
        // seller_id = Merchant app_users.id
        //  user_id   = Seller app_users.id

        $merchantHistories = CoinRechargeHistory::where('role', 'merchant')
            ->where('transaction_type', 'merchant_to_seller')
            ->get();

        //    et all Merchant + Seller IDs

        $appUserIds = $merchantHistories
            ->flatMap(function ($row) {
                return [
                    $row->seller_id,
                    $row->user_id,
                ];
            })
            ->filter()
            ->unique()
            ->values();

        // get Load all app_users in ONE query

        $appUsers = AppUser::whereIn('id', $appUserIds)->get(['id', 'name', 'uid'])->keyBy('id');

        // MERCHANT -> SELLER DATA

        $merchantRecharge = $merchantHistories
            ->map(function ($row) use ($appUsers) {
                $merchant = $appUsers->get($row->seller_id);
                $seller = $appUsers->get($row->user_id);

                return [
                    'recharge_by' => 'Merchant',
                    // Merchant is from app_users
                    'sender_name' => $merchant?->name ?? '-',
                    // Seller is also from app_users
                    'receiver_name' => $seller?->name ?? '-',
                    'receiver_uid' => $seller?->uid ?? $row->user_uid ?? '-',
                    'receiver_type' => 'Coin Seller',
                    'coins' => $row->coin ?? 0,
                    // This table doesn't contain before/after balance
                    // 'balance_before' => '-',
                    // 'balance_after' => '-',
                    'remark' => $row->remark ?? 'Merchant recharge to seller',
                    'created_at' => $row->created_at,
                ];
            });

        // MERGE ADMIN + MERCHANT
        return $adminRecharge
            ->concat($merchantRecharge)
            ->sortByDesc('created_at')
            ->values();
    }

    //    GET SELLER TYPE

    private function getSellerType($userId)
    {
        $coinSeller = \App\Models\CoinSeller::where('user_id', $userId)->first();

        if (!$coinSeller) {
            return '-';
        }

        return (int) $coinSeller->is_merchant === 1 ? 'Merchant' : 'Coin Seller';
    }

    //    EXCEL HEADINGS

    public function headings(): array
    {
        return [
            'Recharge By',
            'Sender Name',
            'Receiver Name',
            'Receiver UID',
            'Receiver Type',
            'Coins',
            // 'Balance Before',
            // 'Balance After',
            'Remark',
            'Recharge Date',
        ];
    }

    // MAP

    public function map($row): array
    {
        return [
            $row['recharge_by'],
            $row['sender_name'],
            $row['receiver_name'],
            $row['receiver_uid'],
            $row['receiver_type'],
            $row['coins'],
            // $row['balance_before'],
            // $row['balance_after'],
            $row['remark'],
            $row['created_at']
                ? Carbon::parse($row['created_at'])
                ->timezone('Asia/Kolkata')
                ->format('Y-m-d h:i A')
                : '-',
        ];
    }
}
