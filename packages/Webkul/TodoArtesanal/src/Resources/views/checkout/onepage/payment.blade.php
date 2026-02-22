{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.before') !!}

<v-payment-methods :methods="paymentMethods" @processing="stepForward" @processed="stepProcessed">
    <x-shop::shimmer.checkout.onepage.payment-method />
</v-payment-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-payment-methods-template">
                    <div class="mb-7 max-md:last:!mb-0">
                        <template v-if="! methods">
                            <!-- Payment Method shimmer Effect -->
                            <x-shop::shimmer.checkout.onepage.payment-method />
                        </template>

                        <template v-else>
                            {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.before') !!}

                            <!-- Accordion Blade Component -->
                            <x-shop::accordion class="overflow-hidden !border-b-0 max-md:rounded-lg max-md:!border-none max-md:!bg-gray-100">
                                <!-- Accordion Blade Component Header -->
                                <x-slot:header class="px-0 py-4 max-md:p-3 max-md:text-sm max-md:font-medium max-sm:p-2">
                                    <div class="flex items-center justify-between">
                                        <h2 class="text-2xl font-medium max-md:text-base">
                                            @lang('shop::app.checkout.onepage.payment.payment-method')
                                        </h2>
                                    </div>
                                </x-slot>

                                <!-- Accordion Blade Component Content -->
                                <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:rounded-t-none max-md:border max-md:border-t-0 max-md:!p-4">

                                    <div class="flex flex-wrap gap-7 max-md:gap-4 max-sm:gap-2.5">
                                        <div
                                            class="relative cursor-pointer max-md:max-w-full max-md:flex-auto"
                                            v-for="(payment, index) in methods"
                                            :key="payment.method || index"
                                        >
                                            {!! view_render_event('bagisto.shop.checkout.payment-method.before') !!}

                                            <input
                                                type="radio"
                                                name="payment[method]"
                                                :value="payment.payment"
                                                :id="payment.method"
                                                class="peer hidden"
                                                @change="store(payment)"
                                            >

                                            <label
                                                :for="payment.method"
                                                class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                            >
                                            </label>

                                            <label
                                                :for="payment.method"
                                                class="block w-[190px] cursor-pointer rounded-xl border border-zinc-200 p-5 max-md:flex max-md:w-full max-md:gap-5 max-md:rounded-lg max-sm:gap-4 max-sm:px-4 max-sm:py-2.5"
                                            >
                                                {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.before') !!}

                                                <img
                                                    class="max-h-11 max-w-14"
                                                    :src="payment.image"
                                                    width="55"
                                                    height="55"
                                                    :alt="payment.method_title"
                                                    :title="payment.method_title"
                                                />

                                                {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.after') !!}

                                                <div>
                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.before') !!}

                                                    <p class="mt-1.5 text-sm font-semibold max-md:mt-1 max-sm:mt-0">
                                                        @{{ payment.method_title }}
                                                    </p>

                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.after') !!}

                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.before') !!}

                                                    <p class="mt-2.5 text-xs font-medium text-zinc-500 max-md:mt-1 max-sm:mt-0">
                                                        @{{ payment.description }}
                                                    </p>

                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.after') !!}
                                                </div>
                                            </label>

                                            {!! view_render_event('bagisto.shop.checkout.payment-method.after') !!}
                                        </div>
                                    </div>

                                    <!-- 💎 BLOQUE PRO TRANSFERENCIA -->
                                    <div
                                        v-if="shouldShowTransferBlock"
                                        class="mt-6 w-full rounded-2xl border border-pink-200 bg-white p-6 shadow-md"
                                    >

                                        <!-- Header -->
                                        <div class="flex items-center gap-3 border-b border-zinc-100 pb-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 text-pink-600 text-xl">
                                                🏦
                                            </div>
                                            <div>
                                                <p class="text-base font-semibold text-navyBlue">
                                                    Transferencia Bancaria
                                                </p>
                                                <p class="text-xs text-zinc-500">
                                                    Realiza tu pago con los siguientes datos
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Datos bancarios -->
                                        <div class="mt-4 space-y-2 text-sm text-zinc-700">
                                            <p><span class="font-semibold">Banco:</span> BBVA</p>
                                            <p><span class="font-semibold">Titular:</span> Karelly A. Ramírez Velázquez</p>
                                            <p class="flex items-center gap-2">
                                                <span class="font-semibold">CLABE:</span>
                                                <span class="tracking-wider">012100015118299987</span>
                                            </p>
                                        </div>

                                        <!-- Nota -->
                                        <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-xs text-zinc-600">
                                            Usa tu número de pedido como referencia.
                                            Tu pedido se procesará una vez confirmado el pago.
                                        </div>

                                        <!-- WhatsApp PRO -->
                                        <div class="mt-6">

                                            <a
                                                href="https://wa.me/529612139040?text=Hola,%20ya%20realic%C3%A9%20mi%20transferencia%20para%20mi%20pedido."
                                                target="_blank"
                                                class="flex w-full items-center justify-center gap-3 rounded-2xl bg-green-500 px-6 py-4 text-base font-bold text-white shadow-lg transition-all duration-200 hover:bg-green-600 hover:scale-[1.02] active:scale-100"
                                            >
                                                <span class="text-2xl">📲</span>
                                                Enviar comprobante por WhatsApp
                                            </a>

                                            <p class="mt-3 text-center text-xs text-zinc-500">
                                                Envíanos tu comprobante para validar tu pago más rápido.
                                            </p>

                                        </div>


                                    </div>


                                </x-slot>
                            </x-shop::accordion>

                            {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.after') !!}
                        </template>
                    </div>
                </script>

    <script type="module">
        app.component('v-payment-methods', {
            template: '#v-payment-methods-template',

            props: {
                methods: {
                    type: Object,
                    required: true,
                    default: () => null,
                },
            },

            emits: ['processing', 'processed'],

            data() {
                return {
                    selectedPaymentCode: null,
                    selectedPaymentTitle: null,
                };
            },

            computed: {
                // ✅ Se muestra si el code trae "transfer" o el título trae "transferencia"
                shouldShowTransferBlock() {
                    const code = (this.selectedPaymentCode || '').toString().toLowerCase();
                    const title = (this.selectedPaymentTitle || '').toString().toLowerCase();

                    return code.includes('transfer') || title.includes('transferencia');
                }
            },

            methods: {
                store(selectedMethod) {
                    // ✅ Guardamos selección para mostrar el bloque
                    this.selectedPaymentCode = selectedMethod.payment ?? null;
                    this.selectedPaymentTitle = selectedMethod.method_title ?? null;

                    this.$emit('processing', 'review');

                    this.$axios.post("{{ route('shop.checkout.onepage.payment_methods.store') }}", {
                        payment: selectedMethod
                    })
                        .then(response => {
                            this.$emit('processed', response.data.cart);

                            // Used in mobile view.
                            if (window.innerWidth <= 768) {
                                window.scrollTo({
                                    top: document.body.scrollHeight,
                                    behavior: 'smooth'
                                });
                            }
                        })
                        .catch(error => {
                            this.$emit('processing', 'payment');

                            if (error.response?.data?.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });
    </script>
@endPushOnce