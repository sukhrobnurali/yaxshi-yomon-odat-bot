<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Telegram\Commands\HabitSelectionCommand;
use App\Telegram\Commands\StartCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $update = Telegram::getWebhookUpdate();
//            $chatId = $request['message']['chat']['id'];
            $chatId = $update->getMessage()->chat->id ?? null;
            $messageText = $update->getMessage()->getText();

            $cacheKey = "user_habit_step_{$chatId}";
            $habitTypeKey = "user_habit_type_{$chatId}";

            if ($messageText === '/start') {
                $keyboard = Keyboard::make()
                    ->row([Keyboard::button('Yaxshi Odatlar'), Keyboard::button('Yomon Odatlar')])
                    ->setResizeKeyboard(true);

                // Send a message with the keyboard
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Hush kelibsiz! Odatlarni tanlang:',
                    'reply_markup' => $keyboard
                ]);
            } elseif ($messageText === 'Yaxshi Odatlar') {
                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button("Yaxshi Odatlarni Ko'rish"),
                        Keyboard::button("Yaxshi Odat Qo'shish")
                    ])
                    ->row([
                        Keyboard::button("Ortga")
                    ]);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Yaxshi Odatlar:',
                    'reply_markup' => $keyboard
                ]);
            } elseif ($messageText === 'Yomon Odatlar') {
                $keyboard = Keyboard::make()
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->row([
                        Keyboard::button("Ko'rish 👁️"),
                        Keyboard::button("Qo'shish 🟢")
                    ])
                    ->row([
                        Keyboard::button("Ortga")
                    ]);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Yomon Odatlar:',
                    'reply_markup' => $keyboard
                ]);
            } elseif ($messageText === "Yaxshi Odat Qo'shish") {
                cache([$habitTypeKey => 'good'], now()->addMinutes(10));
                cache([$cacheKey => 'adding_habit'], now()->addMinutes(10));


                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Iltimos yaxshi odatning nomini yozing:',
                ]);
            } elseif ($messageText === "Qo'shish 🟢") {
                cache([$habitTypeKey => 'bad'], now()->addMinutes(10));
                cache([$cacheKey => 'adding_habit'], now()->addMinutes(10));

                // Ask for the name of the bad habit
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Iltimos yomon odatning nomini yozing:',
                ]);
            } elseif (cache($cacheKey) === 'adding_habit') {
                // Check if it's a good or bad habit
                $habitType = cache($habitTypeKey);
                $isBadHabit = $habitType === 'bad';

                // Save the habit to the database
                Habit::create([
                    'user_id' => $chatId,
                    'name' => $messageText,
                    'is_bad_habit' => $isBadHabit,
                ]);

                cache()->forget($cacheKey);
                cache()->forget($habitTypeKey);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Yangi odat qo'shildi!🎆",
                ]);

                $this->mainMenu($chatId);

            } elseif (cache("edit_habit_{$chatId}")) {
                $habitId = cache("edit_habit_{$chatId}");

                // Update the habit in the database
                $habit = Habit::find($habitId);
                if ($habit) {
                    $habit->name = $messageText;
                    $habit->save();

                    cache()->forget("edit_habit_{$chatId}");

                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Odat ozgartirildi: ' . $messageText,
                    ]);
                } else {
                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Kechirasiz, bu odatni topa olmadim.',
                    ]);
                }
            } elseif ($messageText === "Yaxshi Odatlarni Ko'rish") {
                // Fetch good habits from the database
                $goodHabits = Habit::where('user_id', $chatId)->where('is_bad_habit', false)->get();

                if ($goodHabits->isEmpty()) {
                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Siz hali yaxshi odatlarni qo'shmadingiz.",
                    ]);
                } else {
                    foreach ($goodHabits as $habit) {
                        $inlineKeyboard = [
                            [
                                ['text' => '✏️ Ozgartirish', 'callback_data' => 'edit_' . $habit->id],
                                ['text' => '🗑️ Ochirish', 'callback_data' => 'delete_' . $habit->id],
                            ]
                        ];

                        Telegram::sendMessage([
                            'chat_id' => $chatId,
                            'text' => '✅ ' . $habit->name,
                            'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard]),
                        ]);
                    }
                }
            } // Handling "See Bad Habits" button press
            elseif ($messageText === "Ko'rish 👁️") {
                // Fetch bad habits from the database
                $badHabits = Habit::where('user_id', $chatId)->where('is_bad_habit', true)->get();

                if ($badHabits->isEmpty()) {
                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Sizda hali yomon odatlar yoq.',
                    ]);
                } else {
                    foreach ($badHabits as $habit) {
                        $inlineKeyboard = [
                            [
                                ['text' => '✏️ Ozgartirish', 'callback_data' => 'edit_' . $habit->id],
                                ['text' => '🗑️ Ochirish', 'callback_data' => 'delete_' . $habit->id],
                            ]
                        ];

                        Telegram::sendMessage([
                            'chat_id' => $chatId,
                            'text' => '❌ ' . $habit->name,
                            'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard]),
                        ]);
                    }
                }
            } elseif ($messageText === 'Ortga') {
                $this->mainMenu($chatId);
            }


            if ($update->has('callback_query')) {
                $callbackQuery = $update->getCallbackQuery();
                $chatId = $callbackQuery->getMessage()->chat->id;
                $callbackData = $callbackQuery->getData();

                // Handle Edit Habit
                if (str_starts_with($callbackData, 'edit_')) {
                    $habitId = str_replace('edit_', '', $callbackData);

                    // You can now prompt the user to enter a new name for this habit.
                    // Example: Store this state in the cache and expect the next user message to be the new habit name.
                    cache(["edit_habit_{$chatId}" => $habitId], now()->addMinutes(10));

                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Iltimos ushbu odat uchun yangi nomni kiriting:',
                    ]);
                } // Handle Delete Habit
                elseif (str_starts_with($callbackData, 'delete_')) {
                    $habitId = str_replace('delete_', '', $callbackData);

                    // Delete the habit from the database
                    Habit::destroy($habitId);

                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Odat ochirildi',
                    ]);
                }
            }

        } catch (\Exception $exception) {
            report($exception);
            Log::error('exp', ['message' => $exception->getMessage()]);
            return response('error', 200);
        }
    }


    public function mainMenu($chatId): void
    {
        $keyboard = Keyboard::make()
            ->row([Keyboard::button('Yaxshi Odatlar'), Keyboard::button('Yomon Odatlar')])
            ->setResizeKeyboard(true);

        // Send a message with the main menu
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Asosiy',
            'reply_markup' => $keyboard
        ]);
    }

}
