<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class HabitSelectionCommand extends Command
{
    protected string $name = 'habit_selection';
    protected string $description = 'Select to see or add habits';

    public function handle()
    {
        $messageText = $this->getUpdate()->getMessage()->getText();

        if ($messageText === 'Yaxshi Odatlar') {
            $keyboard = Keyboard::make()
                ->row([
                    Keyboard::button("Yaxshi Odatlarni Ko'rish"),
                    Keyboard::button("Yaxshi Odat Qo'shish")
                ])
                ->row([
                    Keyboard::button('Ortga')
                ]);

            $this->replyWithMessage([
                'text' => 'Yaxshi Odatlarni tanladingiz.',
                'reply_markup' => $keyboard,
            ]);
        } elseif ($messageText === 'Yomon Odatlar') {
            $keyboard = Keyboard::make()
                ->row(
                    Keyboard::button('See Bad Habits'),
                    Keyboard::button('Add Bad Habit')
                )
                ->row(
                    Keyboard::button('Back')
                );

            $this->replyWithMessage([
                'text' => 'You selected Bad Habits. Choose an option:',
                'reply_markup' => $keyboard,
            ]);
        } elseif ($messageText === 'Back') {
            $keyboard = Keyboard::make()
                ->row(
                    Keyboard::button('Good Habits'),
                    Keyboard::button('Bad Habits')
                );

            $this->replyWithMessage([
                'text' => 'Back to the main menu. Choose an option:',
                'reply_markup' => $keyboard,
            ]);
        }
    }
}
