<?php

namespace App\Http\Controllers\Api\Prism;

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
            $ids = $request->user()->children()->pluck('id');
            $q->whereIn('student_id', $ids);
        }

        if ($request->user()->can('view_fee_accounting') && $request->filled('submission_status')) {
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
            'file_path' => 'nullable|string|max:2048',
        ]);

        $v = FeeVoucher::create($data + ['updated_by_user_id' => $request->user()->id]);

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
            null,
            null,
            $request->user()->id,
        );

        return response()->json($v->load('student'), 201);
    }

    public function updateStatus(Request $request, FeeVoucher $feeVoucher, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_fee_vouchers') || $request->user()->can('view_fee_accounting'), 403);

        $data = $request->validate([
            'submission_status' => 'required|in:pending,submitted,verified',
        ]);

        $feeVoucher->update([
            'submission_status' => $data['submission_status'],
            'updated_by_user_id' => $request->user()->id,
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
            null,
            null,
            $request->user()->id,
        );

        return response()->json($feeVoucher);
    }
}
