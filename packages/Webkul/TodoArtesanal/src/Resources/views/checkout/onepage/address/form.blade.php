@pushOnce('scripts')
    <script type="text/x-template" id="v-checkout-address-form-template">
                                <div class="mt-2 max-md:mt-3">
                                    <x-shop::form.control-group class="hidden">
                                        <x-shop::form.control-group.control
                                            type="text"
                                            ::name="controlName + '.id'"
                                            ::value="address.id"
                                        />
                                    </x-shop::form.control-group>

                                    <!-- Company Name -->
                                    <x-shop::form.control-group>
                                        <x-shop::form.control-group.label>
                                            @lang('shop::app.checkout.onepage.address.company-name')
                                        </x-shop::form.control-group.label>

                                        <x-shop::form.control-group.control
                                            type="text"
                                            ::name="controlName + '.company_name'"
                                            ::value="address.company_name"
                                            :placeholder="trans('shop::app.checkout.onepage.address.company-name')"
                                        />
                                    </x-shop::form.control-group>

                                    {!! view_render_event('bagisto.shop.checkout.onepage.address.form.company_name.after') !!}

                                    <!-- First Name -->
                                    <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                                        <x-shop::form.control-group>
                                            <x-shop::form.control-group.label class="required !mt-0">
                                                @lang('shop::app.checkout.onepage.address.first-name')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="text"
                                                ::name="controlName + '.first_name'"
                                                ::value="address.first_name"
                                                rules="required"
                                                :label="trans('shop::app.checkout.onepage.address.first-name')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.first-name')"
                                            />

                                            <x-shop::form.control-group.error ::name="controlName + '.first_name'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.first_name.after') !!}

                                        <!-- Last Name -->
                                        <x-shop::form.control-group>
                                            <x-shop::form.control-group.label class="required !mt-0">
                                                @lang('shop::app.checkout.onepage.address.last-name')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="text"
                                                ::name="controlName + '.last_name'"
                                                ::value="address.last_name"
                                                rules="required"
                                                :label="trans('shop::app.checkout.onepage.address.last-name')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.last-name')"
                                            />

                                            <x-shop::form.control-group.error ::name="controlName + '.last_name'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.last_name.after') !!}
                                    </div>

                                    <!-- Email -->
                                    <x-shop::form.control-group>
                                        <x-shop::form.control-group.label class="required !mt-0">
                                            @lang('shop::app.checkout.onepage.address.email')
                                        </x-shop::form.control-group.label>

                                        <x-shop::form.control-group.control
                                            type="email"
                                            ::name="controlName + '.email'"
                                            ::value="address.email"
                                            rules="required|email"
                                            :label="trans('shop::app.checkout.onepage.address.email')"
                                            placeholder="email@example.com"
                                        />

                                        <x-shop::form.control-group.error ::name="controlName + '.email'" />
                                    </x-shop::form.control-group>

                                    {!! view_render_event('bagisto.shop.checkout.onepage.address.form.email.after') !!}

                                    <!-- Vat ID -->
                                    <template v-if="controlName=='billing'">
                                        <x-shop::form.control-group>
                                            <x-shop::form.control-group.label>
                                                @lang('shop::app.checkout.onepage.address.vat-id')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="text"
                                                ::name="controlName + '.vat_id'"
                                                ::value="address.vat_id"
                                                :label="trans('shop::app.checkout.onepage.address.vat-id')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.vat-id')"
                                            />

                                            <x-shop::form.control-group.error ::name="controlName + '.vat_id'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.vat_id.after') !!}
                                    </template>

                                    <!-- Street Address -->
                                    <x-shop::form.control-group>
                                        <x-shop::form.control-group.label class="required !mt-0">
                                            @lang('shop::app.checkout.onepage.address.street-address')
                                        </x-shop::form.control-group.label>

                                        <x-shop::form.control-group.control
                                            type="text"
                                            ::name="controlName + '.address.[0]'"
                                            ::value="address.address[0]"
                                            rules="required|address"
                                            :label="trans('shop::app.checkout.onepage.address.street-address')"
                                            :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                                        />

                                        <x-shop::form.control-group.error
                                            class="mb-2"
                                            ::name="controlName + '.address.[0]'"
                                        />

                                        @if (core()->getConfigData('customer.address.information.street_lines') > 1)
                                            @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                                                <x-shop::form.control-group.control
                                                    type="text"
                                                    ::name="controlName + '.address.[{{ $i }}]'"
                                                    rules="address"
                                                    :label="trans('shop::app.checkout.onepage.address.street-address')"
                                                    :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                                                />

                                                <x-shop::form.control-group.error
                                                    class="mb-2"
                                                    ::name="controlName + '.address.[{{ $i }}]'"
                                                />
                                            @endfor
                                        @endif
                                    </x-shop::form.control-group>

                                    {!! view_render_event('bagisto.shop.checkout.onepage.address.form.address.after') !!}

                                    <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                                        <!-- Country (MX fijo) -->
                                        <x-shop::form.control-group class="!mb-4 hidden">
                                            <x-shop::form.control-group.label class="required !mt-0">
                                                @lang('shop::app.checkout.onepage.address.country')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="select"
                                                ::name="controlName + '.country'"
                                                ::value="address.country"
                                                v-model="selectedCountry"
                                                rules="required"
                                                :label="trans('shop::app.checkout.onepage.address.country')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.country')"
                                            >
                                                <option value="MX">México</option>
                                            </x-shop::form.control-group.control>

                                            <x-shop::form.control-group.error ::name="controlName + '.country'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.country.after') !!}

                                        <!-- State (solo lectura, se llena por CP) -->
                                        <x-shop::form.control-group>
                                            <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }} !mt-0">
                                                @lang('shop::app.checkout.onepage.address.state')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="text"
                                                ::name="controlName + '.state'"
                                                v-model="address.state"
                                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                                :label="trans('shop::app.checkout.onepage.address.state')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.state')"
                                                readonly
                                            />

                                            <x-shop::form.control-group.error ::name="controlName + '.state'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.state.after') !!}
                                    </div>

                                    <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                                        <!-- City (solo lectura, se llena por CP) -->
                                        <x-shop::form.control-group>
                                            <x-shop::form.control-group.label class="required !mt-0">
                                                @lang('shop::app.checkout.onepage.address.city')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="text"
                                                ::name="controlName + '.city'"
                                                v-model="address.city"
                                                rules="required"
                                                :label="trans('shop::app.checkout.onepage.address.city')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.city')"
                                                readonly
                                            />

                                            <x-shop::form.control-group.error ::name="controlName + '.city'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.city.after') !!}

                                        <!-- Postcode (DIPOMEX) -->
                                        <x-shop::form.control-group>
                                            <x-shop::form.control-group.label class="{{ core()->isPostCodeRequired() ? 'required' : '' }} !mt-0">
                                                @lang('shop::app.checkout.onepage.address.postcode')
                                            </x-shop::form.control-group.label>

                                            <x-shop::form.control-group.control
                                                type="text"
                                                ::name="controlName + '.postcode'"
                                                v-model="address.postcode"
                                                rules="{{ core()->isPostCodeRequired() ? 'required' : '' }}|postcode"
                                                :label="trans('shop::app.checkout.onepage.address.postcode')"
                                                :placeholder="trans('shop::app.checkout.onepage.address.postcode')"
                                                maxlength="5"
                                                inputmode="numeric"
                                                @keyup="onPostcodeKey"
                                                @change="onPostcodeKey"
                                            />

                                            <p v-if="postcodeHint" class="text-xs mt-1 opacity-70">
                                                @{{ postcodeHint }}
                                            </p>

                                            <x-shop::form.control-group.error ::name="controlName + '.postcode'" />
                                        </x-shop::form.control-group>

                                        {!! view_render_event('bagisto.shop.checkout.onepage.address.form.postcode.after') !!}
                                    </div>

                                    <!-- ✅ Colonia (SELECT NATIVO para que SI se pinte dinámico) -->
                                    <div class="mb-4">
                                        <label class="required !mt-0 block font-medium">
                                            Colonia
                                        </label>

                                        <select
                                            class="w-full rounded-lg border px-3 py-2"
                                            :name="controlName + '.address.[1]'"
                                            v-model="address.address[1]"
                                            required
                                        >
                                            <option value="">Selecciona tu colonia</option>

                                            <option v-for="(c, i) in colonies" :key="i" :value="c">
                                                @{{ c }}
                                            </option>
                                        </select>

                                        <p
                                            v-if="address.postcode && address.postcode.length === 5 && !colonies.length && !postcodeLoading"
                                            class="text-xs mt-1 opacity-70"
                                        >
                                            No se encontraron colonias para ese CP.
                                        </p>
                                    </div>

                                    <!-- Phone Number -->
                                    <x-shop::form.control-group>
                                        <x-shop::form.control-group.label class="required !mt-0">
                                            @lang('shop::app.checkout.onepage.address.telephone')
                                        </x-shop::form.control-group.label>

                                        <x-shop::form.control-group.control
                                            type="text"
                                            ::name="controlName + '.phone'"
                                            ::value="address.phone"
                                            rules="required|phone"
                                            :label="trans('shop::app.checkout.onepage.address.telephone')"
                                            :placeholder="trans('shop::app.checkout.onepage.address.telephone')"
                                        />

                                        <x-shop::form.control-group.error ::name="controlName + '.phone'" />
                                    </x-shop::form.control-group>

                                    {!! view_render_event('bagisto.shop.checkout.onepage.address.form.phone.after') !!}
                                </div>
                            </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: { type: String, required: true },

                address: {
                    type: Object,
                    default: () => ({
                        id: 0,
                        company_name: '',
                        first_name: '',
                        last_name: '',
                        email: '',
                        address: [],
                        country: 'MX',
                        state: '',
                        city: '',
                        postcode: '',
                        phone: '',
                    }),
                },
            },

            data() {
                return {
                    selectedCountry: 'MX',
                    countries: [],
                    states: null,

                    colonies: [],
                    postcodeHint: '',
                    postcodeTimer: null,
                    postcodeLoading: false,
                    lastPostcode: '',
                };
            },

            computed: {
                haveStates() {
                    return !!this.states?.[this.selectedCountry]?.length;
                },
            },

            mounted() {
                this.selectedCountry = 'MX';
                this.address.country = 'MX';

                this.getCountries();
                this.getStates();

                // asegura que exista el array address y el índice colonia
                if (!Array.isArray(this.address.address)) this.address.address = [];
                if (typeof this.address.address[1] === 'undefined') this.address.address[1] = '';
            },

            methods: {
                getCountries() {
                    this.$axios.get("{{ route('shop.api.core.countries') }}")
                        .then(r => { this.countries = r.data.data || []; })
                        .catch(() => { });
                },

                getStates() {
                    this.$axios.get("{{ route('shop.api.core.states') }}")
                        .then(r => { this.states = r.data.data || null; })
                        .catch(() => { });
                },

                // ✅ handler confiable: keyup/change siempre trae event real
                onPostcodeKey(e) {
                    const raw = e?.target?.value ?? this.address.postcode ?? '';
                    const v = raw.toString().replace(/\D/g, '').slice(0, 5);

                    // sincroniza el modelo
                    this.address.postcode = v;

                    clearTimeout(this.postcodeTimer);

                    // Si no son 5 dígitos: limpiamos y permitimos que vuelva a buscar después
                    if (v.length !== 5) {
                        this.postcodeHint = '';
                        this.colonies = [];
                        this.lastPostcode = '';
                        return;
                    }

                    // Si ya busqué este CP, no vuelvas a tocar colonias
                    if (v === this.lastPostcode) {
                        return;
                    }

                    // Nuevo CP válido: aquí sí indicamos "buscando" y disparamos debounce
                    this.postcodeHint = 'Buscando información para ' + v + '…';

                    this.postcodeTimer = setTimeout(() => {
                        this.lookupPostcode(v);
                    }, 200);
                },


                async lookupPostcode(cp) {
                    if (this.postcodeLoading) return;
                    if (this.lastPostcode === cp) return;

                    this.lastPostcode = cp;
                    this.postcodeLoading = true;
                    this.postcodeHint = 'Buscando información para ' + cp + '…';

                    try {
                        const res = await this.$axios.get(`/mx/cp/${cp}`);

                        if (!res?.data?.ok) throw new Error('CP no encontrado');

                        const data = res.data.data || {};

                        this.address.state = data.state || '';
                        this.address.city = data.municipality || '';

                        this.colonies = Array.isArray(data.colonies) ? data.colonies : [];

                        if (!Array.isArray(this.address.address)) this.address.address = [];
                        if (typeof this.address.address[1] === 'undefined') this.address.address[1] = '';

                        // (Opcional) autoselecciona la primera colonia si aún no hay selección
                        if (this.colonies.length && !this.address.address[1]) {
                            this.address.address[1] = this.colonies[0];
                        }

                        this.postcodeHint = `CP ${cp} encontrado ✔`;
                    } catch (e) {
                        this.postcodeHint = 'No encontramos ese CP. Verifica e intenta de nuevo.';
                        this.colonies = [];
                        this.lastPostcode = '';
                    } finally {
                        this.postcodeLoading = false;
                    }
                },
            },
        });
    </script>
@endPushOnce