<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "->pluck('reference', 'id')\n                                ->all())",
    "->pluck('reference', 'id')\n                                ->all() + ['__CREATE__' => '➕ إضافة رحلة جديدة'])",
    $content
);
$content = str_replace(
    "->live()\n                            ->createOptionForm([",
    "->live()\n                            ->afterStateUpdated(function (\\Filament\\Forms\\Set \$set, \$state, \\Livewire\\Component \$livewire) {\n                                if (\$state === '__CREATE__') {\n                                    \$set('batch_id', null);\n                                    \$livewire->mountFormComponentAction('data.batch_id', 'createOption');\n                                }\n                            })\n                            ->createOptionForm([",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
