<?php

namespace App\Jobs;

use App\Events\ReportGenerated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTaskReport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Simulate heavy data computation / report compilation
        sleep(5);

        // Dispatches our WebSocket notification event once the database calculation resolves
        event(new ReportGenerated($this->user, 'Your custom CSV history export is complete!'));
    }
}
