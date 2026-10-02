<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Models\Question;
use App\Models\Stamp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * G8 回答投稿
 */
class AnswerController extends Controller
{
    public function create(Request $request, Question $question): View
    {
        Gate::authorize('answer', $question);

        return view('answers.create', [
            'question' => $question,
            'stamps' => Stamp::ordered()->get(),
            'answerCount' => $request->user()->publishedAnswerCount(),
        ]);
    }

    public function store(StoreAnswerRequest $request, Question $question): RedirectResponse
    {
        Gate::authorize('answer', $question);

        $question->answers()->create([
            'user_id' => $request->user()->id,
            'stamp_id' => $request->validated('stamp_id'),
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('questions.show', $question)->with('success', '回答を投稿しました');
    }
}
