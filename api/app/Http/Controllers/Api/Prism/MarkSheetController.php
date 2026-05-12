<?php

namespace App\Http\Controllers\Api\Prism;

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
        abort_unless($request->user()->can('manage_marks'), 403);

        $data = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $sheet = MarkSheet::create([
            ...$data,
            'submitted_by_user_id' => $request->user()->id,
        ]);

        return response()->json($sheet->load(['assessment', 'subject', 'schoolClass', 'section']), 201);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('view_marks') || $request->user()->can('manage_marks'), 403);

        $q = MarkSheet::query()->with([
            'assessment:id,type,name,number',
            'subject:id,name',
            'schoolClass:id,name',
            'section:id,name',
        ]);

        if ($request->user()->hasRole('parent')) {
            $classIds = $request->user()->children()->pluck('school_class_id')->unique();
            $sectionIds = $request->user()->children()->pluck('section_id')->unique();
            $q->whereIn('school_class_id', $classIds)->whereIn('section_id', $sectionIds);
        }

        return response()->json($q->orderByDesc('updated_at')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function show(Request $request, MarkSheet $markSheet)
    {
        abort_unless($request->user()->can('view_marks') || $request->user()->can('manage_marks'), 403);

        if ($request->user()->hasRole('parent')) {
            abort_unless(
                $request->user()->children()
                    ->where('school_class_id', $markSheet->school_class_id)
                    ->where('section_id', $markSheet->section_id)
                    ->exists(),
                403
            );
        }

        $markSheet->load([
            'assessment',
            'subject',
            'entries' => fn ($e) => $e->when(
                $request->user()->hasRole('parent'),
                fn ($qq) => $qq->whereIn(
                    'student_id',
                    $request->user()->children()->pluck('students.id')
                )
            )->with('student:id,first_name,last_name'),
        ]);

        return response()->json($markSheet);
    }

    public function upsertEntries(Request $request, MarkSheet $markSheet)
    {
        abort_unless($request->user()->can('manage_marks'), 403);

        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.student_id' => 'required|exists:students,id',
            'entries.*.marks_obtained' => 'nullable|numeric',
            'entries.*.max_marks' => 'nullable|numeric',
            'entries.*.grade' => 'nullable|string|max:8',
        ]);

        DB::transaction(function () use ($markSheet, $data) {
            foreach ($data['entries'] as $row) {
                MarkEntry::updateOrCreate(
                    [
                        'mark_sheet_id' => $markSheet->id,
                        'student_id' => $row['student_id'],
                    ],
                    [
                        'marks_obtained' => $row['marks_obtained'] ?? null,
                        'max_marks' => $row['max_marks'] ?? null,
                        'grade' => $row['grade'] ?? null,
                    ]
                );
            }
        });

        return response()->json($markSheet->load('entries.student:id,first_name,last_name'));
    }

    public function requestParentNotification(Request $request, MarkSheet $markSheet, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_marks'), 403);

        $markSheet->load(['subject', 'assessment']);

        $studentIds = Student::query()
            ->where('school_class_id', $markSheet->school_class_id)
            ->where('section_id', $markSheet->section_id)
            ->pluck('id')
            ->all();

        $dispatch = $dispatchService->create(
            NotificationFeatureKeys::MARKS_PUBLISHED,
            'MarkSheet',
            $markSheet->id,
            'class',
            ['student_ids' => $studentIds],
            [
                'title' => 'Marks published',
                'body' => 'New marks are available for '.$markSheet->subject->name.' ('.$markSheet->assessment->name.').',
                'data' => [
                    'type' => 'marks_published',
                    'mark_sheet_id' => $markSheet->id,
                ],
            ],
            $markSheet->school_class_id,
            $markSheet->section_id,
            $request->user()->id,
        );

        return response()->json(['dispatch' => $dispatch], 201);
    }
}
