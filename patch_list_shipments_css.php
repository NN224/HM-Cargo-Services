<?php
$content = file_get_contents('app/Filament/Resources/Shipments/Pages/ListShipments.php');

$css = <<<EOT
<style>
/* Remove the big panel background and borders */
.fi-ta-panel { 
    background-color: transparent !important; 
    box-shadow: none !important; 
    border: none !important; 
    --tw-ring-shadow: 0 0 #0000 !important; 
} 
.dark .fi-ta-panel { 
    background-color: transparent !important; 
    --tw-ring-shadow: 0 0 #0000 !important; 
    border: none !important; 
    box-shadow: none !important; 
} 
.fi-ta-content { background-color: transparent !important; border: none !important; } 
.fi-ta-header-toolbar { background-color: transparent !important; }

/* Move actions to the top of the card */
.fi-ta-record {
    display: flex;
    flex-direction: column;
}
.fi-ta-record > div:last-child:has(.fi-ac-action) {
    order: -1;
    margin-bottom: 1rem;
    display: flex;
    justify-content: flex-end;
}
</style>
EOT;

$content = preg_replace("/<style>.*<\/style>/s", $css, $content);

file_put_contents('app/Filament/Resources/Shipments/Pages/ListShipments.php', $content);
echo "Done\n";
