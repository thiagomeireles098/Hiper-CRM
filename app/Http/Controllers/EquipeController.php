<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Cashier;
use App\Models\TeamAuditLog;
use App\Models\TeamRole;
use App\Models\User;
use App\Mail\TeamMemberAccessMail;
use App\Services\TenantMailConfigService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EquipeController extends Controller
{
    private function audit(Request $request, string $action, ?string $targetType = null, $targetId = null, array $metadata = []): void
    {
        $actor = $request->user();
        $tenantId = $actor?->tenant_id;
        if (! $tenantId) {
            return;
        }

        TeamAuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId !== null ? (string) $targetId : null,
            'metadata' => $metadata ?: null,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        if (! $tenantId) {
            abort(403, 'Tenant inválido.');
        }

        $products = Product::forTenant($tenantId)->orderBy('name')->get(['id', 'name'])->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
        ])->values()->all();

        $roles = TeamRole::query()
            ->where('tenant_id', $tenantId)
            ->with('products:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (TeamRole $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions' => $r->permissions ?? [],
                'product_ids' => $r->products->pluck('id')->values()->all(),
                'products' => $r->products->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
            ])->values()->all();

        $members = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', User::ROLE_TEAM)
            ->with('teamRole:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'team_role_id', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'team_role_id' => $u->team_role_id,
                'team_role_name' => $u->teamRole?->name,
                'created_at' => $u->created_at?->toIso8601String(),
            ])->values()->all();

        $storage = app(StorageService::class);
        $cashiers = Cashier::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'logo_path', 'created_at'])
            ->map(fn (Cashier $cashier) => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'username' => $cashier->username,
                'logo_url' => $cashier->logo_path ? $storage->url($cashier->logo_path) : null,
                'access_url' => route('usuarios.equipe.caixas.access', $cashier),
                'created_at' => $cashier->created_at?->toIso8601String(),
            ])->values()->all();

        $logs = [];
        if ($user && $user->isAdmin()) {
            $logs = TeamAuditLog::query()
                ->where('tenant_id', $tenantId)
                ->with('actor:id,name,email')
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (TeamAuditLog $l) => [
                    'id' => $l->id,
                    'action' => $l->action,
                    'target_type' => $l->target_type,
                    'target_id' => $l->target_id,
                    'metadata' => $l->metadata ?? [],
                    'ip' => $l->ip,
                    'actor' => $l->actor ? ['id' => $l->actor->id, 'name' => $l->actor->name, 'email' => $l->actor->email] : null,
                    'created_at' => $l->created_at?->toIso8601String(),
                ])->values()->all();
        }

        return Inertia::render('Users/Equipe', [
            'products' => $products,
            'roles' => $roles,
            'members' => $members,
            'cashiers' => $cashiers,
            'logs' => $logs,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            abort(403, 'Tenant inválido.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['string', 'size:36'],
        ]);

        $role = TeamRole::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        $productIds = array_values(array_unique(array_filter($validated['product_ids'] ?? [])));
        if ($productIds) {
            $allowed = Product::forTenant($tenantId)->whereIn('id', $productIds)->pluck('id')->all();
            $role->products()->sync($allowed);
        }

        $this->audit($request, 'team.role.created', TeamRole::class, $role->id, [
            'name' => $role->name,
            'product_ids' => $productIds,
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Cargo criado.');
    }

    public function updateRole(Request $request, TeamRole $role): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $role->tenant_id !== $tenantId) {
            abort(403, 'Cargo não encontrado.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['string', 'size:36'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        $productIds = array_values(array_unique(array_filter($validated['product_ids'] ?? [])));
        $allowed = $productIds
            ? Product::forTenant($tenantId)->whereIn('id', $productIds)->pluck('id')->all()
            : [];
        $role->products()->sync($allowed);

        $this->audit($request, 'team.role.updated', TeamRole::class, $role->id, [
            'name' => $role->name,
            'product_ids' => $allowed,
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Cargo atualizado.');
    }

    public function destroyRole(Request $request, TeamRole $role): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $role->tenant_id !== $tenantId) {
            abort(403, 'Cargo não encontrado.');
        }

        // Remover vínculo dos membros antes (evita confusão de permissão após delete)
        User::query()
            ->where('tenant_id', $tenantId)
            ->where('team_role_id', $role->id)
            ->update(['team_role_id' => null]);

        $this->audit($request, 'team.role.deleted', TeamRole::class, $role->id, [
            'name' => $role->name,
        ]);

        $role->delete();

        return redirect()->route('usuarios.equipe')->with('success', 'Cargo removido.');
    }

    public function storeMember(Request $request): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            abort(403, 'Tenant inválido.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'team_role_id' => ['required', 'integer', 'exists:team_roles,id'],
            'send_access_email' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Este e-mail já está em uso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $role = TeamRole::query()->where('tenant_id', $tenantId)->where('id', (int) $validated['team_role_id'])->first();
        if (! $role) {
            abort(422, 'Cargo inválido para este tenant.');
        }

        $plainPassword = $validated['password'];
        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'role' => User::ROLE_TEAM,
            'tenant_id' => $tenantId,
            'team_role_id' => $role->id,
        ]);

        $sendAccessEmail = (bool) ($validated['send_access_email'] ?? true);
        if ($sendAccessEmail) {
            try {
                app(TenantMailConfigService::class)->applyMailerConfigForTenant($tenantId, [], null);
                Mail::purge('smtp');
                $loginUrl = rtrim((string) config('app.url'), '/') . '/login';
                Mail::mailer('smtp')->to($member->email)->send(new TeamMemberAccessMail(
                    name: $member->name,
                    email: $member->email,
                    password: $plainPassword,
                    loginUrl: $loginUrl
                ));
            } catch (\Throwable $e) {
                return redirect()->route('usuarios.equipe')->with('error', 'Membro criado, mas não foi possível enviar o e-mail de acesso: ' . $e->getMessage());
            }
        }

        $this->audit($request, 'team.member.created', User::class, $member->id, [
            'email' => $member->email,
            'team_role_id' => $member->team_role_id,
            'send_access_email' => $sendAccessEmail,
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Usuário da equipe criado.');
    }

    public function updateMember(Request $request, User $member): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $member->tenant_id !== $tenantId || $member->role !== User::ROLE_TEAM) {
            abort(403, 'Usuário não encontrado.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$member->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'team_role_id' => ['required', 'integer', 'exists:team_roles,id'],
        ], [
            'email.unique' => 'Este e-mail já está em uso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $role = TeamRole::query()->where('tenant_id', $tenantId)->where('id', (int) $validated['team_role_id'])->first();
        if (! $role) {
            abort(422, 'Cargo inválido para este tenant.');
        }

        $member->name = $validated['name'];
        $member->email = $validated['email'];
        $member->team_role_id = $role->id;
        if (! empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }
        $member->save();

        $this->audit($request, 'team.member.updated', User::class, $member->id, [
            'email' => $member->email,
            'team_role_id' => $member->team_role_id,
            'password_changed' => ! empty($validated['password']),
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Usuário atualizado.');
    }

    public function destroyMember(Request $request, User $member): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $member->tenant_id !== $tenantId || $member->role !== User::ROLE_TEAM) {
            abort(403, 'Usuário não encontrado.');
        }

        $this->audit($request, 'team.member.deleted', User::class, $member->id, [
            'email' => $member->email,
            'team_role_id' => $member->team_role_id,
        ]);

        $member->delete();

        return redirect()->route('usuarios.equipe')->with('success', 'Usuário removido.');
    }

    public function storeCashier(Request $request): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            abort(403, 'Tenant inválido.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', 'unique:cashiers,username,NULL,id,tenant_id,'.$tenantId],
            'password' => ['required', 'string', 'min:4'],
        ], [
            'username.unique' => 'Este usuário de caixa já está em uso.',
        ]);

        $cashier = Cashier::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
        ]);

        $this->audit($request, 'team.cashier.created', Cashier::class, $cashier->id, [
            'name' => $cashier->name,
            'username' => $cashier->username,
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Caixa criado.');
    }

    public function updateCashier(Request $request, Cashier $cashier): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $cashier->tenant_id !== $tenantId) {
            abort(403, 'Caixa não encontrado.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', 'unique:cashiers,username,'.$cashier->id.',id,tenant_id,'.$tenantId],
            'password' => ['nullable', 'string', 'min:4'],
        ], [
            'username.unique' => 'Este usuário de caixa já está em uso.',
        ]);

        $cashier->name = $validated['name'];
        $cashier->username = $validated['username'];
        if (! empty($validated['password'])) {
            $cashier->password = $validated['password'];
        }
        $cashier->save();

        $this->audit($request, 'team.cashier.updated', Cashier::class, $cashier->id, [
            'name' => $cashier->name,
            'username' => $cashier->username,
            'password_changed' => ! empty($validated['password']),
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Caixa atualizado.');
    }

    public function updateCashierLogo(Request $request, Cashier $cashier): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $cashier->tenant_id !== $tenantId) {
            abort(403, 'Caixa nÃ£o encontrado.');
        }

        $validated = $request->validate([
            'logo' => ['required', 'image', 'max:4096'],
        ]);

        $file = $validated['logo'];
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'png';
        $path = app(StorageService::class)->putFileAs(
            'cashiers/'.$tenantId,
            $file,
            'cashier-'.$cashier->id.'-'.Str::random(10).'.'.$extension
        );

        $cashier->logo_path = $path;
        $cashier->save();

        $this->audit($request, 'team.cashier.logo.updated', Cashier::class, $cashier->id, [
            'name' => $cashier->name,
            'logo_path' => $path,
        ]);

        return redirect()->route('usuarios.equipe')->with('success', 'Logo do caixa atualizada.');
    }

    public function accessCashier(Request $request, Cashier $cashier): Response
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $cashier->tenant_id !== $tenantId) {
            abort(403, 'Caixa nÃ£o encontrado.');
        }

        return Inertia::render('Cashier/Pdv', [
            'cashier' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'username' => $cashier->username,
                'logo_url' => $cashier->logo_path ? app(StorageService::class)->url($cashier->logo_path) : null,
            ],
            'manager_mode' => true,
        ]);
    }

    public function downloadCashier(Request $request)
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            abort(403, 'Tenant invÃ¡lido.');
        }

        $path = public_path('hipercaixa/build/HiperCaixa.exe');
        if (! is_file($path)) {
            abort(404, 'HiperCaixa.exe ainda nÃ£o foi gerado.');
        }

        return response()->download($path, 'HiperCaixa.exe', [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
        ]);
    }

    public function destroyCashier(Request $request, Cashier $cashier): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId || $cashier->tenant_id !== $tenantId) {
            abort(403, 'Caixa não encontrado.');
        }

        $this->audit($request, 'team.cashier.deleted', Cashier::class, $cashier->id, [
            'name' => $cashier->name,
            'username' => $cashier->username,
        ]);

        $cashier->delete();

        return redirect()->route('usuarios.equipe')->with('success', 'Caixa removido.');
    }

    public function clearLogs(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Apenas admin pode acessar os logs.');
        }

        $tenantId = $user->tenant_id;
        if (! $tenantId) {
            abort(403, 'Tenant inválido.');
        }

        TeamAuditLog::query()->where('tenant_id', $tenantId)->delete();

        $this->audit($request, 'team.logs.cleared', null, null, []);

        return redirect()->route('usuarios.equipe')->with('success', 'Logs limpos.');
    }
}
