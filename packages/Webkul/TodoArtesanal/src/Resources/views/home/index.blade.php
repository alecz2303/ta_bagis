<x-shop::layouts :hasFooter="false" :hasFeature="true">
    <x-slot:title>Todo Artesanal</x-slot>

        @php
            $channel = core()->getCurrentChannel();
        @endphp

        <main class="ta-home">
            {{-- Logo / Marca --}}
            <div class="ta-brand">
                <a href="{{ url('/') }}">
                    @if ($channel && $channel->logo_url)
                        <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" class="ta-logo-img">
                    @else
                        <span class="ta-logo-text">TODO ARTESANAL</span>
                    @endif
                </a>
            </div>

            {{-- Grid de productos --}}
            <div class="ta-products">
                <x-shop::products.carousel title="" :src="route('shop.api.products.index')"
                    :navigation-link="route('shop.search.index')" :mode="'grid'" />
            </div>
        </main>
</x-shop::layouts>