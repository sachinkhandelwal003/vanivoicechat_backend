<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Host;
use App\Models\Agency;
use App\Models\AppUser;
use App\Models\HostPolicy;
use Illuminate\Console\Command;
use App\Models\GiftTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\AgencySalarySettlement;

class SettleAgencySalary extends Command
{
    // protected $signature = 'agency:settle-salary';
    protected $signature = 'agency:settle-salary {month?} {cycle?}';

    protected $description = 'Settle agency commission automatically';


    // public function handle()
    // {
    //     $this->settleCycle('2026-06', 2);

    //     return Command::SUCCESS;
    // }

    public function handle()
    {
        $month = $this->argument('month');
        $cycle = $this->argument('cycle');

        // Manual Run
        if ($month && $cycle) {

            $this->settleCycle($month, (int) $cycle);

            $this->info('Agency salary settled successfully.');

            return Command::SUCCESS;
        }

        
        $today = now();

        /*
        |--------------------------------------------------------------------------
        | Cycle 1 Settlement
        | 01-15 => Credit on 16th
        |--------------------------------------------------------------------------
        */

        if ($today->day == 16) {

            $this->settleCycle(
                $today->format('Y-m'),
                1
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Cycle 2 Settlement
        | 16-End => Credit on Next Month 1st
        |--------------------------------------------------------------------------
        */

        if ($today->day == 1) {

            $this->settleCycle(
                $today->copy()
                    ->subMonth()
                    ->format('Y-m'),
                2
            );
        }

        return Command::SUCCESS;
    }



    private function settleCycle(
        string $month,
        int $cycle
    ) {

        $agencies = Agency::with('country')
            ->where('status', 1)
            ->where('invite_status', 'accept')
            ->get();

        foreach ($agencies as $agency) {

            DB::transaction(function () use (
                $agency,
                $month,
                $cycle
            ) {

                /*
                |--------------------------------------------------------------------------
                | Already Settled
                |--------------------------------------------------------------------------
                */

                $exists = AgencySalarySettlement::where([
                    'agency_id' => $agency->id,
                    'month'     => $month,
                    'cycle'     => $cycle,
                ])->exists();

                if ($exists) {
                    return;
                }

                $monthDate = Carbon::createFromFormat(
                    'Y-m',
                    $month
                );

                /*
                |--------------------------------------------------------------------------
                | Cycle Date Range
                |--------------------------------------------------------------------------
                */

                if ($cycle == 1) {

                    $from = $monthDate
                        ->copy()
                        ->startOfMonth();

                    $to = $monthDate
                        ->copy()
                        ->startOfMonth()
                        ->addDays(14)
                        ->endOfDay();
                } else {

                    $from = $monthDate
                        ->copy()
                        ->startOfMonth()
                        ->addDays(15);

                    $to = $monthDate
                        ->copy()
                        ->endOfMonth();
                }

                /*
                |--------------------------------------------------------------------------
                | Agency Hosts
                |--------------------------------------------------------------------------
                */

                $hosts = Host::where(
                    'agency_id',
                    $agency->id
                )
                    ->where('invite_status', 'accept')
                    ->where('status', 1)
                    ->get();

                $totalTarget = 0;
                $totalAgentCommission = 0;

                foreach ($hosts as $host) {

                    $hostJoinDate = Carbon::parse(
                        $host->created_at
                    );

                    $hostFrom = $from->copy();

                    if ($hostFrom < $hostJoinDate) {
                        $hostFrom = $hostJoinDate;
                    }

                    $giftTotal = GiftTransaction::where(
                        'receiver_id',
                        $host->user_id
                    )
                        ->whereBetween(
                            'created_at',
                            [$hostFrom, $to]
                        )
                        ->sum('total_value');

                    $policy = HostPolicy::where(
                        'country',
                        $agency->country->name
                    )
                        ->where('status', 1)
                        ->where(
                            'target_value',
                            '<=',
                            $giftTotal
                        )
                        ->orderByDesc('level')
                        ->first();

                    $totalTarget += $giftTotal;

                    $totalAgentCommission +=
                        ($policy->agent_commission ?? 0);
                }

                /*
                |--------------------------------------------------------------------------
                | Credit Agency Commission
                |--------------------------------------------------------------------------
                */

                if ($totalAgentCommission > 0) {

                    $user = AppUser::find(
                        $agency->user_id
                    );

                    $user->balance =
                        ($user->balance ?? 0)
                        + $totalAgentCommission;

                    $user->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Settlement Entry
                |--------------------------------------------------------------------------
                */

                AgencySalarySettlement::create([

                    'agency_id'     => $agency->id,
                    'user_id'       => $agency->user_id,

                    'month'         => $month,
                    'cycle'         => $cycle,

                    'target_value'  => $totalTarget,

                    'policy_id'     => null,
                    'level'         => 0,

                    'agent_salary'  => $totalAgentCommission,
                    'total_salary'  => $totalAgentCommission,

                    'status'        => 'settled',

                    'settled_at'    => now(),
                ]);
            });
        }
    }
}
