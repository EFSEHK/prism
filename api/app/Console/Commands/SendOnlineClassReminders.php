<?php

namespace App\Console\Commands;

use App\Models\OnlineClassLink;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Console\Command;

class SendOnlineClassReminders extends Command
{
    protected $signature = 'efsc:online-class-reminders';

    protected $description = 'Dispatch parent reminders for approved online classes starting soon';

    public function handle(NotificationDispatchService $dispatchService): int
    {
        $windowStart = now();
        $windowEnd = now()->addMinutes(60);

        $links = OnlineClassLink::query()
            ->where('status', 'approved')
            ->whereNull('reminder_sent_at')
            ->whereDate('scheduled_date', $windowStart->toDateString())
            ->get();

        $sent = 0;
        foreach ($links as $link) {
            $start = $link->scheduled_date->copy()->setTimeFromTimeString((string) $link->start_time);
            if ($start->lt($windowStart) || $start->gt($windowEnd)) {
                continue;
            }

            $dispatchService->create(
                NotificationFeatureKeys::ONLINE_CLASS_REMINDER,
                'OnlineClassLink',
                $link->id,
                'study_group',
                null,
                [
                    'title' => 'Online class reminder',
                    'body' => $link->label.' starts at '.$start->format('g:i A'),
                    'data' => ['online_class_link_id' => $link->id],
                ],
                studyGroupId: (int) $link->study_group_id,
            );

            $link->update(['reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} online class reminder(s).");

        return self::SUCCESS;
    }
}
