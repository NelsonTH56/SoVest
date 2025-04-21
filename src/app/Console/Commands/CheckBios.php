<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckBios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-bios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $usersWithBio = \App\Models\User::whereNotNull('bio')->get(['id', 'bio']);

        if ($usersWithBio->isEmpty()) {
            $this->info("No bios found.");
        } else {
            $this->table(['ID', 'Bio'], $usersWithBio->toArray());
        }
    }
}
