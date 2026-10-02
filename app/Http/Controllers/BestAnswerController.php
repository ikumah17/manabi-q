<?php

namespace App\Http\Controllers;

use App\Actions\ChooseBestAnswer;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * G9 ベストアンサー評価（G7 の上のボトムシートから送信）
 */
class BestAnswerController extends Controller
{
    public function store(Request $request, Question $question, Answer $answer, ChooseBestAnswer $chooseBestAnswer): RedirectResponse
    {
        Gate::authorize('view', $question);
        Gate::authorize('chooseBest', [$question, $answer]);

        // エラー時は G7 上部に表示し、対象のシートを開き直す（sheet = 回答ID）
        $validated = $request->validateWithBag('best', [
            'rating' => ['required', 'integer', 'between:1,5'],
        ], [
            'rating.required' => '評価を選択してください',
        ]);

        if (! $chooseBestAnswer->handle($question, $answer, (int) $validated['rating'])) {
            return redirect()->route('questions.show', $question)
                ->with('error', 'ベストアンサーは既に決定されています');
        }

        return redirect()->route('questions.show', $question)->with('success', 'ベストアンサーを決定しました');
    }
}
