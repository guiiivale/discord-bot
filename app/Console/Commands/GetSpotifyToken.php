<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetSpotifyToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-spotify-token';

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
        $spotifyService = app()->make('App\Services\SpotifyService');

        $spotifyService->getToken();

        $this->info('Token retrieved successfully!');

        return 0;
    }
}
