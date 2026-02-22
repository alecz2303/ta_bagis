<?php

namespace Webkul\MPBridge\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class MPBridge extends Payment
{
    protected $code = 'mpbridge';

    public function isAvailable()
    {
        return (bool) core()->getConfigData('sales.payment_methods.mpbridge.active');
    }

    public function getTitle()
    {
        return core()->getConfigData('sales.payment_methods.mpbridge.title') ?: 'Mercado Pago';
    }

    public function getDescription()
    {
        return core()->getConfigData('sales.payment_methods.mpbridge.description') ?: '';
    }

    public function getSortOrder()
    {
        return (int) (core()->getConfigData('sales.payment_methods.mpbridge.sort') ?? 1);
    }

    public function getRedirectUrl()
    {
        return route('mpbridge.redirect');
    }

    /**
     * ✅ ESTO ES LO QUE USA LA CARD DEL CHECKOUT PARA EL <img src="...">
     * Si no existe, Bagisto puede renderizar <img> pero sin src.
     */
    public function getImage()
    {
        $logo = core()->getConfigData('sales.payment_methods.mpbridge.logo');

        if (!$logo) {
            return null;
        }

        // Normalmente $logo viene como: configuration/xxxxx.png
        // Storage::url() te genera: /storage/configuration/xxxxx.png
        $url = Storage::url($logo);

        // Por si Storage::url regresa algo inesperado, fallback seguro:
        return $url ?: asset('storage/' . ltrim($logo, '/'));
    }

    /**
     * IMPORTANTÍSIMO:
     * Bagisto (admin + emails) espera que aquí exista al menos ['title'].
     */
    public function getAdditionalDetails()
    {
        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'logo' => $this->getImage(), // ✅ ya regresa URL completa usable
        ];
    }
}
