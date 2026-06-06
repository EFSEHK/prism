<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\MarkEntry;
use App\Models\MarkSheet;
use App\Models\Student;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarkSheetController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->can('enter_marks'), 403);

        $data = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'study_group_id' => 'required|exists:study_groups,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $sheet = MarkSheet::firstOrCreate(
            [
                'assessment_id' => $data['assessment_id'],
                'study_group_id' => $data['study_group_id'],
                'subject_id' => $data['subject_id'],
            ],
            [
                'status' => 'draft',
                'submitted_by_user_id' => $request->user()->id,
            ]
        );

        return response()->json($sheet->load(['assessment', 'subject', 'studyGroup']), 201);
    }

    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('enter_marks') || $request->user()->can('view_marks_reports') || $request->user()->can('view_own_marks'),
            403
        );

        $q = MarkSheet::query()->with(['assessment:id,type,name', 'subject:id,name', 'studyGroup:id,name']);

        if ($request->user()->can('view_own_marks') && ! $request->user()->can('view_marks_reports')) {
            $groupIds = $this->learnerStudyGroupIds($request);
            $q->where('status', 'verified')->whereIn('study_group_id', $groupIds);
        }

        if ($request->filled('study_group_id')) {
            $q->where('study_group_id', $request->query('study_group_id'));
        }

        return response()->json($q->orderByDesc('updated_at')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function show(Request $request, MarkSheet $markSheet)
    {
        abort_unless(
            $request->user()->can('enter_marks') || $request->user()->can('view_marks_reports') || $request->user()->can('view_own_marks'),
            403
        );

        if ($request->user()->can('view_own_marks') && ! $request->user()->can('view_marks_reports')) {
            abort_unless($markSheet->isVerified(), 403);
            abort_unless(
                $this->learnerStudyGroupIds($request)->contains($markSheet->study_group_id),
                403
            );
        }

        $markSheet->load(['entries.student:id,first_name,last_name', 'assessment', 'subject', 'studyGroup']);

        return response()->json($markSheet);
    }

    public function upsertEntries(Request $request, MarkSheet $markSheet)
    {
        abort_unless($request->user()->can('enter_marks'), 403);

        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.student_id' => 'required|exists:students,id',
            'entries.*.marks_obtained' => 'nullable|numeric|min:0',
            'entries.*.max_marks' => 'nullable|numeric|min:0',
            'entries.*.grade' => 'nullable|string|max:8',
        ]);

        DB::transaction(function () use ($data, $markSheet, $request) {
            foreach ($data['entries'] as $row) {
                $isPass = null;
                if (isset($row['marks_obtained'], $row['max_marks']) && $row['max_marks'] > 0) {
                    $isPass = ($row['marks_obtained'] / $row['max_marks']) >= 0.4;
                }
                MarkEntry::updateOrCreate(
                    ['mark_sheet_id' => $markSheet->id, 'student_id' => $row['student_id']],
                    [
                        'marks_obtained' => $row['marks_obtained'] ?? null,
                        'max_marks' => $row['max_marks'] ?? null,
                        'grade' => $row['grade'] ?? null,
                        'is_pass' => $isPass,
                    ]
                );
            }
            $markSheet->update(['status' => 'submitted', 'submitted_by_user_id' => $request->user()->id]);
        });

        return response()->json($markSheet->fresh(['entries.student:id,first_name,last_name']));
    }

    public function verify(Request $request, MarkSheet $markSheet, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('verify_marks'), 403);
        abort_unless($markSheet->status === 'submitted', 422);

        $markSheet->update([
            'status' => 'verified',
            'verified_by_user_id' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $failedStudentIds = $markSheet->entries()->where('is_pass', false)->pluck('student_id')->all();
        if ($failedStudentIds !== []) {
            $dispatchService->create(
                NotificationFeatureKeys::MARKS_SUBJECT_FAILED,
                'MarkSheet',
                $markSheet->id,
                'study_group',
                ['student_ids' => $failedStudentIds],
                [
                    'title' => 'Marks notice',
                    'body' => 'A subject result requires your attention.',
                    'data' => ['mark_sheet_id' => $markSheet->id],
                ],
                studyGroupId: (int) $markSheet->study_group_id,
                createdByUserId: $request->user()->id,
            );
        }

        $dispatchService->create(
            NotificationFeatureKeys::MARKS_ASSESSMENT_SUMMARY,
            'MarkSheet',
            $markSheet->id,
            'study_group',
            ['student_ids' => Student::where('study_group_id', $markSheet->study_group_id)->pluck('id')->all()],
            [
                'title' => 'Assessment results published',
                'body' => 'Marks for an assessment are now available.',
                'data' => ['mark_sheet_id' => $markSheet->id],
            ],
            studyGroupId: (int) $markSheet->study_group_id,
            createdByUserId: $request->user()->id,
        );

        return response()->json($markSheet->fresh());
    }

    public function requestParentNotification(Request $request, MarkSheet $markSheet, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('enter_marks'), 403);

        $dispatchService->create(
            NotificationFeatureKeys::MARKS_PUBLISHED,
            'MarkSheet',
            $markSheet->id,
            'study_group',
            ['student_ids' => Student::where('study_group_id', $markSheet->study_group_id)->pluck('id')->all()],
            [
                'title' => 'Marks published',
                'body' => 'New marks are available for review.',
                'data' => ['mark_sheet_id' => $markSheet->id],
            ],
            studyGroupId: (int) $markSheet->study_group_id,
            createdByUserId: $request->user()->id,
        );

        return response()->json(['message' => 'Notification dispatch requested.']);
    }

    private function learnerStudyGroupIds(Request $request)
    {
        if ($request->user()->hasRole('parent')) {
            return $request->user()->children()->pluck('study_group_id')->unique();
        }

        $sg = $request->user()->studentProfile?->study_group_id;

        return collect($sg ? [$sg] : []);
    }
}
