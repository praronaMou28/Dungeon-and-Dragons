<?php


function heroIllustration(string $type): string {
    switch ($type) {

        case 'farmer':
          
            return <<<SVG
<svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
  <circle cx="80" cy="80" r="72" fill="#2F8F4E" opacity="0.10"/>
  <circle cx="80" cy="80" r="52" fill="#2F8F4E" opacity="0.14"/>
  <g stroke="#E7A93A" stroke-width="3" stroke-linecap="round" opacity="0.6">
    <line x1="80" y1="10" x2="80" y2="24"/>
    <line x1="80" y1="136" x2="80" y2="150"/>
    <line x1="10" y1="80" x2="24" y2="80"/>
    <line x1="136" y1="80" x2="150" y2="80"/>
    <line x1="30" y1="30" x2="40" y2="40"/>
    <line x1="120" y1="120" x2="130" y2="130"/>
    <line x1="130" y1="30" x2="120" y2="40"/>
    <line x1="40" y1="120" x2="30" y2="130"/>
  </g>
  <path d="M80 118 C80 92 80 78 80 60" stroke="#1F7A3D" stroke-width="6" stroke-linecap="round" fill="none"/>
  <path d="M80 92 C64 92 56 80 58 66 C74 66 80 78 80 92 Z" fill="#2F8F4E"/>
  <path d="M80 78 C96 78 104 66 102 52 C86 52 80 64 80 78 Z" fill="#3FA85F"/>
  <ellipse cx="80" cy="122" rx="26" ry="8" fill="#1F7A3D" opacity="0.18"/>
</svg>
SVG;

        case 'buyer':
            
            return <<<SVG
<svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
  <circle cx="80" cy="80" r="72" fill="#C9892B" opacity="0.10"/>
  <circle cx="80" cy="80" r="52" fill="#C9892B" opacity="0.14"/>
  <rect x="42" y="76" width="76" height="46" rx="4" fill="#E7A93A"/>
  <rect x="48" y="86" width="20" height="36" rx="2" fill="#FFF6E5"/>
  <rect x="92" y="86" width="20" height="20" rx="2" fill="#FFF6E5"/>
  <path d="M36 76 L48 48 L112 48 L124 76 Z" fill="#C9892B"/>
  <path d="M36 76 Q46 84 56 76 Q66 84 76 76 Q86 84 96 76 Q106 84 116 76 Q120 84 124 76"
        fill="none" stroke="#FFF6E5" stroke-width="3"/>
  <circle cx="118" cy="52" r="18" fill="#2F8F4E"/>
  <path d="M110 52 L116 58 L128 44" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
SVG;

        case 'consumer':
            
            return <<<SVG
<svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
  <circle cx="80" cy="80" r="72" fill="#C2571F" opacity="0.10"/>
  <circle cx="80" cy="80" r="52" fill="#C2571F" opacity="0.14"/>
  <path d="M46 82 L114 82 L104 128 Q80 134 56 128 Z" fill="#E7A93A"/>
  <path d="M46 82 L114 82 L110 94 L50 94 Z" fill="#C9892B"/>
  <path d="M60 82 Q66 56 80 56 Q94 56 100 82" stroke="#8A5A22" stroke-width="5" fill="none" stroke-linecap="round"/>
  <circle cx="64" cy="72" r="11" fill="#D9642E"/>
  <circle cx="82" cy="66" r="12" fill="#2F8F4E"/>
  <circle cx="100" cy="73" r="10" fill="#E14C3D"/>
  <path d="M82 54 q3 -6 8 -6" stroke="#1F7A3D" stroke-width="3" fill="none" stroke-linecap="round"/>
</svg>
SVG;

        case 'admin':
         
            return <<<SVG
<svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
  <circle cx="80" cy="80" r="72" fill="#4F46E5" opacity="0.10"/>
  <circle cx="80" cy="80" r="52" fill="#4F46E5" opacity="0.14"/>
  <path d="M80 34 L118 48 V80 C118 106 102 122 80 130 C58 122 42 106 42 80 V48 Z" fill="#4F46E5"/>
  <path d="M80 34 L118 48 V80 C118 106 102 122 80 130 Z" fill="#6D64F0" opacity="0.55"/>
  <path d="M64 80 L76 92 L100 64" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  <g fill="#E7A93A">
    <rect x="30" y="118" width="8" height="16" rx="2"/>
    <rect x="42" y="108" width="8" height="26" rx="2"/>
    <rect x="54" y="122" width="8" height="12" rx="2"/>
  </g>
</svg>
SVG;

        case 'landing':
        default:
            
            return <<<SVG
<svg viewBox="0 0 200 160" xmlns="http://www.w3.org/2000/svg">
  <circle cx="150" cy="40" r="22" fill="#E7A93A" opacity="0.9" class="anim-sun-glow"/>
  <g stroke="#E7A93A" stroke-width="3" stroke-linecap="round" opacity="0.5" class="anim-sun-rays">
    <line x1="150" y1="4" x2="150" y2="14"/>
    <line x1="186" y1="40" x2="176" y2="40"/>
    <line x1="176" y1="14" x2="169" y2="21"/>
  </g>
  <path d="M0 120 Q50 96 100 118 T200 112 V160 H0 Z" fill="#2F8F4E" opacity="0.18" class="anim-drift-back"/>
  <path d="M0 136 Q60 118 120 134 T200 128 V160 H0 Z" fill="#1F7A3D" opacity="0.22" class="anim-drift-front"/>
  <g class="anim-sway-1">
    <path d="M60 140 C60 118 60 106 60 92" stroke="#1F7A3D" stroke-width="6" stroke-linecap="round" fill="none"/>
    <path d="M60 118 C44 118 36 106 38 92 C54 92 60 104 60 118 Z" fill="#2F8F4E"/>
    <path d="M60 104 C76 104 84 92 82 78 C66 78 60 90 60 104 Z" fill="#3FA85F"/>
  </g>
  <g class="anim-sway-2">
    <path d="M130 148 C130 128 130 118 130 106" stroke="#8A5A22" stroke-width="5" stroke-linecap="round" fill="none"/>
    <circle cx="130" cy="100" r="9" fill="#D9642E" class="anim-bob-1"/>
    <circle cx="118" cy="110" r="8" fill="#E7A93A" class="anim-bob-2"/>
    <circle cx="142" cy="112" r="8" fill="#C2571F" class="anim-bob-3"/>
  </g>
</svg>
SVG;
    }
}
?>
