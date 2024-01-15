<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\Parts\WebSockets\TypingStart;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use App\Services\OpGGService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $opGGService;

    public function __construct(OpGGService $opGGService)
    {
        parent::__construct();
        $this->opGGService = $opGGService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $discord = new Discord([
            'token' => Env::get('BOT_TOKEN'),
            'storeMessages' => true,
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT
        ]);

        $discord->on('ready', function (Discord $discord) {
            echo "Bot is ready!", PHP_EOL;
        });
        try {
            $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
                if ($message->author->bot) {
                    return;
                }
    
                $lastChar = substr($message->content, -1);
                $penultimateChar = substr($message->content, -2, 1);
    
                if ($lastChar === '?' || $penultimateChar === '?') {
                    $message->channel->sendMessage('Se quiser sim mano');
                }
    
                if (stripos($message->content, 'guizap') !== false) {
                    $message->react('🖕');
                }
    
                if (strtolower(substr($message->content, 0, 7)) == 'update:') {
                    $formattedName = $this->opGGService->transformPlayerName($message->content);
    
                    $data = $this->opGGService->updateData($formattedName);
    
                    if ($data['status'] != '200') {
                        $message->channel->sendMessage('Erro ao atualizar!');
                        $message->channel->sendMessage('Motivo do erro: ' . $data['data']['message']);
                        if (isset($data['data']['renewable_at'])) {
                            $dataHoraCarbon = Carbon::parse($data['data']['renewable_at']);
    
                            $dataHoraCarbon->setTimezone('America/Sao_Paulo');
    
                            $dataHoraFormatada = $dataHoraCarbon->format('d-m-Y H:i:s');
    
                            $message->channel->sendMessage('Tente novamente em: ' . $dataHoraFormatada);
                        }
                        return;
                    }
    
                    $message->channel->sendMessage('Atualizado com sucesso!');
                }
    
                if (strtolower(substr($message->content, 0, 7)) == 'player:') {
    
                    $formattedName = $this->opGGService->transformPlayerName($message->content);
    
                    $data = $this->opGGService->getDataOpGG($formattedName);
    
                    if (!isset($data['pageProps']['data']['level'])) {
                        $message->channel->sendMessage('Não encontrei nada, tente novamente, tente como o exemplo: soul#gule');
                        return;
                    }
    
                    if (isset($data['pageProps']['statusCode']) && $data['pageProps']['statusCode'] == '404') {
                        $message->channel->sendMessage('Não encontrei nada, tente novamente, tente como o exemplo: soul#gule');
                        return;
                    }
    
                    if ($data) {
                        $previousSeasons = $data['pageProps']['data']['previous_seasons'];
                        $currentLevel = $data['pageProps']['data']['level'];
                        $gameName = $data['pageProps']['data']['game_name'];
    
                        $messageString = "Previous Season Elos:\n";
    
                        foreach ($previousSeasons as $season) {
                            $tierInfo = $season['tier_info'];
                            $createdAt = date('Y-m-d', strtotime($season['created_at']));
    
                            $eloInfo = "{$tierInfo['tier']} {$tierInfo['division']} - LP: {$tierInfo['lp']}";
    
                            $messageString .= "- Season {$season['season_id']} ({$createdAt}): {$eloInfo}\n";
                        }
                        $currentLevelMessage = "Game name: {$gameName} \nCurrent Level: {$currentLevel}\n";
                        $leagueStats = $data['pageProps']['data']['league_stats'];
    
                        $leagueStatsMessage = "League Stats:\n";
    
                        foreach ($leagueStats as $stats) {
                            $queueInfo = $stats['queue_info'];
                            $tierInfo = $stats['tier_info'];
    
                            $win = $stats['win'] ? $stats['win'] : '0';
                            $lose = $stats['lose'] ? $stats['lose'] : '0';
    
                            $formattedTierInfo = $this->formatTierInfo($tierInfo);
    
                            $statInfo = "Queue: {$queueInfo['queue_translate']}\n";
                            $statInfo .= "Tier Info: {$formattedTierInfo}\n";
                            $statInfo .= "Wins: {$win}\n";
                            $statInfo .= "Losses: {$lose}\n";
    
                            $leagueStatsMessage .= "{$statInfo}\n";
                        }
    
                        $message->channel->sendMessage($currentLevelMessage . "\n" . "\n" . "\n" . "\n");
                        $message->channel->sendMessage($leagueStatsMessage . "\n" . "\n" . "\n" . "\n");
                        $message->channel->sendMessage($messageString . "\n" . "\n" . "\n" . "\n");
                        $profileImageUrl = $data['pageProps']['data']['profile_image_url'];
    
                        $message->channel->sendMessage('Summoner icon: ' . "\n");
                        $message->channel->sendMessage($profileImageUrl);
                    }

                    $maxAttempts = 5;
                    $attempts = 0;
                    $success = false;
                    
                    while ($attempts < $maxAttempts && !$success) {
                        try {
                            $ingameData = $this->opGGService->ingameData($formattedName);

                            $ingameData = $ingameData['data'];

                            $reply = "Match details:\n\n";
                            $reply .= "**Match ID:** {$ingameData['game_id']}\n";
                            $reply .= "**Game Type:** {$ingameData['queue_info']['queue_translate']}\n";
                            $reply .= "**Map:** {$ingameData['game_map']}\n\n";
                            $reply .= "**Participants:**\n";
                    
                            foreach ($ingameData['participants'] as $participant) {
                                $reply .= "----------------------\n";
                                $reply .= "**Name:** {$participant['summoner']['name']}\n";
                                $reply .= "**Team side:** {$participant['team_key']}\n";
                                $reply .= "**Position:** {$participant['position']}\n";
                                $reply .= "**Level:** {$participant['summoner']['level']}\n";
                            }
                    
                            $message->channel->sendMessage($reply);
                            $success = true;
                        } catch (\Exception $e) {
                            $attempts++;
                            sleep(1);
                            Log::error($e->getMessage());
                        }
                    }
                    
                    if (!$success) {
                        $message->channel->sendMessage('Not in game.');
                    }
                }

                if (strtolower(substr($message->content, 0, 7)) == 'artist:') {
                    $spotifyService = app()->make('App\Services\SpotifyService');
                    $spotifyService->search($message->content, 'artist');

                    $message->channel->sendMessage('Searching...');

                    $maxAttempts = 5;

                    $attempts = 0;

                    $success = false;

                    while ($attempts < $maxAttempts && !$success) {
                        try {
                            $name = substr($message->content, 7);
                            $name = trim($name);
                            $name = strtolower($name);
                            $data = $spotifyService->search($name, 'artist');

                            if(!$data)
                            {
                                $message->channel->sendMessage('Error searching, try again.');
                                return;
                            }

                            $data = $data['artists']['items'];
                            
                            foreach($data as $artist)
                            {
                                if(strtolower($artist['name']) == $name)
                                {
                                    $data = $artist;
                                    break;
                                }
                            }

                            if(!isset($data['images'][0]['url']))
                            {
                                $message->channel->sendMessage('Error searching, try again.');
                                return;
                            }

                            $message->channel->sendMessage($data['images'][0]['url']);

                            $reply = "Artist details:\n\n";
                            $reply .= "**Name:** {$data['name']}\n";
                            $reply .= "**Genres:** " . implode(', ', $data['genres']) . "\n";
                            $reply .= "**Popularity:** {$data['popularity']}\n";
                            $reply .= "**Followers:** {$data['followers']['total']}\n";
                            $reply .= "**Link:** {$data['external_urls']['spotify']}\n";

                            $message->channel->sendMessage($reply);
                            $success = true;
                        } catch (\Exception $e) {
                            $attempts++;
                            sleep(1);
                            Log::error($e->getMessage());
                        }
                    }
                }
            });
    
            $discord->on(Event::TYPING_START, function (TypingStart $typing, Discord $discord) {
                if ($typing->user->bot) {
                    return;
                }
    
                $typing->channel->sendMessage($typing->user->username . ' está digitando... corram.');
            });
    
            $discord->on(Event::MESSAGE_DELETE, function (object $message, Discord $discord) {
                if ($message instanceof Message) {
                    $message->channel->sendMessage('Mensagem de ' . $message->author->username . ' deletada: ' . PHP_EOL . '"' . $message->content . '"');
                }
            });
    
            $discord->run();
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    function formatTierInfo($tierInfo)
    {
        if ($tierInfo['tier'] === null) {
            return 'unranked';
        }

        return "{$tierInfo['tier']} {$tierInfo['division']} - LP: {$tierInfo['lp']}";
    }
}
