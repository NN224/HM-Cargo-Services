<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "->createOptionForm([",
    "->createOptionAction(fn (\\Filament\\Forms\\Components\\Actions\\Action \$action) => \$action->extraAttributes(['class' => 'hidden']))\n                            ->createOptionForm([",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
