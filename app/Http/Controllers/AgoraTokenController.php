<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgoraTokenController extends Controller
{
    /**
     * Issue a student RTC token for a given channel.
     * For now, uses a placeholder env token if a real builder is not available.
     */
    public function rtcToken(Request $request)
    {
        // Restrict to logged-in students only
        if (!Auth::check()) {
            return response()->json(["error" => "unauthorized"], 401);
        }

        $channel = trim((string) $request->query("channel", ""));
        if ($channel === "") {
            return response()->json(["error" => "missing_channel"], 422);
        }

        $appId = (string) config("app.agora_app_id");
        $appCert = (string) config("app.agora_app_certificate");
        if ($appId === "") {
            return response()->json(["error" => "missing_agora_app_id"], 500);
        }

        // TTL in seconds (default: 24h for student calls)
        $ttl = (int) config("services.agora.ttl_seconds", 24 * 60 * 60);
        $expireAt = now("Asia/Manila")->addSeconds($ttl)->timestamp;

        // Determine UID: use authenticated student ID to keep it stable per user
        $uid = (string) (Auth::user()->Stud_ID ?? Auth::id());
        if ($uid === "" || $uid === null) {
            $uid = (string) random_int(100000, 999999);
        }

        if ($appCert === "") {
            return response()->json(["error" => "missing_agora_app_certificate"], 500);
        }

        // Build RTC token using TokenFactory
        try {
            $factory = new \Monyxie\Agora\TokenBuilder\TokenFactory($appId, $appCert);
            $tokenObj = $factory->create(
                (string) $channel,
                (string) $uid,
                [
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::JOIN_CHANNEL => $expireAt,
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::PUBLISH_AUDIO_STREAM => $expireAt,
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::PUBLISH_VIDEO_STREAM => $expireAt,
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::PUBLISH_DATA_STREAM => $expireAt,
                ],
                $expireAt,
            );
            $token = $tokenObj->toString();

            return response()->json([
                "appId" => $appId,
                "token" => $token,
                "expiresAt" => $expireAt,
                "uid" => $uid,
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                ["error" => "token_build_failed", "message" => $e->getMessage()],
                500,
            );
        }
    }

    /**
     * Issue a student RTM token if chat/presence is needed (optional).
     */
    public function rtmToken(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(["error" => "unauthorized"], 401);
        }
        $appId = (string) config("app.agora_app_id");
        $appCert = (string) config("app.agora_app_certificate");
        if ($appId === "") {
            return response()->json(["error" => "missing_agora_app_id"], 500);
        }
        if ($appCert === "") {
            return response()->json(["error" => "missing_agora_app_certificate"], 500);
        }
        $ttl = (int) config("services.agora.ttl_seconds", 24 * 60 * 60);
        $expireAt = now("Asia/Manila")->addSeconds($ttl)->timestamp;
        $uid = (string) (Auth::user()->Stud_ID ?? Auth::id());
        if ($uid === "" || $uid === null) {
            $uid = (string) random_int(100000, 999999);
        }

        try {
            $factory = new \Monyxie\Agora\TokenBuilder\TokenFactory($appId, $appCert);
            $token = null;
            try {
                // Some versions accept (uid, expireAt)
                $tokenObj = $factory->createRtmToken((string) $uid, $expireAt);
                $token = $tokenObj->toString();
            } catch (\Throwable $e) {
                // Fallback to (uid)->withTs(expireAt)
                $tokenObj = $factory->createRtmToken((string) $uid)->withTs($expireAt);
                $token = $tokenObj->toString();
            }
            return response()->json([
                "appId" => $appId,
                "token" => $token,
                "expiresAt" => $expireAt,
                "uid" => $uid,
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                ["error" => "token_build_failed", "message" => $e->getMessage()],
                500,
            );
        }
    }

    /**
     * Issue a professor RTC token for a given channel (professor guard).
     */
    public function rtcTokenProfessor(Request $request)
    {
        if (!Auth::guard("professor")->check()) {
            return response()->json(["error" => "unauthorized"], 401);
        }
        $channel = trim((string) $request->query("channel", ""));
        if ($channel === "") {
            return response()->json(["error" => "missing_channel"], 422);
        }
        $appId = (string) config("app.agora_app_id");
        $appCert = (string) config("app.agora_app_certificate");
        if ($appId === "") {
            return response()->json(["error" => "missing_agora_app_id"], 500);
        }
        if ($appCert === "") {
            return response()->json(["error" => "missing_agora_app_certificate"], 500);
        }
        $ttl = (int) config("services.agora.ttl_seconds", 24 * 60 * 60);
        $expireAt = now("Asia/Manila")->addSeconds($ttl)->timestamp;
        $uid = (string) (Auth::guard("professor")->user()->Prof_ID ?? random_int(100000, 999999));

        try {
            $factory = new \Monyxie\Agora\TokenBuilder\TokenFactory($appId, $appCert);
            $tokenObj = $factory->create(
                (string) $channel,
                (string) $uid,
                [
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::JOIN_CHANNEL => $expireAt,
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::PUBLISH_AUDIO_STREAM => $expireAt,
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::PUBLISH_VIDEO_STREAM => $expireAt,
                    \Monyxie\Agora\TokenBuilder\AccessControl\Privilege::PUBLISH_DATA_STREAM => $expireAt,
                ],
                $expireAt,
            );
            $token = $tokenObj->toString();
            return response()->json([
                "appId" => $appId,
                "token" => $token,
                "expiresAt" => $expireAt,
                "uid" => $uid,
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                ["error" => "token_build_failed", "message" => $e->getMessage()],
                500,
            );
        }
    }

    /**
     * Issue a professor RTM token (optional chat/presence).
     */
    public function rtmTokenProfessor(Request $request)
    {
        if (!Auth::guard("professor")->check()) {
            return response()->json(["error" => "unauthorized"], 401);
        }
        $appId = (string) config("app.agora_app_id");
        $appCert = (string) config("app.agora_app_certificate");
        if ($appId === "") {
            return response()->json(["error" => "missing_agora_app_id"], 500);
        }
        if ($appCert === "") {
            return response()->json(["error" => "missing_agora_app_certificate"], 500);
        }
        $ttl = (int) config("services.agora.ttl_seconds", 24 * 60 * 60);
        $expireAt = now("Asia/Manila")->addSeconds($ttl)->timestamp;
        $uid = (string) (Auth::guard("professor")->user()->Prof_ID ?? random_int(100000, 999999));

        try {
            $factory = new \Monyxie\Agora\TokenBuilder\TokenFactory($appId, $appCert);
            $token = null;
            try {
                $tokenObj = $factory->createRtmToken((string) $uid, $expireAt);
                $token = $tokenObj->toString();
            } catch (\Throwable $e) {
                $tokenObj = $factory->createRtmToken((string) $uid)->withTs($expireAt);
                $token = $tokenObj->toString();
            }
            return response()->json([
                "appId" => $appId,
                "token" => $token,
                "expiresAt" => $expireAt,
                "uid" => $uid,
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                ["error" => "token_build_failed", "message" => $e->getMessage()],
                500,
            );
        }
    }
}
