<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use App\Services\UserService;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Drivers\DriverManager;

class BotManController extends Controller
{
    protected $botman;

    public function __construct()
    {

        DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);

        $config = [
            'user_cache_time' => 720,

            'config' => [
                'conversation_cache_time' => 720,
            ],

            // Your driver-specific configuration
            "telegram" => [
                "token" => env('TELEGRAM_TOKEN'),
            ]
        ];

        // // Create BotMan instance
        $this->botman = BotManFactory::create($config, new LaravelCache());
    }
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {

        // Listens for Start command with referral link
        $this->botman->hears('/start ([0-9]+)', function (BotMan $bot, $referrer = null) {

            $userService = new UserService;

            if ($referrer) {
                $response = $userService->createAndReward($referrer, $bot);

                if (!$response) {
                    return $bot->reply('Access denied. You need a valid referral code to use this bot.');
                }

                if (isset($response['user_existed'])) {
                    $link =  $response['link'];
                    return $bot->reply(
                        "You are already a *prima warrior*. Keep sharing your referral link to gather more tokens. \n\nYour referral link is [$link]($link)",
                        ['parse_mode' => 'Markdown']
                    );
                } else {

                    $this->notifyReferrer($response['referrer_points'], $referrer);

                    $bot->reply("*Welcome aboard, Prima Warrior!* \n\nYou were referred by $referrer", ['parse_mode' => 'Markdown']);
                    $bot->typesAndWaits(2);
                    $bot->reply("Your referral link  is \n" . $response['link'], ['parse_mode' => 'Markdown']);
                }
            } else {
                $response = $userService->create($bot);

                $bot->reply('Welcome to PrimaAI bot!');
                $bot->reply("Your referral link  is $response");
            }
        });

        // Start command without referral link
        $this->botman->hears('start|/start|Start', function ($bot) {
            $userService = new UserService;
            $response = $userService->create($bot);

            $bot->reply('Welcome to PrimaAI bot!');
            $bot->reply("Your referral link  is $response");
        });


        // Command to get user points
        $this->botman->hears('/points|points', function (BotMan $bot) {
            $userService = new UserService();
            $tokens = $userService->getPoints($bot);
            $bot->reply("You have a total of *$tokens* PrimaAI tokens", ['parse_mode' => 'Markdown']);
        })->stopsConversation();

        // Command to see about the bot
        $this->botman->hears('/about|about', function (BotMan $bot) {
            $bot->reply("This is a Laravel 11 and BotMan 2 project by Ejimadu Prevail. \n\nIt is a cryto project management bot that handles the referral and reward system. \nUsers are rewarded in PrimaAI tokens", ['parse_mode' => 'Markdown']);
        })->stopsConversation();

        // This is a fallback for any command it does not understand
        $this->botman->fallback(function ($bot) {
            $bot->reply("Sorry, I am just a notification bot. Type 'start' or click on '/start to begin. See menu for other commands");
        });

        //Must be under all the commands to tell the bot to listen
        $this->botman->listen();
    }

    private function notifyReferrer($tokens, $referrer)
    {
        $this->botman->say('Congratulations! You have successfully referred a new user and earned a reward!', $referrer);
        $this->botman->say("You have a total of $tokens PrimaAI tokens", $referrer);
    }
}
