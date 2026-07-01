<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Host;
use App\Models\AppUser;
use App\Models\HostPolicy;
use Illuminate\Console\Command;
use App\Models\GiftTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\HostSalarySettlement;

class SettleHostSalary extends Command
{
    // protected $signature = 'host:settle-salary';
    protected $signature = 'host:settle-salary {month?} {cycle?}';

    protected $description = 'Settle host salary automatically';

    public function handle()
    {
        $month = $this->argument('month');
        $cycle = $this->argument('cycle');
        /*
    |--------------------------------------------------------------------------
    | Manual Run
    |--------------------------------------------------------------------------
    */

        if ($month && $cycle) {

            $this->settleCycle(
                $month,
                (int) $cycle
            );

            $this->info("Host salary settled successfully.");

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



    // public function handle()
    // {
    //     $this->settleCycle(
    //         '2026-06',
    //         2
    //     );

    //     return Command::SUCCESS;
    // }




    private function settleCycle(
        string $month,
        int $cycle
    ) {

        $hosts = Host::with('country')
            ->where('status', 1)
            ->where('invite_status', 'accept')
            ->get();

        foreach ($hosts as $host) {

            DB::transaction(function () use (
                $host,
                $month,
                $cycle
            ) {

                /*
                |--------------------------------------------------------------------------
                | Skip If Already Settled
                |--------------------------------------------------------------------------
                */

                $exists = HostSalarySettlement::where([
                    'host_id' => $host->id,
                    'month'   => $month,
                    'cycle'   => $cycle
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
                | Date Range
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
                | Host Join Date
                |--------------------------------------------------------------------------
                */

                $hostJoinDate = Carbon::parse(
                    $host->created_at
                );

                if ($from < $hostJoinDate) {
                    $from = $hostJoinDate;
                }

                /*
                |--------------------------------------------------------------------------
                | Gift Total
                |--------------------------------------------------------------------------
                */

                $giftTotal = GiftTransaction::where(
                    'receiver_id',
                    $host->user_id
                )
                    ->whereBetween(
                        'created_at',
                        [$from, $to]
                    )
                    ->sum('total_value');

                /*
                |--------------------------------------------------------------------------
                | Policy Match
                |--------------------------------------------------------------------------
                */

                $policy = HostPolicy::where(
                    'country',
                    $host->country->nicename
                )
                    ->where('status', 1)
                    ->where(
                        'target_value',
                        '<=',
                        $giftTotal
                    )
                    ->orderByDesc('level')
                    ->first();

                $level = $policy->level ?? 0;

                $salary = $policy->host_salary ?? 0;
                $agencyCommission = $policy->agent_commission ?? 0;

                $totalSalary = $salary + $agencyCommission;

                /*
                |--------------------------------------------------------------------------
                | Credit Salary
                |--------------------------------------------------------------------------
                */

                $user = AppUser::find(
                    $host->user_id
                );

                if ($salary > 0) {

                    $user->balance =
                        ($user->balance ?? 0)
                        + $salary;

                    $user->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Settlement Entry
                |--------------------------------------------------------------------------
                */

                HostSalarySettlement::create([

                    'host_id' => $host->id,

                    'agency_id' => $host->agency_id,

                    'user_id' => $host->user_id,

                    'month' => $month,

                    'cycle' => $cycle,

                    'target_value' => $giftTotal,

                    'policy_id' => $policy->id ?? null,

                    'level' => $level,

                    'host_salary' => $salary,

                    'agency_commission' => $agencyCommission,

                    'total_salary' => $totalSalary,

                    'status' => 'settled',

                    'settled_at' => now(),
                ]);
            });
        }
    }
}
