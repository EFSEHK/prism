<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\FeeVoucher;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class FeeVoucherController extends Controller
{
    public function index(Request $request)
    {
        $q = FeeVoucher::query()->with(['student:id,first_name,last_name,admission_no']);

        if ($request->user()->hasRole('parent')) {
            $ids = $request->user()->children()->pluck('students.id');
            $q->whereIn('student_id', $ids);
        } elseif (! $request->user()->can('manage_fee_vouchers') && ! $request->user()->can('view_fee_accounting')) {
            abort(403);
        }

        if ($request->filled('submission_status')) {
            $q->where('submission_status', $request->query('submission_status'));
        }

        return response()->json($q->orderByDesc('updated_at')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function store(Request $request, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_fee_vouchers'), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'title' => 'required|string|max:255',
            'file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'file_path' => 'nullable|string|max:2048',
        ]);

        $filePath = $data['file_path'] ?? null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('fee-vouchers', 'public');
        }

        $v = FeeVoucher::create([
            'student_id' => $data['student_id'],
            'title' => $data['title'],
            'file_path' => $filePath,
            'submission_status' => 'pending',
            'updated_by_user_id' => $request->user()->id,
        ]);

        $dispatchService->create(
            NotificationFeatureKeys::FEE_VOUCHER_AVAILABLE,
            'FeeVoucher',
            $v->id,
            'student',
            ['student_ids' => [$v->student_id]],
            [
                'title' => 'Fee voucher',
                'body' => $v->title,
                'data' => ['type' => 'fee_voucher', 'fee_voucher_id' => $v->id],
            ],
            createdByUserId: $request->user()->id,
        );

        return response()->json($v->load('student'), 201);
    }

    public function updateStatus(Request $request, FeeVoucher $feeVoucher, NotificationDispatchService $dispatchService)
    {
        $user = $request->user();
        $isParent = $user->hasRole('parent');
        $isStaff = $user->can('manage_fee_vouchers') || $user->can('view_fee_accounting');

        abort_unless($isParent || $isStaff, 403);

        $data = $request->validate([
            'submission_status' => 'required|in:pending,submitted,verified',
        ]);

        if ($isParent && ! $isStaff) {
            abort_unless(
                $user->children()->where('students.id', $feeVoucher->student_id)->exists(),
                403
            );
            abort_unless(
                in_array($data['submission_status'], ['submitted'], true),
                422,
                'Parents may only mark vouchers as submitted.'
            );
            abort_unless(
                in_array($feeVoucher->submission_status, ['pending', 'submitted'], true),
                422,
                'This voucher can no longer be updated.'
            );
        }

        if ($isStaff && $data['submission_status'] === 'verified') {
            abort_unless($user->can('view_fee_accounting') || $user->can('manage_fee_vouchers'), 403);
        }

        $feeVoucher->update([
            'submission_status' => $data['submission_status'],
            'updated_by_user_id' => $user->id,
        ]);

        $dispatchService->create(
            NotificationFeatureKeys::FEE_VOUCHER_STATUS,
            'FeeVoucher',
            $feeVoucher->id,
            'student',
            ['student_ids' => [$feeVoucher->student_id]],
            [
                'title' => 'Fee voucher update',
                'body' => 'Status: '.$feeVoucher->submission_status,
                'data' => ['type' => 'fee_status', 'fee_voucher_id' => $feeVoucher->id],
            ],
            createdByUserId: $user->id,
        );

        return response()->json($feeVoucher->fresh(['student:id,first_name,last_name,admission_no']));
    }
}
