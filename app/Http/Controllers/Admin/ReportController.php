<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * G18 通報管理
 */
class ReportController extends Controller
{
    /**
     * ① 状態の選択肢（初期表示は未対応）
     */
    public const STATUSES = [
        'all' => 'すべて',
        'pending' => '未対応',
        'resolved' => '対応済み',
    ];

    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status'), self::STATUSES) ? (string) $request->query('status') : 'pending';
        $reason = ReportReason::tryFrom((int) $request->query('reason'));

        $reports = Report::query()
            ->with(['reporter', 'question', 'answer.question'])
            ->when($status === 'pending', fn ($query) => $query->where('status', ReportStatus::Pending->value))
            ->when($status === 'resolved', fn ($query) => $query->where('status', ReportStatus::Resolved->value))
            ->when($reason, fn ($query) => $query->where('reason', $reason->value))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'statuses' => self::STATUSES,
            'reasons' => ReportReason::cases(),
            'status' => $status,
            'reason' => $reason,
        ]);
    }
}
