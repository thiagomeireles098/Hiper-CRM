<?php

namespace App\Services;

use App\Models\Cashier;
use App\Models\CashierFiscalDocument;
use App\Models\SpedyIntegration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashierFiscalService
{
    public function configFor(User $owner): array
    {
        $spedy = SpedyIntegration::forTenant($owner->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        return [
            'enabled' => (bool) $spedy,
            'provider' => $spedy ? 'spedy' : null,
            'environment' => $spedy?->environment,
            'message' => $spedy
                ? 'Emissao fiscal ativa pela integracao Spedy.'
                : 'Nenhum provedor fiscal ativo. As vendas ficam pendentes e podem ser impressas como comprovante local.',
        ];
    }

    /**
     * @param array<string, mixed> $sale
     */
    public function issueForCashierSale(User $owner, Cashier $cashier, int $cashierSaleId, array $sale): CashierFiscalDocument
    {
        $document = CashierFiscalDocument::firstOrCreate(
            [
                'tenant_id' => $owner->id,
                'cashier_id' => $cashier->id,
                'local_id' => (string) $sale['local_id'],
                'type' => 'nfce',
            ],
            [
                'cashier_sale_id' => $cashierSaleId,
                'status' => CashierFiscalDocument::STATUS_PENDING,
                'print_payload' => $this->buildPrintPayload($owner, $cashier, $sale),
            ]
        );

        if ($document->status === CashierFiscalDocument::STATUS_AUTHORIZED) {
            return $document;
        }

        $spedy = SpedyIntegration::forTenant($owner->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $spedy || ! $spedy->api_key) {
            $document->forceFill([
                'status' => CashierFiscalDocument::STATUS_PENDING,
                'provider' => null,
                'error_message' => 'Configure uma integracao fiscal ativa para autorizar a NFC-e.',
                'print_payload' => $this->buildPrintPayload($owner, $cashier, $sale),
            ])->save();

            return $document;
        }

        try {
            $result = app(SpedyService::class)->createCashierSaleAndIssueInvoices($sale, $spedy->api_key, $spedy->environment);
            $document->forceFill([
                'status' => CashierFiscalDocument::STATUS_AUTHORIZED,
                'provider' => 'spedy',
                'provider_document_id' => $result['order_id'] ?? null,
                'access_key' => $result['access_key'] ?? null,
                'danfe_url' => $result['danfe_url'] ?? null,
                'authorization_payload' => $result,
                'print_payload' => $this->buildPrintPayload($owner, $cashier, $sale, $result),
                'error_message' => null,
                'issued_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $document->forceFill([
                'status' => CashierFiscalDocument::STATUS_ERROR,
                'provider' => 'spedy',
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
                'print_payload' => $this->buildPrintPayload($owner, $cashier, $sale),
            ])->save();
        }

        return $document;
    }

    /**
     * @param array<string, mixed> $sale
     * @param array<string, mixed> $authorization
     *
     * @return array<string, mixed>
     */
    private function buildPrintPayload(User $owner, Cashier $cashier, array $sale, array $authorization = []): array
    {
        $items = array_values($sale['items'] ?? []);
        $total = (float) ($sale['total'] ?? 0);

        return [
            'title' => 'DANFE NFC-e',
            'company' => [
                'name' => $owner->name ?: 'Hiperlink',
                'email' => $owner->email,
            ],
            'cashier' => [
                'name' => $cashier->name,
                'username' => $cashier->username,
            ],
            'sale' => [
                'local_id' => (string) ($sale['local_id'] ?? ''),
                'cpf' => $sale['cpf'] ?? null,
                'sold_at' => $sale['created_at'] ?? now()->toIso8601String(),
                'subtotal' => (float) ($sale['subtotal'] ?? $total),
                'discount' => (float) ($sale['discount'] ?? 0),
                'total' => $total,
                'payment_method' => $sale['payment_method'] ?? null,
                'payment_payload' => $sale['payment_payload'] ?? [],
            ],
            'items' => array_map(fn (array $item) => [
                'code' => $item['code'] ?? '',
                'name' => $item['name'] ?? 'Produto',
                'qty' => (float) ($item['qty'] ?? 1),
                'price' => (float) ($item['price'] ?? 0),
                'total' => (float) ($item['total'] ?? 0),
            ], $items),
            'authorization' => [
                'provider' => $authorization['provider'] ?? null,
                'order_id' => $authorization['order_id'] ?? null,
                'access_key' => $authorization['access_key'] ?? null,
                'danfe_url' => $authorization['danfe_url'] ?? null,
            ],
        ];
    }
}
