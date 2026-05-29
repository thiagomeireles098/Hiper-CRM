<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentBotController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $session = $this->sessionId($tenantId);

        return response()->json([
            'connected' => filter_var(Setting::get('agent_bot_whatsapp_connected', false, $tenantId), FILTER_VALIDATE_BOOLEAN),
            'session_id' => $session,
            'qr_payload' => $this->qrPayload($session),
        ]);
    }

    public function refreshQr(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $session = (string) Str::uuid();

        Setting::set('agent_bot_whatsapp_session_id', $session, $tenantId);
        Setting::set('agent_bot_whatsapp_connected', false, $tenantId);

        return response()->json([
            'connected' => false,
            'session_id' => $session,
            'qr_payload' => $this->qrPayload($session),
        ]);
    }

    public function markConnected(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $connected = (bool) $request->boolean('connected', true);

        Setting::set('agent_bot_whatsapp_connected', $connected, $tenantId);

        return response()->json([
            'connected' => $connected,
            'session_id' => $this->sessionId($tenantId),
        ]);
    }

    private function sessionId(?int $tenantId): string
    {
        $session = (string) Setting::get('agent_bot_whatsapp_session_id', '', $tenantId);
        if ($session !== '') {
            return $session;
        }

        $session = (string) Str::uuid();
        Setting::set('agent_bot_whatsapp_session_id', $session, $tenantId);

        return $session;
    }

    private function qrPayload(string $session): string
    {
        return 'hiperlink-agent-bot:' . $session;
    }
}
