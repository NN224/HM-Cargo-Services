<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer support
    |--------------------------------------------------------------------------
    |
    | The number a customer reaches from the public tracking page. It was
    | previously written into the page markup, which meant changing it needed
    | a code change and a deploy.
    |
    | Digits only, including the country code and without a leading plus —
    | that is the form wa.me expects.
    |
    */

    'support_whatsapp' => env('COMPANY_SUPPORT_WHATSAPP', '971521616814'),

];
