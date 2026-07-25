<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "\$livewire->mountFormComponentAction('data.batch_id', 'createOption');",
    "\$livewire->mountAction('createOption', context: ['schemaComponent' => 'data.batch_id']);",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
