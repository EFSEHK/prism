<?php

namespace App\Jobs;

use App\Models\NotificationDispatchRequest;
use App\Models\Student;
use App\Models\UserNotification;
use App\Services\Notifications\FcmNotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessApprovedNotificationDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $notificationDispatchRequestId,
    ) {}

    public function handle(FcmNotificationSender $fcm): void
    {
        $dispatch = NotificationDispatchRequest::query()
            ->with('feature')
            ->find($this->notificationDispatchRequestId);

        if (! $dispatch || $dispatch->status !== 'approved') {
            return;
        }

        if ($dispatch->sent_at) {
            return;
        }

        $payload = $dispatch->payload_json ?? [];
        $title = (string) ($payload['title'] ?? 'School notification');
        $body = (string) ($payload['body'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        $parentUserIds = $this->resolveParentUserIds($dispatch, $payload);

        if ($parentUserIds === []) {
            $dispatch->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return;
        }

        DB::transaction(function () use ($dispatch, $parentUserIds, $title, $body, $data, $fcm) {
            $rows = [];
            $now = now();
            foreach ($parentUserIds as $uid) {
                $rows[] = [
                    'user_id' => $uid,
                    'notification_dispatch_request_id' => $dispatch->id,
                    'title' => $title,
                    'body' => $body,
                    'data_json' => json_encode($data),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                UserNotification::insert($chunk);
            }

            $fcm->sendToUsers($parentUserIds, $title, $body, $data);

            $dispatch->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        });
    }

    /**
     * @return list<int>
     */
    private function resolveParentUserIds(NotificationDispatchRequest $dispatch, array $payload): array
    {
        if ($dispatch->scope_type === 'school') {
            return DB::table('parent_student')->distinct()->pluck('parent_user_id')->all();
        }

        $ids = [];

        if (! empty($payload['parent_user_ids']) && is_array($payload['parent_user_ids'])) {
            return array_map('intval', $payload['parent_user_ids']);
        }

        $studentIds = $payload['student_ids'] ?? ($dispatch->scope_ids['student_ids'] ?? null);
        if (is_array($studentIds) && $studentIds !== []) {
            return DB::table('parent_student')
                ->whereIn('student_id', $studentIds)
                ->distinct()
                ->pluck('parent_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($dispatch->scope_type === 'study_group' && $dispatch->study_group_id) {
            $sids = Student::query()->where('study_group_id', $dispatch->study_group_id)->pluck('id');
            if ($sids->isEmpty()) {
                return [];
            }

            return DB::table('parent_student')
                ->whereIn('student_id', $sids)
                ->distinct()
                ->pluck('parent_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($dispatch->scope_type === 'class' && $dispatch->school_class_id) {
            $sids = Student::query()
                ->whereHas('studyGroup', fn ($sq) => $sq->where('school_class_id', $dispatch->school_class_id))
                ->pluck('id');
            if ($sids->isEmpty()) {
                return [];
            }

            return DB::table('parent_student')
                ->whereIn('student_id', $sids)
                ->distinct()
                ->pluck('parent_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
