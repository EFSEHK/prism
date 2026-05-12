<?php

namespace Database\Seeders;

use App\Models\NotificationApprovalPolicy;
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
            [NotificationFeatureKeys::TIMETABLE_DATESHEET, 'timetable', 'Datesheet published'],
            [NotificationFeatureKeys::HOMEWORK_NEW, 'homework', 'New homework posted'],
            [NotificationFeatureKeys::ONLINE_CLASS_REMINDER, 'online_class', 'Online class reminder'],
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

        $attendanceFeature = NotificationFeature::where('feature_key', NotificationFeatureKeys::ATTENDANCE_ABSENT)->first();
        NotificationApprovalPolicy::updateOrCreate(
            [
                'notification_feature_id' => $attendanceFeature->id,
                'sequence' => 1,
                'school_class_id' => null,
                'section_id' => null,
            ],
            ['approver_role_name' => 'class_incharge', 'requires_approval' => true, 'is_active' => true]
        );

        $marksFeature = NotificationFeature::where('feature_key', NotificationFeatureKeys::MARKS_PUBLISHED)->first();
        NotificationApprovalPolicy::updateOrCreate(
            [
                'notification_feature_id' => $marksFeature->id,
                'sequence' => 1,
                'school_class_id' => null,
                'section_id' => null,
            ],
            ['approver_role_name' => 'principal', 'requires_approval' => true, 'is_active' => true]
        );

        foreach ([
            NotificationFeatureKeys::HOMEWORK_NEW,
            NotificationFeatureKeys::FEE_VOUCHER_AVAILABLE,
            NotificationFeatureKeys::FEE_VOUCHER_STATUS,
            NotificationFeatureKeys::EVENTS_BROADCAST,
        ] as $key) {
            $f = NotificationFeature::where('feature_key', $key)->first();
            NotificationApprovalPolicy::updateOrCreate(
                [
                    'notification_feature_id' => $f->id,
                    'sequence' => 1,
                    'school_class_id' => null,
                    'section_id' => null,
                ],
                ['approver_role_name' => 'principal', 'requires_approval' => true, 'is_active' => true]
            );
        }

        $leaveF = NotificationFeature::where('feature_key', NotificationFeatureKeys::LEAVE_DECISION_PARENT)->first();
        NotificationApprovalPolicy::updateOrCreate(
            [
                'notification_feature_id' => $leaveF->id,
                'sequence' => 1,
                'school_class_id' => null,
                'section_id' => null,
            ],
            ['approver_role_name' => 'teacher', 'requires_approval' => false, 'is_active' => true]
        );

        $onlineF = NotificationFeature::where('feature_key', NotificationFeatureKeys::ONLINE_CLASS_REMINDER)->first();
        NotificationApprovalPolicy::updateOrCreate(
            [
                'notification_feature_id' => $onlineF->id,
                'sequence' => 1,
                'school_class_id' => null,
                'section_id' => null,
            ],
            ['approver_role_name' => 'principal', 'requires_approval' => true, 'is_active' => true]
        );
    }
}
