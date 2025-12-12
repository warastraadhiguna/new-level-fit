<?php

namespace App\Console\Commands;

use App\Models\Member\CheckInMember;
use App\Models\Trainer\CheckInTrainerSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCheckout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gym:auto-checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check out all open check-ins at end of day';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');

        $membersUpdated = CheckInMember::whereNull('check_out_time')
            ->update(['check_out_time' => $now]);

        $trainerUpdated = CheckInTrainerSession::whereNull('check_out_time')
            ->update(['check_out_time' => $now]);

        Log::info('gym:auto-checkout executed', [
            'at' => $now->toDateTimeString(),
            'members_updated' => $membersUpdated,
            'trainer_sessions_updated' => $trainerUpdated,
        ]);

        $this->info("Done. members={$membersUpdated}, trainer_sessions={$trainerUpdated}");

        return self::SUCCESS;
    }
}