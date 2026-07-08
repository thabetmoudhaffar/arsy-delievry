<?php
/** Stylized Arsy Delivery brand text — variants: header, hero, cta, footer */
$brandVariant = $brandVariant ?? 'header';
?>
<span class="arsy-brand arsy-brand--<?= sanitize($brandVariant) ?>">
    <span class="arsy-brand-text">
        <span class="arsy-word-arsy">Arsy</span>
        <span class="arsy-word-delivery">
            Del<span class="arsy-pin-letter"><i class="fas fa-location-dot" aria-hidden="true"></i></span>very
        </span>
    </span>
    <svg class="arsy-brand-curve" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M8 32 Q 40 4, 95 28 L 95 34 L 88 34 L 88 28" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    </svg>
</span>
