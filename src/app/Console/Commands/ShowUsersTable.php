<?php

namespace App\Console\Commands;
use App\Models\User;

use Illuminate\Console\Command;

class ShowUsersTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:show-users-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays a table of users in the terminal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all(['id', 'first_name', 'last_name', 'email', 'reputation_score', 'profile_picture', 'bio']);

        $this->table(
            ['ID', 'First Name', 'Last Name', 'Email', 'Reputation', 'Profile Picture', 'Bio'],
            $users->toArray()
        );
    }
}
