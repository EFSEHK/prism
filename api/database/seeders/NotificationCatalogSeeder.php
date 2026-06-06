<?php

namespace Database\Seeders;

use App\Models\NotificationFeature;
use App\Support\NotificationFeatureKeys;
use Illuminate\Database\Seeder;

class NotificationCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [NotificationFeatureKeys::ATTENDANCE_ABSENT, 'attendance', 'Absent alert to parents'],
            [NotificationFeatureKeys::MARKS_PUBLISHED, 'marks', 'Marks published to parents'],
            [NotificationFeatureKeys::MARKS_SUBJECT_FAILED, 'marks', 'Subject failed alert to parents'],
            [NotificationFeatureKeys::MARKS_ASSESSMENT_SUMMARY, 'marks', 'Assessment summary to parents'],
            [NotificationFeatureKeys::TIMETABLE_DATESHEET, 'timetable', 'Datesheet published'],
            [NotificationFeatureKeys::HOMEWORK_NEW, 'homework', 'New homework posted'],
            [NotificationFeatureKeys::ONLINE_CLASS_REMINDER, 'online_class', 'Online class reminder'],
            [NotificationFeatureKeys::ONLINE_CLASS_APPROVED, 'online_class', 'Online class approved'],
            [NotificationFeatureKeys::FEE_VOUCHER_AVAILABLE, 'fee', 'Fee voucher available'],
            [NotificationFeatureKeys::FEE_VOUCHER_STATUS, 'fee', 'Fee voucher status changed'],
            [NotificationFeatureKeys::EVENTS_BROADCAST, 'events', 'Event / announcement broadcast'],
            [NotificationFeatureKeys::LEAVE_DECISION_PARENT, 'leave', 'Leave decision to parent'],
        ];

        foreach ($features as [$key, $module, $name]) {
            NotificationFeature::updateOrCreate(
                ['feature_key' => $key],
                ['module_code' => $module, 'name' => $name, 'is_active' => true]
            );
        }
    }
}
