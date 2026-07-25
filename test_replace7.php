<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "->createOptionAction(fn (\\Filament\\Forms\\Components\\Actions\\Action \$action) => \$action->extraAttributes(['style' => 'display: none !important;']))",
    "->suffixActions([])",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
