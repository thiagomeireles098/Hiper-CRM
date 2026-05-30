<?php

namespace App\Http\Controllers;

use App\Models\Cashier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashierSyncController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:26'],
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $owner = $this->ownerFromToken($validated['token']);
        if (! $owner) {
            return response()->json(['message' => 'Token invÃ¡lido.'], 401);
        }

        $cashier = Cashier::query()
            ->where('tenant_id', $owner->id)
            ->where('username', $validated['username'])
            ->first();

        if (! $cashier || ! hash_equals((string) $cashier->password, (string) $validated['password'])) {
            return response()->json(['message' => 'UsuÃ¡rio ou senha do caixa invÃ¡lidos.'], 401);
        }

        return response()->json([
            'ok' => true,
            'tenant_id' => $owner->id,
            'cashier' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'username' => $cashier->username,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        [$owner, $cashier] = $this->authenticate($request);

        $products = Product::query()
            ->where('tenant_id', $owner->id)
            ->where('type', Product::TYPE_PRODUTO)
            ->orderBy('name')
            ->get(['id', 'name', 'checkout_slug', 'price', 'currency', 'is_active', 'updated_at'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->checkout_slug ?: Str::of($product->id)->replace('-', '')->substr(0, 13)->toString(),
                'name' => $product->name,
                'price' => (float) $product->price,
                'currency' => $product->currency ?: 'BRL',
                'stock' => (int) data_get($product->checkout_config, 'pos.stock', 0),
                'active' => (bool) $product->is_active,
                'updated_at' => $product->updated_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'cashier' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'username' => $cashier->username,
            ],
            'products' => $products,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        [$owner, $cashier] = $this->authenticate($request);

        $validated = $request->validate([
            'sales' => ['nullable', 'array', 'max:200'],
            'sales.*.local_id' => ['required_with:sales', 'string', 'max:80'],
            'sales.*.cpf' => ['nullable', 'string', 'max:20'],
            'sales.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'sales.*.discount' => ['nullable', 'numeric', 'min:0'],
            'sales.*.total' => ['required_with:sales', 'numeric', 'min:0'],
            'sales.*.payment_method' => ['nullable', 'string', 'max:40'],
            'sales.*.payment_payload' => ['nullable', 'array'],
            'sales.*.items' => ['required_with:sales', 'array', 'max:300'],
            'sales.*.items.*.product_id' => ['nullable', 'string', 'max:80'],
            'sales.*.items.*.code' => ['nullable', 'string', 'max:120'],
            'sales.*.items.*.name' => ['required_with:sales.*.items', 'string', 'max:255'],
            'sales.*.items.*.qty' => ['required_with:sales.*.items', 'numeric', 'min:0.001'],
            'sales.*.items.*.price' => ['required_with:sales.*.items', 'numeric', 'min:0'],
            'sales.*.items.*.total' => ['required_with:sales.*.items', 'numeric', 'min:0'],
            'sales.*.created_at' => ['nullable', 'date'],
        ]);

        $accepted = [];
        foreach ($validated['sales'] ?? [] as $sale) {
            $alreadyExists = DB::table('cashier_sales')
                ->where('tenant_id', $owner->id)
                ->where('cashier_id', $cashier->id)
                ->where('local_id', $sale['local_id'])
                ->exists();

            if (! $alreadyExists) {
                DB::table('cashier_sales')->insert([
                    'tenant_id' => $owner->id,
                    'cashier_id' => $cashier->id,
                    'local_id' => $sale['local_id'],
                    'cpf' => $sale['cpf'] ?? null,
                    'subtotal' => $sale['subtotal'] ?? $sale['total'],
                    'discount' => $sale['discount'] ?? 0,
                    'total' => $sale['total'],
                    'payment_method' => $sale['payment_method'] ?? null,
                    'payment_payload' => json_encode($sale['payment_payload'] ?? []),
                    'items' => json_encode($sale['items'] ?? []),
                    'sold_at' => isset($sale['created_at']) ? date('Y-m-d H:i:s', strtotime($sale['created_at'])) : now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $accepted[] = $sale['local_id'];
        }

        return response()->json([
            'ok' => true,
            'accepted_sales' => $accepted,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function updateInfo(): JsonResponse
    {
        return response()->json([
            'version' => (string) config('getfy.version', '1.0.0'),
            'download_url' => url('/hipercaixa/download'),
            'message' => 'Nova versÃ£o do HiperCaixa disponÃ­vel.',
        ]);
    }

    public function download()
    {
        $path = public_path('hipercaixa/build/HiperCaixa.exe');
        if (! is_file($path)) {
            abort(404, 'HiperCaixa.exe ainda nÃ£o foi gerado.');
        }

        return response()->download($path, 'HiperCaixa.exe', [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
        ]);
    }

    private function authenticate(Request $request): array
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:26'],
            'cashier_id' => ['required', 'integer'],
        ]);

        $owner = $this->ownerFromToken($validated['token']);
        abort_if(! $owner, 401, 'Token invÃ¡lido.');

        $cashier = Cashier::query()
            ->where('tenant_id', $owner->id)
            ->where('id', (int) $validated['cashier_id'])
            ->first();
        abort_if(! $cashier, 401, 'Caixa invÃ¡lido.');

        return [$owner, $cashier];
    }

    private function ownerFromToken(string $token): ?User
    {
        return User::query()
            ->where('cashier_sync_token', strtoupper($token))
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_INFOPRODUTOR])
            ->first();
    }
}
