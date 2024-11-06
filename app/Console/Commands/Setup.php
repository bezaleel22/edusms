<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class Setup extends Command
{
    protected $name = 'app:setup';

    public function handle()
    {
        $ac = env('ACCES_CODE', '');
        $email = env('ADMIN_EMAIL', 'onosbrown.saved@gmail.com');
        $password = env('ADMIN_PASSWORD', '#1414bruno#');

        Storage::put('.app_installed', $ac);
        Storage::put('.user_email', $email);
        Storage::put('.user_pass', $password);

        $this->info('Setup completed');

        return 0;
    }
}
