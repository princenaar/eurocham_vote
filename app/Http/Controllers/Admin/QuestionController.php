<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionQuestion;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function store(Request $request, Election $election): RedirectResponse
    {
        abort_unless($election->isQuestionsVote(), 404);

        if (! $election->canEditConfiguration()) {
            return redirect()->route('admin.election.edit', ['election' => $election->id])
                ->withErrors(['questions' => 'Les questions sont verrouillées dès l’ouverture du vote.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [
            'title.required' => 'Le titre de la question est obligatoire.',
        ]);

        $question = $election->questions()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? ($election->questions()->count() + 1),
        ]);

        if ($election->status === Election::STATUS_DRAFT) {
            $election->update(['status' => Election::STATUS_READY]);
        }

        AuditLogger::log('question.created', "Question ajoutée : {$question->title}", [
            'election_id' => $election->id,
            'question_id' => $question->id,
        ]);

        return redirect()->route('admin.election.edit', ['election' => $election->id])
            ->with('status', 'Question ajoutée.');
    }

    public function destroy(Election $election, ElectionQuestion $question): RedirectResponse
    {
        abort_unless($election->isQuestionsVote() && $question->election_id === $election->id, 404);

        if (! $election->canEditConfiguration()) {
            return redirect()->route('admin.election.edit', ['election' => $election->id])
                ->withErrors(['questions' => 'Les questions sont verrouillées dès l’ouverture du vote.']);
        }

        $title = $question->title;
        $question->delete();

        if (! $election->questions()->exists()) {
            $election->update(['status' => Election::STATUS_DRAFT]);
        }

        AuditLogger::log('question.deleted', "Question supprimée : {$title}", ['election_id' => $election->id]);

        return redirect()->route('admin.election.edit', ['election' => $election->id])
            ->with('status', 'Question supprimée.');
    }
}
