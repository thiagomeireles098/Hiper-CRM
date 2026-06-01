<?php

namespace App\Http\Controllers;

use App\Models\PlatformNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformNoticeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PlatformNotices/Index', [
            'notices' => PlatformNotice::query()
                ->with('admin:id,name,email')
                ->latest()
                ->get()
                ->map(fn (PlatformNotice $notice) => $this->noticePayload($notice)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedNotice($request);
        $data['admin_user_id'] = $request->user()->id;
        PlatformNotice::create($data);

        return redirect()->route('platform-notices.index')->with('success', 'Aviso salvo.');
    }

    public function update(Request $request, PlatformNotice $notice): RedirectResponse
    {
        $notice->update($this->validatedNotice($request));

        return redirect()->route('platform-notices.index')->with('success', 'Aviso atualizado.');
    }

    public function destroy(PlatformNotice $notice): RedirectResponse
    {
        $notice->delete();

        return redirect()->route('platform-notices.index')->with('success', 'Aviso excluido.');
    }

    public function send(PlatformNotice $notice): RedirectResponse
    {
        $notice->forceFill(['is_sent' => true])->save();

        return redirect()->route('platform-notices.index')->with('success', 'Aviso enviado para os infoprodutores.');
    }

    private function validatedNotice(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'starts_at' => $validated['start_date'].' '.$validated['start_time'].':00',
            'ends_at' => $validated['end_date'].' '.$validated['end_time'].':00',
        ];
    }

    private function noticePayload(PlatformNotice $notice): array
    {
        return [
            'id' => $notice->id,
            'admin_user' => $notice->admin?->name,
            'title' => $notice->title,
            'description' => $notice->description,
            'start_date' => $notice->starts_at?->format('Y-m-d'),
            'end_date' => $notice->ends_at?->format('Y-m-d'),
            'start_time' => $notice->starts_at?->format('H:i'),
            'end_time' => $notice->ends_at?->format('H:i'),
            'is_sent' => (bool) $notice->is_sent,
        ];
    }
}
