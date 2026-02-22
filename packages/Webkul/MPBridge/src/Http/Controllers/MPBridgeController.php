<?php

namespace Webkul\MPBridge\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Checkout\Models\Cart as CartModel;

// MercadoPago SDK v3 (mercadopago/dx-php)
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class MPBridgeController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
    ) {
    }

    protected function mpInit(): void
    {
        $token = (string) core()->getConfigData('sales.payment_methods.mpbridge.access_token');

        if (!$token) {
            throw new \RuntimeException('MPBridge: access_token vacío en configuración.');
        }

        MercadoPagoConfig::setAccessToken($token);

        // Runtime env (depende versión SDK)
        $sandbox = (bool) core()->getConfigData('sales.payment_methods.mpbridge.sandbox');

        if (method_exists(MercadoPagoConfig::class, 'setRuntimeEnvironment')) {
            MercadoPagoConfig::setRuntimeEnvironment(
                $sandbox ? MercadoPagoConfig::LOCAL : MercadoPagoConfig::SERVER
            );
        } elseif (method_exists(MercadoPagoConfig::class, 'setRuntimeEnviroment')) {
            // algunas versiones traen el typo "Enviroment"
            MercadoPagoConfig::setRuntimeEnviroment(
                $sandbox ? MercadoPagoConfig::LOCAL : MercadoPagoConfig::SERVER
            );
        }
    }

    private function backToCheckout(string $type, string $message)
    {
        return redirect()->route('shop.checkout.onepage.index')->with($type, $message);
    }

    /**
     * Obtener carrito aunque se pierda el objeto de sesión.
     */
    private function resolveCart(): ?\Webkul\Checkout\Models\Cart
    {
        $cart = Cart::getCart();

        if ($cart?->id) {
            return $cart->loadMissing(['items', 'billing_address', 'shipping_address', 'payment']);
        }

        $sessionCart = session()->get('cart');
        $cartId = null;

        if (is_numeric($sessionCart)) {
            $cartId = (int) $sessionCart;
        } elseif (is_array($sessionCart) && isset($sessionCart['id'])) {
            $cartId = (int) $sessionCart['id'];
        } elseif (is_object($sessionCart) && isset($sessionCart->id)) {
            $cartId = (int) $sessionCart->id;
        }

        if ($cartId) {
            $cartDb = \Webkul\Checkout\Models\Cart::query()
                ->with(['items', 'billing_address', 'shipping_address', 'payment'])
                ->find($cartId);

            if ($cartDb) {
                Cart::setCart($cartDb);
                return $cartDb;
            }
        }

        return null;
    }

    /**
     * True si la notificación es de merchant_order (incluye variantes)
     */
    private function isMerchantOrderNotification(?string $topicOrType): bool
    {
        if (!$topicOrType) {
            return false;
        }

        $t = strtolower($topicOrType);

        return str_contains($t, 'merchant_order') || str_contains($t, 'merchant_orders');
    }

    /**
     * True si la notificación es de payment (incluye variantes)
     */
    private function isPaymentNotification(?string $topicOrType): bool
    {
        if (!$topicOrType) {
            return false;
        }

        $t = strtolower($topicOrType);

        return $t === 'payment' || str_contains($t, 'payment');
    }

    public function redirect(Request $request)
    {
        $this->mpInit();

        $cart = $this->resolveCart();

        if (!$cart) {
            Log::warning('MPBridge redirect: cart not found', [
                'session_keys' => array_keys(session()->all()),
            ]);

            return redirect()->route('shop.checkout.cart.index')
                ->with('error', 'No se encontró el carrito para procesar el pago (sesión/checkout).');
        }

        // Items desde carrito (NO desde orden)
        $items = [];
        foreach ($cart->items as $item) {
            $items[] = [
                'title' => $item->name ?? 'Item',
                'quantity' => (int) ($item->quantity ?? 1),
                'unit_price' => (float) ($item->price ?? 0),
                'currency_id' => $cart->cart_currency_code ?? 'MXN',
            ];
        }

        $externalRef = 'cart_' . $cart->id;

        try {
            $pref = (new PreferenceClient())->create([
                'items' => $items,

                'external_reference' => $externalRef,
                'metadata' => [
                    'cart_id' => (int) $cart->id,
                    'currency' => (string) ($cart->cart_currency_code ?? 'MXN'),
                ],

                'back_urls' => [
                    'success' => route('mpbridge.return', ['ref' => $externalRef, 's' => 'success']),
                    'pending' => route('mpbridge.return', ['ref' => $externalRef, 's' => 'pending']),
                    'failure' => route('mpbridge.return', ['ref' => $externalRef, 's' => 'failure']),
                ],

                // Webhook: funciona aunque cierren el navegador
                'notification_url' => route('mpbridge.webhook'),
            ]);

            Log::info('MPBridge preference created', [
                'cart_id' => $cart->id,
                'preference_id' => $pref->id ?? null,
                'external_ref' => $externalRef,
            ]);
        } catch (\Throwable $e) {
            Log::error('MPBridge redirect: preference create failed', [
                'cart_id' => $cart->id,
                'message' => $e->getMessage(),
            ]);

            return $this->backToCheckout('error', 'No se pudo iniciar el pago con Mercado Pago.');
        }

        $sandbox = (bool) core()->getConfigData('sales.payment_methods.mpbridge.sandbox');
        $url = $sandbox ? ($pref->sandbox_init_point ?? null) : ($pref->init_point ?? null);

        if (!$url) {
            Log::error('MPBridge: preference sin init_point', ['pref' => $pref]);
            return $this->backToCheckout('error', 'No se pudo iniciar el pago.');
        }

        return redirect()->away($url);
    }

    public function return(Request $request)
    {
        $s = $request->get('s');

        if ($s === 'pending') {
            return $this->backToCheckout('info', 'Pago pendiente. Te avisaremos cuando se confirme.');
        }

        if ($s === 'failure') {
            return $this->backToCheckout('error', 'Pago no aprobado o fue rechazado.');
        }

        // Success: intentar confirmar rápido (si no, webhook lo hará)
        $paymentId = $request->get('payment_id') ?: $request->get('collection_id');

        if ($paymentId) {
            try {
                $order = $this->handlePaymentAndCreateOrder((string) $paymentId, 'return');

                if ($order) {
                    session()->flash('order_id', $order->id);
                    return redirect()->route('shop.checkout.onepage.success');
                }
            } catch (\Throwable $e) {
                Log::warning('MPBridge return: handlePayment failed', [
                    'paymentId' => $paymentId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->backToCheckout('info', 'Pago recibido. Estamos confirmándolo, en breve verás tu pedido.');
    }

    public function webhook(Request $request)
    {
        $topic = $request->query('topic')
            ?? $request->query('type')
            ?? $request->input('topic')
            ?? $request->input('type');

        // Ignorar merchant_order (y variantes)
        if ($this->isMerchantOrderNotification($topic)) {
            $id = $request->query('id') ?? $request->input('id') ?? $request->input('data_id');

            Log::info('MPBridge webhook: merchant_order notification (ignored)', [
                'topic' => $topic,
                'id' => $id,
                'query' => $request->query(),
                'payload' => $request->all(),
            ]);

            return response('ok', 200);
        }

        // Payment id
        $paymentId = $request->input('data.id')
            ?? $request->input('id')
            ?? $request->query('id');

        if (!$paymentId && $this->isPaymentNotification($topic)) {
            $paymentId = $request->input('data_id') ?? $request->query('data_id');
        }

        if (!$paymentId) {
            return response('ok', 200);
        }

        // ✅ Idempotencia ATÓMICA (evita doble proceso por spam de webhooks)
        $cacheKey = "mpbridge:webhook:payment:{$paymentId}";
        if (!Cache::add($cacheKey, true, now()->addMinutes(10))) {
            return response('ok', 200);
        }

        try {
            $order = $this->handlePaymentAndCreateOrder((string) $paymentId, 'webhook');

            Cache::put($cacheKey, true, now()->addDays($order ? 30 : 1));
        } catch (\Throwable $e) {
            Log::error('MPBridge webhook: fatal', [
                'paymentId' => $paymentId,
                'message' => $e->getMessage(),
                'query' => $request->query(),
                'payload' => $request->all(),
            ]);

            // permitir reintento si falló
            Cache::forget($cacheKey);
        }

        return response('ok', 200);
    }

    /**
     * Fetch payment -> detect cart_id -> create order if missing -> create invoice if approved -> create transaction
     */
    private function handlePaymentAndCreateOrder(string $paymentId, string $source): ?\Webkul\Sales\Models\Order
    {
        $this->mpInit();

        $paymentClient = new PaymentClient();

        // Si MP manda algo que NO es pago, aquí truena: por eso filtramos merchant_order arriba.
        $payment = $paymentClient->get((int) $paymentId);

        $status = $payment->status ?? null;
        $ref = $payment->external_reference ?? null;
        $amount = (float) ($payment->transaction_amount ?? 0);
        $currency = (string) ($payment->currency_id ?? 'MXN');

        Log::info('MPBridge payment fetched', [
            'source' => $source,
            'paymentId' => $paymentId,
            'status' => $status,
            'external_reference' => $ref,
            'metadata' => $payment->metadata ?? null,
        ]);

        // cart_id desde metadata o external_reference cart_XX
        $cartId = null;

        if (!empty($payment->metadata?->cart_id)) {
            $cartId = (int) $payment->metadata->cart_id;
        }

        if (!$cartId && is_string($ref) && str_starts_with($ref, 'cart_')) {
            $cartId = (int) str_replace('cart_', '', $ref);
        }

        if (!$cartId) {
            Log::warning('MPBridge: cannot determine cart_id', [
                'paymentId' => $paymentId,
                'ref' => $ref,
            ]);
            return null;
        }

        // ✅ Lock por cart_id: evita que 2 procesos creen orden al mismo tiempo (increment_id duplicado)
        $lock = Cache::lock("mpbridge:lock:cart:{$cartId}", 30);

        return $lock->block(10, function () use ($cartId, $paymentId, $source, $payment, $status, $amount, $currency, $ref) {

            // 1) Si ya existe la orden, solo sincroniza y finaliza carrito
            $existing = \Webkul\Sales\Models\Order::query()
                ->where('cart_id', $cartId)
                ->latest('id')
                ->first();

            if ($existing) {
                $this->ensureTransaction($existing->id, $paymentId, $status, $amount, $currency, $payment);
                $this->syncOrderStatusAndInvoice($existing, $paymentId, $status, $amount, $currency);

                // ✅ “Bonito” en pedido (tabla)
                $this->persistOrderPaymentAdditional($existing, $paymentId, $payment, $currency, $status, $ref);

                // ✅ comentario corto (solo si no existe ya)
                $this->ensureMpAuditComment($existing, $paymentId, $status, $currency);

                $this->finalizeCart($cartId);

                return $existing;
            }

            // 2) Cargar carrito
            $cartDb = \Webkul\Checkout\Models\Cart::query()
                ->with(['items', 'billing_address', 'shipping_address', 'payment'])
                ->find($cartId);

            if (!$cartDb) {
                Log::warning('MPBridge: cart not found in DB', [
                    'cart_id' => $cartId,
                    'paymentId' => $paymentId,
                ]);
                return null;
            }

            Cart::setCart($cartDb);

            // 3) Armar order data desde cart
            $orderData = $this->buildOrderDataFromCart($cartDb, $paymentId, $status, $currency, $ref);

            $orderData['status'] = match ($status) {
                'approved' => 'processing',
                'rejected', 'cancelled', 'charged_back', 'refunded' => 'canceled',
                default => 'pending_payment',
            };

            // 4) Crear orden
            $order = $this->orderRepository->create($orderData);

            // 5) Guardar TX (JSON completo)
            $this->ensureTransaction($order->id, $paymentId, $status, $amount, $currency, $payment);

            // 6) Sincronizar estado/factura
            $this->syncOrderStatusAndInvoice($order, $paymentId, $status, $amount, $currency);

            // 7) ✅ “Bonito” en pedido (tabla)
            $this->persistOrderPaymentAdditional($order, $paymentId, $payment, $currency, $status, $ref);

            // 8) ✅ comentario corto
            $this->ensureMpAuditComment($order, $paymentId, $status, $currency);

            // 9) ✅ finalizar carrito (DB + sesión)
            $this->finalizeCart($cartId);

            return $order;
        });
    }

    /**
     * Bagisto espera payment.additional como lista: [ ['title'=>..,'value'=>..], ... ]
     * Esto se renderiza bonito en la vista del pedido SIN TOCAR VISTAS.
     */
    private function buildOrderDataFromCart($cartDb, string $paymentId, ?string $status, string $currency, ?string $externalRef): array
    {
        $billing = $this->mapAddress($cartDb->billing_address, 'order_billing');
        $shipping = $this->mapAddress($cartDb->shipping_address, 'order_shipping');
        $items = $this->mapItems($cartDb->items);

        $additional = [
            ['title' => 'Proveedor', 'value' => 'Mercado Pago'],
            ['title' => 'Payment ID', 'value' => (string) $paymentId],
            ['title' => 'Estado', 'value' => (string) ($status ?? 'unknown')],
            ['title' => 'External Ref', 'value' => (string) ($externalRef ?? '')],
            ['title' => 'Moneda', 'value' => (string) $currency],
        ];

        return [
            'cart_id' => $cartDb->id,

            'customer_email' => $cartDb->customer_email,
            'customer_first_name' => $cartDb->customer_first_name,
            'customer_last_name' => $cartDb->customer_last_name,

            'is_guest' => (int) $cartDb->is_guest,
            'customer_id' => $cartDb->customer_id,
            'channel_id' => $cartDb->channel_id,
            'channel_type' => Channel::class,

            'shipping_method' => $cartDb->shipping_method,
            'coupon_code' => $cartDb->coupon_code,

            'items_count' => $cartDb->items_count,
            'items_qty' => $cartDb->items_qty,

            'grand_total' => (float) $cartDb->grand_total,
            'base_grand_total' => (float) $cartDb->base_grand_total,
            'sub_total' => (float) $cartDb->sub_total,
            'base_sub_total' => (float) $cartDb->base_sub_total,

            'tax_total' => (float) $cartDb->tax_total,
            'base_tax_total' => (float) $cartDb->base_tax_total,

            'discount_amount' => (float) $cartDb->discount_amount,
            'base_discount_amount' => (float) $cartDb->base_discount_amount,

            'shipping_amount' => (float) $cartDb->shipping_amount,
            'base_shipping_amount' => (float) $cartDb->base_shipping_amount,

            'sub_total_incl_tax' => (float) $cartDb->sub_total_incl_tax,
            'base_sub_total_incl_tax' => (float) $cartDb->base_sub_total_incl_tax,

            'order_currency_code' => $currency,
            'base_currency_code' => $currency,
            'channel_currency_code' => $currency,

            'billing_address' => $billing,
            'shipping_address' => $shipping,

            'items' => $items,

            'payment' => [
                'method' => 'mpbridge',
                'additional' => $additional,
            ],
        ];
    }

    private function mapAddress(?object $addr, string $type): array
    {
        if (!$addr) {
            return [];
        }

        $a = $addr->toArray();

        unset($a['id'], $a['cart_id'], $a['order_id'], $a['created_at'], $a['updated_at']);

        if (isset($a['address1']) && is_string($a['address1'])) {
            $a['address1'] = [$a['address1']];
        }

        if ((!isset($a['address1']) || empty($a['address1'])) && !empty($a['address'])) {
            $a['address1'] = [$a['address']];
        }

        $a['address_type'] = $type;

        return $a;
    }

    private function mapItems($cartItems): array
    {
        $items = [];

        foreach ($cartItems as $item) {
            $i = $item->toArray();

            unset($i['id'], $i['cart_id'], $i['created_at'], $i['updated_at']);

            $qty = (float) ($i['quantity'] ?? 1);

            $items[] = [
                'product_id' => $i['product_id'] ?? null,
                'product_type' => Product::class,
                'sku' => $i['sku'] ?? null,
                'type' => $i['type'] ?? 'simple',
                'name' => $i['name'] ?? 'Item',
                'weight' => $i['weight'] ?? 0,
                'total_weight' => $i['total_weight'] ?? (($i['weight'] ?? 0) * $qty),
                'qty_ordered' => $qty,

                'price' => (float) ($i['price'] ?? 0),
                'base_price' => (float) ($i['base_price'] ?? ($i['price'] ?? 0)),
                'total' => (float) ($i['total'] ?? 0),
                'base_total' => (float) ($i['base_total'] ?? ($i['total'] ?? 0)),

                'tax_percent' => (float) ($i['tax_percent'] ?? 0),
                'tax_amount' => (float) ($i['tax_amount'] ?? 0),
                'base_tax_amount' => (float) ($i['base_tax_amount'] ?? 0),

                'discount_percent' => (float) ($i['discount_percent'] ?? 0),
                'discount_amount' => (float) ($i['discount_amount'] ?? 0),
                'base_discount_amount' => (float) ($i['base_discount_amount'] ?? 0),

                'price_incl_tax' => (float) ($i['price_incl_tax'] ?? ($i['price'] ?? 0)),
                'base_price_incl_tax' => (float) ($i['base_price_incl_tax'] ?? ($i['base_price'] ?? ($i['price'] ?? 0))),
                'total_incl_tax' => (float) ($i['total_incl_tax'] ?? ($i['total'] ?? 0)),
                'base_total_incl_tax' => (float) ($i['base_total_incl_tax'] ?? ($i['base_total'] ?? ($i['total'] ?? 0))),

                'additional' => $i['additional'] ?? [],
            ];
        }

        return $items;
    }

    /**
     * Guardar JSON completo en order_transactions.data (auditoría).
     */
    private function ensureTransaction(int $orderId, string $paymentId, ?string $status, float $amount, string $currency, $paymentObj): void
    {
        $tx = $this->orderTransactionRepository->findOneWhere([
            'transaction_id' => (string) $paymentId,
        ]);

        if ($tx) {
            return;
        }

        $raw = json_encode($paymentObj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $this->orderTransactionRepository->create([
                'transaction_id' => (string) $paymentId,
                'status' => (string) ($status ?? 'unknown'),
                'type' => 'mpbridge',
                'amount' => $amount,
                'payment_method' => 'mpbridge',
                'data' => $raw,
                'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('MPBridge: tx create failed', [
                'order_id' => $orderId,
                'paymentId' => $paymentId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function syncOrderStatusAndInvoice($order, string $paymentId, ?string $status, float $amount, string $currency): void
    {
        if (in_array($status, ['pending', 'in_process', 'authorized'], true)) {
            $this->orderRepository->update(['status' => 'pending_payment'], $order->id);
            return;
        }

        if (in_array($status, ['rejected', 'cancelled', 'charged_back', 'refunded'], true)) {
            $this->orderRepository->update(['status' => 'canceled'], $order->id);
            return;
        }

        if ($status !== 'approved') {
            return;
        }

        $this->orderRepository->update(['status' => 'processing'], $order->id);

        // Factura idempotente
        try {
            $order->loadMissing('invoices', 'items');

            if ($order->invoices->count() > 0) {
                return;
            }

            $items = [];
            foreach ($order->items as $item) {
                $ordered = (int) ($item->qty_ordered ?? 0);
                $invoiced = (int) ($item->qty_invoiced ?? 0);
                $qtyToInvoice = max(0, $ordered - $invoiced);

                if ($qtyToInvoice > 0) {
                    $items[$item->id] = $qtyToInvoice;
                }
            }

            if (empty($items)) {
                return;
            }

            $invoice = $this->invoiceRepository->create([
                'order_id' => $order->id,
                'invoice' => ['items' => $items],
            ]);

            // link invoice_id en transaction si existe
            if (!empty($invoice?->id)) {
                $tx = $this->orderTransactionRepository->findOneWhere([
                    'transaction_id' => (string) $paymentId,
                ]);

                if ($tx) {
                    $this->orderTransactionRepository->update([
                        'invoice_id' => $invoice->id,
                    ], $tx->id);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('MPBridge: invoice create failed', [
                'order_id' => $order->id,
                'paymentId' => $paymentId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Arma el resumen "bonito" que Bagisto renderiza en tabla (Additional Details).
     */
    private function mpAdditionalDetails(string $paymentId, $payment, string $currency, ?string $status, ?string $externalRef): array
    {
        $statusDetail = $payment->status_detail ?? 'N/A';

        $payerEmail = $payment->payer?->email ?? 'N/A';
        $payerId = $payment->payer?->id ?? 'N/A';

        $method = $payment->payment_method_id ?? 'N/A';
        $type = $payment->payment_type_id ?? 'N/A';

        $ref = $externalRef ?? ($payment->external_reference ?? 'N/A');

        $created = $payment->date_created ?? 'N/A';
        $approved = $payment->date_approved ?? 'N/A';

        $amount = (string) ($payment->transaction_amount ?? 'N/A');
        $installments = (string) ($payment->installments ?? 'N/A');

        return [
            ['title' => 'Proveedor', 'value' => 'Mercado Pago'],
            ['title' => 'Payment ID', 'value' => (string) $paymentId],
            ['title' => 'Estado', 'value' => (string) ($status ?? 'unknown')],
            ['title' => 'Detalle', 'value' => (string) $statusDetail],
            ['title' => 'Monto', 'value' => "{$amount} {$currency}"],
            ['title' => 'Cuotas', 'value' => $installments],
            ['title' => 'Método', 'value' => (string) $method],
            ['title' => 'Tipo', 'value' => (string) $type],
            ['title' => 'External Ref', 'value' => (string) $ref],
            ['title' => 'Email comprador', 'value' => (string) $payerEmail],
            ['title' => 'Payer ID', 'value' => (string) $payerId],
            ['title' => 'Creado', 'value' => (string) $created],
            ['title' => 'Aprobado', 'value' => (string) $approved],
        ];
    }

    /**
     * Persiste el resumen "bonito" en order_payment.additional (para la vista del pedido / emails).
     */
    private function persistOrderPaymentAdditional($order, string $paymentId, $payment, string $currency, ?string $status, ?string $externalRef): void
    {
        try {
            $order->loadMissing('payment');

            if (!$order->payment) {
                return;
            }

            $order->payment->additional = $this->mpAdditionalDetails($paymentId, $payment, $currency, $status, $externalRef);
            $order->payment->save();
        } catch (\Throwable $e) {
            Log::warning('MPBridge: cannot persist order payment additional', [
                'order_id' => $order->id ?? null,
                'paymentId' => $paymentId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Comentario corto (auditoría) — no amontona, no requiere vista.
     */
    private function ensureMpAuditComment($order, string $paymentId, ?string $status, string $currency): void
    {
        try {
            $marker = "MPBridge|payment_id={$paymentId}";

            // evita duplicados si llegan 2 webhooks
            $exists = $order->comments()
                ->where('comment', 'like', '%' . $marker . '%')
                ->exists();

            if ($exists) {
                return;
            }

            $comment =
                "🟦 Mercado Pago (MPBridge)\n" .
                "Payment ID: {$paymentId}\n" .
                "Estado: " . ($status ?? 'unknown') . "\n" .
                "Moneda: {$currency}\n" .
                $marker;

            $order->comments()->create([
                'comment' => $comment,
                'customer_notified' => 0,
                'is_visible_on_front' => 0,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Vacía/desactiva carrito para que al volver de MP ya se vea vacío.
     */
    private function finalizeCart(int $cartId): void
    {
        try {
            CartModel::query()
                ->where('id', $cartId)
                ->update([
                    'is_active' => 0,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('MPBridge: finalizeCart DB update failed', [
                'cart_id' => $cartId,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            Cart::deActivateCart();

            if (method_exists(Cart::class, 'removeCart')) {
                Cart::removeCart();
            }

            session()->forget('cart');
            session()->forget('coupon_code');
        } catch (\Throwable $e) {
            Log::warning('MPBridge: finalizeCart session clear failed', [
                'cart_id' => $cartId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
