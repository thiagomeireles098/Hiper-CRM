<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\TeamAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    public function index(): Response
    {
        $access = app(TeamAccessService::class);
        $users = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_INFOPRODUTOR])
            ->orderByRaw("role = ? DESC", [User::ROLE_ADMIN])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar', 'role', 'cashier_sync_token', 'platform_permissions', 'platform_subscription_config', 'platform_payment_due_day', 'platform_payment_paid', 'platform_payment_grace_days', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar_url' => $u->avatar ? app(\App\Services\StorageService::class)->url($u->avatar) : null,
                'role' => $u->role,
                'is_master' => $u->role === User::ROLE_ADMIN,
                'cashier_sync_token' => $u->role === User::ROLE_INFOPRODUTOR || $u->role === User::ROLE_ADMIN
                    ? $this->ensureCashierSyncToken($u)
                    : null,
                'platform_permissions' => $u->role === User::ROLE_INFOPRODUTOR
                    ? $access->permissionsFor($u)
                    : $access->allPermissions(),
                'platform_subscription_config' => $u->platform_subscription_config ?? [],
                'platform_subscription_product_ids' => array_values($u->platform_subscription_config['product_ids'] ?? []),
                'platform_payment_due_day' => $u->platform_payment_due_day,
                'platform_payment_paid' => (bool) $u->platform_payment_paid,
                'platform_payment_grace_days' => (int) ($u->platform_payment_grace_days ?? 0),
                'platform_payment_grace_interest_percent' => (int) ($u->platform_payment_grace_days ?? 0),
                'created_at' => $u->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'defaultInfoprodutorPermissions' => $access->defaultInfoprodutorPermissions(),
            'platformSubscriptionProducts' => Product::forTenant(auth()->user()->tenant_id)
                ->where('type', Product::TYPE_ASSINANTES)
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'currency', 'is_active'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'currency' => $product->currency ?? 'BRL',
                    'is_active' => (bool) $product->is_active,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Este e-mail já está em uso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_INFOPRODUTOR,
            'cashier_sync_token' => $this->newCashierSyncToken(),
            'platform_permissions' => app(TeamAccessService::class)->defaultInfoprodutorPermissions(),
            'platform_payment_due_day' => (int) now()->day,
            'platform_payment_paid' => false,
            'platform_payment_grace_days' => 0,
        ]);

        $user->update(['tenant_id' => $user->id]);

        return redirect()->route('usuarios.index')->with('success', 'Infoprodutor cadastrado com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role === User::ROLE_ADMIN) {
            abort(403, 'A conta Master (admin) não pode ser excluída.');
        }

        if ($user->role !== User::ROLE_INFOPRODUTOR) {
            abort(403, 'Apenas infoprodutores podem ser excluídos por esta ação.');
        }

        $user->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuário excluído.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'platform_permissions' => ['nullable', 'array'],
        ], [
            'email.unique' => 'Este e-mail já está em uso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if ($user->role === User::ROLE_INFOPRODUTOR) {
            $user->platform_permissions = $validated['platform_permissions'] ?? app(TeamAccessService::class)->defaultInfoprodutorPermissions();
        }
        $user->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado.');
    }

    public function markPlatformPaid(User $user): RedirectResponse
    {
        $this->assertInfoprodutor($user);

        $config = $user->platform_subscription_config ?? [];
        $config['paid_month'] = now()->format('Y-m');

        $user->forceFill([
            'platform_subscription_config' => $config,
            'platform_permissions' => $this->permissionsFromPlatformProducts($config['product_ids'] ?? []),
            'platform_payment_paid' => true,
            'platform_payment_grace_days' => 0,
        ])->save();

        return redirect()->route('usuarios.index')->with('success', 'Pagamento deste mes marcado como pago.');
    }

    public function updatePlatformBilling(Request $request, User $user): RedirectResponse
    {
        $this->assertInfoprodutor($user);

        $validated = $request->validate([
            'platform_payment_due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'platform_payment_grace_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'platform_subscription_product_ids' => ['nullable', 'array'],
            'platform_subscription_product_ids.*' => ['string'],
        ]);

        $config = $user->platform_subscription_config ?? [];
        if (array_key_exists('platform_subscription_product_ids', $validated)) {
            $allowedIds = Product::forTenant(auth()->user()->tenant_id)
                ->where('type', Product::TYPE_ASSINANTES)
                ->whereIn('id', $validated['platform_subscription_product_ids'] ?? [])
                ->pluck('id')
                ->all();
            $config['product_ids'] = array_values($allowedIds);
            $user->platform_payment_paid = false;
        }
        if (array_key_exists('platform_payment_due_day', $validated)) {
            $user->platform_payment_due_day = $validated['platform_payment_due_day'];
        }
        if (array_key_exists('platform_payment_grace_days', $validated)) {
            $user->platform_payment_grace_days = (int) $validated['platform_payment_grace_days'];
        }

        $user->platform_subscription_config = $config;
        $user->save();

        return redirect()->route('usuarios.index')->with('success', 'Pagamento da plataforma atualizado.');
    }

    private function assertInfoprodutor(User $user): void
    {
        if ($user->role !== User::ROLE_INFOPRODUTOR) {
            abort(403, 'Apenas infoprodutores podem receber esta acao.');
        }
    }

    private function permissionsFromPlatformProducts(array $productIds): array
    {
        $permissions = [];
        Product::forTenant(auth()->user()->tenant_id)
            ->where('type', Product::TYPE_ASSINANTES)
            ->whereIn('id', $productIds)
            ->get()
            ->each(function (Product $product) use (&$permissions) {
                $planPermissions = $product->checkout_config['platform_subscription']['permissions'] ?? [];
                if (is_array($planPermissions)) {
                    foreach ($planPermissions as $key => $enabled) {
                        if (is_string($key) && filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
                            $permissions[$key] = true;
                        }
                    }
                }
            });

        return $permissions ?: app(TeamAccessService::class)->defaultInfoprodutorPermissions();
    }

    private function ensureCashierSyncToken(User $user): string
    {
        if (is_string($user->cashier_sync_token) && strlen($user->cashier_sync_token) === 26) {
            return $user->cashier_sync_token;
        }

        $token = $this->newCashierSyncToken();
        $user->forceFill(['cashier_sync_token' => $token])->save();

        return $token;
    }

    private function newCashierSyncToken(): string
    {
        do {
            $token = Str::upper(Str::random(26));
        } while (User::query()->where('cashier_sync_token', $token)->exists());

        return $token;
    }
}
