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

    /*
    |--------------------------------------------------------------------------
    | Office numbers printed in messages
    |--------------------------------------------------------------------------
    |
    | The two office numbers the arrival message ends with, so a recipient can
    | reach either end of the route about delivery.
    |
    | Unlike support_whatsapp above these are read, not dialled by a link, so
    | they are written the way a person reads them aloud — with the plus and
    | the spaces. Do not feed these to wa.me without stripping first.
    |
    */

    'whatsapp_dubai' => env('COMPANY_WHATSAPP_DUBAI', '+971 52 153 0190'),

    'whatsapp_beirut' => env('COMPANY_WHATSAPP_BEIRUT', '+961 81 059 063'),

];
