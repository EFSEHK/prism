<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\DatesheetEntry;
use App\Models\FeeVoucher;
use App\Models\HomeworkPost;
use App\Models\LeaveRequest;
use App\Models\MarkSheet;
use App\Models\OnlineClassLink;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\UserBroadcast;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class LearnerDashboardController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->can('view_parent_dashboard') || $user->can('view_student_dashboard'),
            403
        );

        $studentIds = $user->hasRole('parent')
            ? $user->children()->pluck('students.id')
            : collect([$user->studentProfile?->id])->filter();

        if ($studentIds->isEmpty()) {
            return response()->json($this->emptyPayload());
        }

        $allChildren = Student::query()
            ->whereIn('id', $studentIds)
            ->with(['studyGroup:id,name', 'section:id,name,school_class_id', 'section.schoolClass:id,name'])
            ->get(['id', 'first_name', 'last_name', 'study_group_id', 'section_id', 'admission_no']);

        $focusStudentId = $request->query('student_id');
        $scopedStudents = $allChildren;
        if ($focusStudentId !== null && $focusStudentId !== '') {
            abort_unless($studentIds->contains((int) $focusStudentId), 403);
            $scopedStudents = $allChildren->where('id', (int) $focusStudentId)->values();
        }

        $studyGroupIds = $scopedStudents->pluck('study_group_id')->unique()->filter();
        $sectionIds = $scopedStudents->pluck('section_id')->unique()->filter();
        $focusStudentIds = $scopedStudents->pluck('id');

        $unread = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $include = array_filter(explode(',', (string) $request->query(
            'include',
            'homework,timetable,marks,broadcasts,fees,online_classes,leave,datesheet,notifications'
        )));

        $payload = [
            'children' => $allChildren,
            'unread_notifications' => $unread,
            'school_announcements' => UserBroadcast::query()
                ->whereNotNull('published_at')
                ->where('audience_type', 'general')
                ->orderByDesc('published_at')
                ->limit(10)
                ->get(['id', 'title', 'body', 'audience_type', 'published_at']),
        ];

        if (in_array('homework', $include, true)) {
            $payload['homework'] = HomeworkPost::query()
                ->where('status', 'approved')
                ->where(function ($qq) use ($studyGroupIds, $sectionIds) {
                    if ($studyGroupIds->isNotEmpty()) {
                        $qq->whereIn('study_group_id', $studyGroupIds);
                    }
                    if ($sectionIds->isNotEmpty()) {
                        $qq->orWhereIn('section_id', $sectionIds);
                    }
                    if ($studyGroupIds->isEmpty() && $sectionIds->isEmpty()) {
                        $qq->whereRaw('1 = 0');
                    }
                })
                ->with(['subject:id,name', 'studyGroup:id,name', 'section:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        if (in_array('timetable', $include, true)) {
            $dow = now()->dayOfWeek;
            $payload['timetable_today'] = TimetableSlot::query()
                ->whereIn('study_group_id', $studyGroupIds)
                ->where('day_of_week', $dow)
                ->with('subject:id,name')
                ->orderBy('start_time')
                ->get();
        }

        if (in_array('datesheet', $include, true)) {
            $classIds = $scopedStudents
                ->map(fn ($s) => $s->section?->school_class_id)
                ->filter()
                ->unique()
                ->values();

            $payload['datesheet'] = DatesheetEntry::query()
                ->when(
                    $classIds->isNotEmpty(),
                    fn ($q) => $q->where(function ($qq) use ($classIds) {
                        $qq->whereIn('school_class_id', $classIds)->orWhereNull('school_class_id');
                    }),
                    fn ($q) => $q->whereNull('school_class_id')
                )
                ->where('exam_date', '>=', now()->toDateString())
                ->with(['schoolClass:id,name', 'subject:id,name'])
                ->orderBy('exam_date')
                ->limit(20)
                ->get();
        }

        if (in_array('marks', $include, true) && $user->can('view_own_marks')) {
            $payload['mark_sheets'] = MarkSheet::query()
                ->where('status', 'verified')
                ->whereIn('study_group_id', $studyGroupIds)
                ->with([
                    'assessment:id,type,name,number',
                    'subject:id,name',
                    'studyGroup:id,name',
                ])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        if (in_array('broadcasts', $include, true)) {
            $payload['broadcasts'] = UserBroadcast::query()
                ->whereNotNull('published_at')
                ->where(function ($qq) use ($focusStudentIds, $studyGroupIds) {
                    $qq->where('audience_type', 'general')
                        ->orWhereIn('study_group_id', $studyGroupIds)
                        ->orWhereIn('student_id', $focusStudentIds);
                })
                ->orderByDesc('published_at')
                ->limit(10)
                ->get(['id', 'title', 'body', 'audience_type', 'published_at']);
        }

        if (in_array('fees', $include, true)) {
            $payload['fee_vouchers'] = FeeVoucher::query()
                ->whereIn('student_id', $focusStudentIds)
                ->with('student:id,first_name,last_name')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        if (in_array('online_classes', $include, true)) {
            $payload['online_classes'] = OnlineClassLink::query()
                ->where('status', 'approved')
                ->whereIn('study_group_id', $studyGroupIds)
                ->with(['subject:id,name', 'studyGroup:id,name'])
                ->orderBy('scheduled_date')
                ->limit(20)
                ->get();
        }

        if (in_array('leave', $include, true) && $user->hasRole('parent')) {
            $payload['leave_requests'] = LeaveRequest::query()
                ->where('parent_user_id', $user->id)
                ->with('student:id,first_name,last_name')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        if (in_array('notifications', $include, true)) {
            $payload['recent_notifications'] = UserNotification::query()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'title', 'body', 'read_at', 'created_at']);
        }

        return response()->json($payload);
    }

    private function emptyPayload(): array
    {
        return [
            'children' => [],
            'school_announcements' => [],
            'unread_notifications' => 0,
            'homework' => [],
            'timetable_today' => [],
            'datesheet' => [],
            'mark_sheets' => [],
            'broadcasts' => [],
            'fee_vouchers' => [],
            'online_classes' => [],
            'leave_requests' => [],
            'recent_notifications' => [],
        ];
    }
}
