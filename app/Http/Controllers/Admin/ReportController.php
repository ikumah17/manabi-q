<?php

namespace App\Http\Controllers\Admin;

use App\Actions\HandleReport;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
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

    /**
     * ⑤ 対象を非表示
     */
    public function hideTarget(Report $report, HandleReport $handleReport): RedirectResponse
    {
        return $this->back($handleReport->hideTarget($report), '対象の投稿を非表示にし、通報を対応済みにしました');
    }

    /**
     * ⑥ 対応済みにする
     */
    public function resolve(Report $report, HandleReport $handleReport): RedirectResponse
    {
        return $this->back($handleReport->resolve($report), '通報を対応済みにしました');
    }

    /**
     * 操作した一覧（検索条件・ページ）へ戻る。管理画面の外の URL には戻らない
     */
    private function back(bool $done, string $message): RedirectResponse
    {
        $previous = url()->previous();
        $index = route('admin.reports.index');
        $url = $previous === $index || str_starts_with($previous, $index.'?') ? $previous : $index;

        return redirect()->to($url)->with($done ? 'success' : 'error', $done ? $message : '既に対応済みです');
    }
}
