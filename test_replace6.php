<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "\\Filament\\Forms\\Set \$set",
    "\\Filament\\Schemas\\Components\\Utilities\\Set \$set",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
