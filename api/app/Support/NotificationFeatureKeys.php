<?php

namespace App\Support;

final class NotificationFeatureKeys
{
    public const ATTENDANCE_ABSENT = 'attendance.absent_parent_alert';

    public const MARKS_PUBLISHED = 'marks.published';

    public const MARKS_SUBJECT_FAILED = 'marks.subject_failed';

    public const MARKS_ASSESSMENT_SUMMARY = 'marks.assessment_summary';

    public const TIMETABLE_DATESHEET = 'timetable.datesheet_published';

    public const HOMEWORK_NEW = 'homework.new_post';

    public const ONLINE_CLASS_REMINDER = 'online_class.reminder';

    public const ONLINE_CLASS_APPROVED = 'online_class.approved';

    public const FEE_VOUCHER_AVAILABLE = 'fee.voucher_available';

    public const FEE_VOUCHER_STATUS = 'fee.voucher_status_changed';

    public const EVENTS_BROADCAST = 'events.broadcast';

    public const LEAVE_DECISION_PARENT = 'leave.decision_parent_notify';
}
