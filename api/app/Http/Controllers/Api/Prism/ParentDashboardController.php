<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\HomeworkPost;
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

        $studentIds = $request->user()->children()->pluck('id');
        if ($studentIds->isEmpty()) {
            return response()->json([
                'children' => [],
                'unread_notifications' => 0,
                'homework' => [],
                'timetable_today' => [],
            ]);
        }

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->with(['schoolClass:id,name', 'section:id,name,school_class_id'])
            ->get(['id', 'first_name', 'last_name', 'school_class_id', 'section_id', 'admission_no']);

        $unread = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        $include = array_filter(explode(',', (string) $request->query('include', 'homework,timetable')));

        $homework = [];
        if (in_array('homework', $include, true)) {
            $homework = HomeworkPost::query()
                ->whereIn('school_class_id', $students->pluck('school_class_id')->unique())
                ->whereIn('section_id', $students->pluck('section_id')->unique())
                ->with(['subject:id,name', 'schoolClass:id,name', 'section:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        $timetableToday = [];
        if (in_array('timetable', $include, true)) {
            $dow = now()->dayOfWeek;
            $timetableToday = TimetableSlot::query()
                ->whereIn('school_class_id', $students->pluck('school_class_id')->unique())
                ->whereIn('section_id', $students->pluck('section_id')->unique())
                ->where('day_of_week', $dow)
                ->with('subject:id,name')
                ->orderBy('start_time')
                ->get();
        }

        return response()->json([
            'children' => $students,
            'unread_notifications' => $unread,
            'homework' => $homework,
            'timetable_today' => $timetableToday,
        ]);
    }
}
