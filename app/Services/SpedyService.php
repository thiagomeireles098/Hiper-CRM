<?php

namespace App\Services;

use App\Models\CheckoutSession;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class SpedyService
{
    private const BASE_URL_PRODUCTION = 'https://api.spedy.com.br/v1';

    private const BASE_URL_SANDBOX = 'https://sandbox-api.spedy.com.br/v1';

    /**
     * Create order in Spedy and trigger invoice issuance.
     *
     * @throws \RuntimeException
     */
    public function createOrderAndIssueInvoices(Order $order, string $apiKey, string $environment): void
    {
        $baseUrl = $environment === \App\Models\SpedyIntegration::ENVIRONMENT_SANDBOX
            ? self::BASE_URL_SANDBOX
            : self::BASE_URL_PRODUCTION;

        $order->loadMissing([
            'user',
            'orderItems.product',
            'orderItems.productOffer',
            'orderItems.subscriptionPlan',
            'product',
        ]);

        $payload = $this->buildOrderPayload($order);
        $response = $this->post($baseUrl, $apiKey, 'orders', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Spedy API error on create order: ' . $response->status() . ' ' . $response->body()
            );
        }

        $data = $response->json();
        $spedyOrderId = $data['id'] ?? null;

        if ($spedyOrderId) {
            $issueResponse = $this->post($baseUrl, $apiKey, "orders/{$spedyOrderId}/invoices/issue", []);
            if (! $issueResponse->successful()) {
                throw new \RuntimeException(
                    'Spedy API error on issue invoices: ' . $issueResponse->status() . ' ' . $issueResponse->body()
                );
            }
        }
    }

    /**
     * Create a fiscal order from an offline/online HiperCaixa sale and trigger invoice issuance.
     *
     * @param array<string, mixed> $sale
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function createCashierSaleAndIssueInvoices(array $sale, string $apiKey, string $environment): array
    {
        $baseUrl = $environment === \App\Models\SpedyIntegration::ENVIRONMENT_SANDBOX
            ? self::BASE_URL_SANDBOX
            : self::BASE_URL_PRODUCTION;

        $payload = $this->buildCashierSalePayload($sale);
        $response = $this->post($baseUrl, $apiKey, 'orders', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Spedy API error on create cashier order: ' . $response->status() . ' ' . $response->body()
            );
        }

        $data = $response->json();
        $spedyOrderId = $data['id'] ?? $data['orderId'] ?? null;

        if ($spedyOrderId) {
            $issueResponse = $this->post($baseUrl, $apiKey, "orders/{$spedyOrderId}/invoices/issue", []);
            if (! $issueResponse->successful()) {
                throw new \RuntimeException(
                    'Spedy API error on issue cashier invoices: ' . $issueResponse->status() . ' ' . $issueResponse->body()
                );
            }

            $issueData = $issueResponse->json();

            return [
                'provider' => 'spedy',
                'order_id' => (string) $spedyOrderId,
                'access_key' => $issueData['accessKey'] ?? $issueData['access_key'] ?? $issueData['key'] ?? null,
                'danfe_url' => $issueData['danfeUrl'] ?? $issueData['danfe_url'] ?? $issueData['pdfUrl'] ?? null,
                'create_response' => $data,
                'issue_response' => $issueData,
            ];
        }

        return [
            'provider' => 'spedy',
            'order_id' => null,
            'create_response' => $data,
        ];
    }

    /**
     * Build OrderPostDto payload from Order.
     *
     * @return array<string, mixed>
     */
    public function buildOrderPayload(Order $order): array
    {
        $session = CheckoutSession::where('order_id', $order->id)->first();
        $customerName = $session?->name ?? $order->user?->name ?? trim($order->email ?: 'Cliente');
        if ($customerName === '') {
            $customerName = 'Cliente';
        }

        $customer = [
            'name' => mb_substr($customerName, 0, 80),
            'federalTaxNumber' => $this->normalizeCpfCnpj($order->cpf),
            'email' => $order->email ? mb_substr($order->email, 0, 50) : null,
            'phone' => $order->phone ? mb_substr(preg_replace('/\D/', '', $order->phone), 0, 15) : null,
        ];

        $items = [];
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            $productName = $product?->name ?? 'Produto';
            $amount = (float) $item->amount;
            $code = (string) ($item->product_offer_id ?? $item->subscription_plan_id ?? $item->product_id ?? $item->id);
            $items[] = [
                'quantity' => 1,
                'price' => $amount,
                'amount' => $amount,
                'product' => [
                    'code' => $code,
                    'name' => $productName,
                    'price' => $amount,
                ],
            ];
        }

        if (empty($items)) {
            $mainProduct = $order->product;
            $amount = (float) $order->amount;
            $items[] = [
                'quantity' => 1,
                'price' => $amount,
                'amount' => $amount,
                'product' => [
                    'code' => (string) ($mainProduct?->id ?? $order->product_id ?? $order->id),
                    'name' => $mainProduct?->name ?? 'Produto',
                    'price' => $amount,
                ],
            ];
        }

        $amount = (float) $order->amount;
        $date = $order->created_at->utc()->format('Y-m-d\TH:i:s\Z');

        $payload = [
            'transactionId' => (string) $order->id,
            'customer' => $customer,
            'amount' => $amount,
            'date' => $date,
            'status' => 'approved',
            'paymentMethod' => $this->mapPaymentMethod($order->gateway),
            'items' => $items,
        ];

        return $payload;
    }

    /**
     * @param array<string, mixed> $sale
     * @return array<string, mixed>
     */
    public function buildCashierSalePayload(array $sale): array
    {
        $items = [];
        foreach ($sale['items'] ?? [] as $item) {
            $quantity = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $amount = (float) ($item['total'] ?? ($quantity * $price));
            $items[] = [
                'quantity' => $quantity,
                'price' => $price,
                'amount' => $amount,
                'product' => [
                    'code' => (string) ($item['code'] ?? $item['product_id'] ?? ''),
                    'name' => mb_substr((string) ($item['name'] ?? 'Produto'), 0, 120),
                    'price' => $price,
                ],
            ];
        }

        if (empty($items)) {
            $items[] = [
                'quantity' => 1,
                'price' => (float) ($sale['total'] ?? 0),
                'amount' => (float) ($sale['total'] ?? 0),
                'product' => [
                    'code' => (string) ($sale['local_id'] ?? ''),
                    'name' => 'Venda HiperCaixa',
                    'price' => (float) ($sale['total'] ?? 0),
                ],
            ];
        }

        return [
            'transactionId' => (string) ($sale['local_id'] ?? uniqid('cashier-', true)),
            'customer' => [
                'name' => 'Cliente PDV',
                'federalTaxNumber' => $this->normalizeCpfCnpj($sale['cpf'] ?? null),
            ],
            'amount' => (float) ($sale['total'] ?? 0),
            'date' => isset($sale['created_at'])
                ? gmdate('Y-m-d\TH:i:s\Z', strtotime((string) $sale['created_at']))
                : gmdate('Y-m-d\TH:i:s\Z'),
            'status' => 'approved',
            'paymentMethod' => $this->mapPaymentMethod($sale['payment_method'] ?? null),
            'items' => $items,
        ];
    }

    private function normalizeCpfCnpj(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    private function mapPaymentMethod(?string $gateway): string
    {
        if (! $gateway) {
            return 'pix';
        }
        $g = strtolower($gateway);
        if (str_contains($g, 'pix')) {
            return 'pix';
        }
        if (str_contains($g, 'dinheiro') || str_contains($g, 'cash')) {
            return 'cash';
        }
        if (str_contains($g, 'cheque') || str_contains($g, 'check')) {
            return 'check';
        }
        if (str_contains($g, 'boleto') || str_contains($g, 'ticket')) {
            return 'billetBank';
        }
        if (str_contains($g, 'debit') || str_contains($g, 'debito')) {
            return 'debitCard';
        }
        if (str_contains($g, 'card') || str_contains($g, 'credit') || str_contains($g, 'credito') || str_contains($g, 'cartao')) {
            return 'creditCard';
        }

        return 'pix';
    }

    private function post(string $baseUrl, string $apiKey, string $path, array $body): Response
    {
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        return Http::timeout(30)
            ->withHeaders(['X-Api-Key' => $apiKey])
            ->post($url, $body);
    }
}
