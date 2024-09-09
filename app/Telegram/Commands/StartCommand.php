<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Start interacting with the bot';

    public function handle()
    {
        // Create keyboard with two buttons: "Good Habits" and "Bad Habits"
        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->row([
                Keyboard::button('Yaxshi Odatlar'),
                Keyboard::button('Yomon Odatlar')
            ]);

        $this->replyWithMessage([
            'text' => 'Hush kelibsiz! Odatlarni tanlang:',
//            'reply_markup' => $keyboard,
        ]);
    }
}
