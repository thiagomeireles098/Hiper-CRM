<?php

namespace App\Http\Controllers;

use App\Services\TeamAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSubscriptionController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $permissions = $user ? app(TeamAccessService::class)->permissionsFor($user) : [];

        if (! $user || ! ($user->isAdmin() || $user->isInfoprodutor()) || empty($permissions['platform_subscription.view'])) {
            abort(403);
        }

        return Inertia::render('PlatformSubscription/Edit', [
            'is_master' => $user->isAdmin(),
            'subscription' => [
                'name' => $user->isAdmin() ? 'Assinatura da plataforma' : 'Minha assinatura',
                'price' => $user->platform_subscription_config['price'] ?? null,
                'description' => $user->platform_subscription_config['description'] ?? '',
                'due_day' => $user->platform_payment_due_day,
                'paid' => (bool) $user->platform_payment_paid,
                'grace_days' => (int) ($user->platform_payment_grace_days ?? 0),
                'grace_interest_percent' => (int) ($user->platform_payment_grace_days ?? 0),
            ],
        ]);
    }
}
