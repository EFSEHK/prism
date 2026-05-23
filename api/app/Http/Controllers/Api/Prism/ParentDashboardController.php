<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\DatesheetEntry;
use App\Models\FeeVoucher;
use App\Models\FeedPost;
use App\Models\HomeworkPost;
use App\Models\LeaveRequest;
use App\Models\MarkSheet;
use App\Models\OnlineClassLink;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class ParentDashboardController extends Controller
{
    /**
     * Single aggregate response for parent home (reduces parallel API calls).
     */
    public function show(Request $request)
    {
        abort_unless($request->user()->can('view_parent_dashboard'), 403);

        $studentIds = $request->user()->children()->pluck('students.id');
        if ($studentIds->isEmpty()) {
            return response()->json($this->emptyPayload());
        }

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->with(['schoolClass:id,name', 'section:id,name,school_class_id'])
            ->get(['id', 'first_name', 'last_name', 'school_class_id', 'section_id', 'admission_no']);

        $classIds = $students->pluck('school_class_id')->unique()->filter();
        $sectionIds = $students->pluck('section_id')->unique()->filter();

        $unread = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        $include = array_filter(explode(',', (string) $request->query(
            'include',
            'homework,timetable,marks,feed,fees,online_classes,leave,datesheet,notifications'
        )));

        $payload = [
            'children' => $students,
            'unread_notifications' => $unread,
        ];

        if (in_array('homework', $include, true)) {
            $payload['homework'] = HomeworkPost::query()
                ->whereIn('school_class_id', $classIds)
                ->whereIn('section_id', $sectionIds)
                ->with(['subject:id,name', 'schoolClass:id,name', 'section:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        if (in_array('timetable', $include, true)) {
            $dow = now()->dayOfWeek;
            $payload['timetable_today'] = TimetableSlot::query()
                ->whereIn('school_class_id', $classIds)
                ->whereIn('section_id', $sectionIds)
                ->where('day_of_week', $dow)
                ->with('subject:id,name')
                ->orderBy('start_time')
                ->get();
        }

        if (in_array('marks', $include, true) && $request->user()->can('view_marks')) {
            $payload['mark_sheets'] = MarkSheet::query()
                ->whereIn('school_class_id', $classIds)
                ->whereIn('section_id', $sectionIds)
                ->with([
                    'assessment:id,type,name,number',
                    'subject:id,name',
                    'schoolClass:id,name',
                ])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        if (in_array('feed', $include, true)) {
            $payload['feed'] = FeedPost::query()
                ->whereNotNull('published_at')
                ->where(function ($qq) use ($studentIds, $classIds) {
                    $qq->where('scope', 'school')
                        ->orWhere(function ($q2) use ($classIds) {
                            $q2->where('scope', 'class')->whereIn('scope_school_class_id', $classIds);
                        })
                        ->orWhere(function ($q3) use ($studentIds) {
                            $q3->where('scope', 'student')->whereIn('scope_student_id', $studentIds);
                        });
                })
                ->orderByDesc('published_at')
                ->limit(10)
                ->get(['id', 'type', 'title', 'body', 'scope', 'published_at']);
        }

        if (in_array('fees', $include, true)) {
            $payload['fee_vouchers'] = FeeVoucher::query()
                ->whereIn('student_id', $studentIds)
                ->with('student:id,first_name,last_name')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        if (in_array('online_classes', $include, true)) {
            $payload['online_classes'] = OnlineClassLink::query()
                ->whereIn('school_class_id', $classIds)
                ->where(function ($qq) use ($sectionIds) {
                    $qq->whereNull('section_id')->orWhereIn('section_id', $sectionIds);
                })
                ->with(['subject:id,name', 'schoolClass:id,name'])
                ->orderBy('label')
                ->limit(20)
                ->get();
        }

        if (in_array('leave', $include, true)) {
            $payload['leave_requests'] = LeaveRequest::query()
                ->where('parent_user_id', $request->user()->id)
                ->with('student:id,first_name,last_name')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        if (in_array('datesheet', $include, true)) {
            $payload['datesheet'] = DatesheetEntry::query()
                ->where(function ($qq) use ($classIds) {
                    $qq->whereNull('school_class_id')
                        ->orWhereIn('school_class_id', $classIds);
                })
                ->whereDate('exam_date', '>=', now()->toDateString())
                ->with(['subject:id,name', 'schoolClass:id,name'])
                ->orderBy('exam_date')
                ->limit(15)
                ->get();
        }

        if (in_array('notifications', $include, true)) {
            $payload['recent_notifications'] = UserNotification::query()
                ->where('user_id', $request->user()->id)
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
            'unread_notifications' => 0,
            'homework' => [],
            'timetable_today' => [],
            'mark_sheets' => [],
            'feed' => [],
            'fee_vouchers' => [],
            'online_classes' => [],
            'leave_requests' => [],
            'datesheet' => [],
            'recent_notifications' => [],
        ];
    }
}
