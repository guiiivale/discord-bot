<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Illuminate\Support\Env;

class MessagesController extends Controller
{
    public function test()
    {
        $discord = new Discord([
            'token' => Env::get('BOT_TOKEN'),
            'intents' => Intents::getDefaultIntents()
        ]);
        
        $discord->on('ready', function (Discord $discord) {
            echo "Bot is ready!", PHP_EOL;
        
            $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
                echo "{$message->author->username}: {$message->content}", PHP_EOL;
            });
        });
        
        $discord->run();
        
    }
}
