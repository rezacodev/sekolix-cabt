<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\SessionNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionNoteController extends Controller
{
  public function store(Request $request, ExamSession $session): JsonResponse
  {
    $data = $request->validate([
      'catatan' => ['required', 'string', 'max:1000'],
    ]);

    $note = SessionNote::create([
      'exam_session_id' => $session->id,
      'user_id'         => $request->user()->id,
      'catatan'         => $data['catatan'],
      'created_at'      => now(),
    ]);

    $note->load('author');

    return response()->json([
      'success'    => true,
      'id'         => $note->id,
      'catatan'    => $note->catatan,
      'author'     => $note->author->name,
      'created_at' => $note->created_at->format('H:i, d M Y'),
    ]);
  }
}
