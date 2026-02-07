<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
//MODELS
use App\Models\User;
//EVENTS
use App\Events\UserPresenceChanged;

class CleanInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clean-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark users as offline if inactive for more than 5 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = now()->subMinutes(5);

        $inactiveUsers = User::where('status', 'online')
            ->where('last_seen', '<', $threshold)
            ->get();

        foreach ($inactiveUsers as $user) {
            $user->update(['status' => 'offline']);
            broadcast(new UserPresenceChanged($user, false));
            
            Log::info("User {$user->id} marked as offline due to inactivity");
        }

        $this->info("Marked {$inactiveUsers->count()} users as offline");
        
        return Command::SUCCESS;
    }
}
