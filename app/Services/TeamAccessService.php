<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;

class TeamAccessService
{
    /**
     * @return array<string, bool>
     */
    public function permissionsFor(User $user): array
    {
        if ($user->isAdmin()) {
            return $this->allPermissions();
        }

        if ($user->isInfoprodutor()) {
            if (! $this->hasActivePlatformSubscription($user)) {
                return array_replace(
                    array_fill_keys(array_keys($this->allPermissions()), false),
                    [
                        'vendas.view' => true,
                        'platform_subscription.view' => true,
                        'vendas.assinaturas.view' => true,
                    ]
                );
            }

            return $this->normalizePermissions($user->platform_permissions, $this->defaultInfoprodutorPermissions());
        }

        if (! $user->isTeam()) {
            return [];
        }

        $raw = $user->teamRole?->permissions;
        if (! is_array($raw)) {
            return [];
        }

        return $this->normalizePermissions($raw);
    }

    public function can(User $user, string $permission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInfoprodutor()) {
            $perms = $this->permissionsFor($user);

            return ! empty($perms[$permission]);
        }

        if (! $user->isTeam()) {
            return false;
        }

        $perms = $this->permissionsFor($user);

        return ! empty($perms[$permission]);
    }

    /**
     * @return list<string>
     */
    public function allowedProductIdsFor(User $user): array
    {
        if ($user->isAdmin() || $user->isInfoprodutor()) {
            $tenantId = $user->tenant_id;
            if ($tenantId === null) {
                return [];
            }
            return Product::forTenant($tenantId)->pluck('id')->all();
        }

        if (! $user->isTeam()) {
            return [];
        }

        return $user->teamRole?->products()->pluck('products.id')->all() ?? [];
    }

    /**
     * @return array<string, bool>
     */
    public function allPermissions(): array
    {
        return [
            'dashboard.view' => true,
            'vendas.view' => true,
            'vendas.assinaturas.view' => true,
            'billing.one_time' => true,
            'billing.subscription' => true,
            'reembolsos.view' => true,
            'reembolsos.manage' => true,
            'produtos.view' => true,
            'products.delivery.aplicativo' => true,
            'products.delivery.area_membros' => true,
            'products.delivery.area_membros_externa' => true,
            'products.delivery.link' => true,
            'products.delivery.link_pagamento' => true,
            'products.delivery.produto' => true,
            'products.delivery.assinantes' => true,
            'products.business.supermercado' => true,
            'products.business.farmacia' => true,
            'products.business.loja_roupas' => true,
            'products.business.informatica_assistencia' => true,
            'products.business.padaria' => true,
            'relatorios.view' => true,
            'integracoes.view' => true,
            'email_marketing.view' => true,
            'api_pagamentos.view' => true,
            'configuracoes.view' => true,
            'settings.email' => true,
            'settings.storage' => true,
            'settings.traducoes' => true,
            'settings.moedas' => true,
            'settings.cron' => true,
            'settings.update' => true,
            'settings.agente_bot' => true,
            'settings.caixa' => true,
            'equipe.manage' => true,
            'caixa.manage' => true,
            'platform_subscription.view' => true,
            'platform_subscription.manage' => true,
        ];
    }

    public function defaultInfoprodutorPermissions(): array
    {
        return [
            'dashboard.view' => true,
            'vendas.view' => true,
            'vendas.assinaturas.view' => false,
            'billing.one_time' => true,
            'billing.subscription' => false,
            'reembolsos.view' => true,
            'reembolsos.manage' => true,
            'produtos.view' => true,
            'products.delivery.aplicativo' => false,
            'products.delivery.area_membros' => false,
            'products.delivery.area_membros_externa' => false,
            'products.delivery.link' => false,
            'products.delivery.link_pagamento' => false,
            'products.delivery.produto' => true,
            'products.delivery.assinantes' => false,
            'products.business.supermercado' => true,
            'products.business.farmacia' => false,
            'products.business.loja_roupas' => false,
            'products.business.informatica_assistencia' => false,
            'products.business.padaria' => false,
            'relatorios.view' => true,
            'integracoes.view' => true,
            'email_marketing.view' => false,
            'api_pagamentos.view' => false,
            'configuracoes.view' => true,
            'settings.email' => false,
            'settings.storage' => false,
            'settings.traducoes' => false,
            'settings.moedas' => true,
            'settings.cron' => false,
            'settings.update' => true,
            'settings.agente_bot' => false,
            'settings.caixa' => true,
            'equipe.manage' => true,
            'caixa.manage' => false,
            'platform_subscription.view' => true,
            'platform_subscription.manage' => false,
        ];
    }

    public function hasActivePlatformSubscription(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isInfoprodutor()) {
            return true;
        }

        $config = $user->platform_subscription_config ?? [];
        $productIds = $config['product_ids'] ?? [];
        if (! is_array($productIds) || $productIds === []) {
            return true;
        }

        if ((bool) $user->platform_payment_paid) {
            return true;
        }

        $dueDay = max(1, min(31, (int) ($user->platform_payment_due_day ?: 1)));
        $today = Carbon::now();
        $dueDay = min($dueDay, $today->daysInMonth);
        $dueAt = $today->copy()->day($dueDay)->endOfDay();
        $graceDays = max(0, (int) ($user->platform_payment_grace_days ?? 0));

        return $today->lte($dueAt->addDays($graceDays));
    }

    private function normalizePermissions(?array $raw, array $defaults = []): array
    {
        $perms = $defaults;
        foreach (($raw ?? []) as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $perms[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $perms;
    }
}
