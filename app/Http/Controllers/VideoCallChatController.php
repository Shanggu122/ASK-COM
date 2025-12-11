<?php

namespace App\Http\Controllers;

use App\Models\VideoCallMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class VideoCallChatController extends Controller
{
    public function history(Request $request)
    {
        if (!Schema::hasTable('video_call_messages')) {
            return response()->json([
                'messages' => [],
                'status' => 'migrations_pending',
            ], 503);
        }

        $channel = trim((string) $request->query('channel', ''));
        if ($channel === '') {
            return response()->json(['error' => 'channel_required'], 422);
        }

        $afterId = $request->query('after_id');
        $limit = min(200, (int) $request->query('limit', 100));

        $query = VideoCallMessage::where('channel', $channel)->orderBy('id');
        if ($afterId !== null && $afterId !== '') {
            $query->where('id', '>', (int) $afterId);
        }

        $messages = $query
            ->limit($limit)
            ->get()
            ->map(function (VideoCallMessage $message) {
                return [
                    'id' => $message->id,
                    'channel' => $message->channel,
                    'sender_role' => $message->sender_role,
                    'sender_uid' => $message->sender_uid,
                    'sender_name' => $message->sender_name,
                    'message' => $message->message,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('video_call_messages')) {
            return response()->json(['error' => 'migrations_pending'], 503);
        }

        if (!Auth::check() && !Auth::guard('professor')->check()) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $data = $request->only(['channel', 'message']);
        $validator = Validator::make($data, [
            'channel' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'invalid', 'messages' => $validator->errors()->all()], 422);
        }

        $channel = trim($data['channel']);
        $messageText = trim($data['message']);
        if ($messageText === '') {
            return response()->json(['error' => 'empty'], 422);
        }

        $senderRole = 'student';
        $senderUid = null;
        $senderName = null;

        if (Auth::guard('professor')->check()) {
            $prof = Auth::guard('professor')->user();
            $senderRole = 'professor';
            $senderUid = (string) ($prof->Prof_ID ?? '');
            $senderName = $prof->Name ?? $prof->email ?? 'Professor';
        } elseif (Auth::check()) {
            $student = Auth::user();
            $senderRole = 'student';
            $senderUid = (string) ($student->Stud_ID ?? '');
            $senderName = $student->Name ?? $student->name ?? $student->email ?? 'Student';
        }

        if ($senderUid === null || $senderUid === '') {
            $senderUid = null;
        }

        $message = VideoCallMessage::create([
            'channel' => $channel,
            'sender_role' => $senderRole,
            'sender_uid' => $senderUid,
            'sender_name' => $senderName,
            'message' => $messageText,
        ]);

        return response()->json([
            'id' => $message->id,
            'channel' => $message->channel,
            'sender_role' => $message->sender_role,
            'sender_uid' => $message->sender_uid,
            'sender_name' => $message->sender_name,
            'message' => $message->message,
            'created_at' => $message->created_at?->toIso8601String(),
        ]);
    }
}
