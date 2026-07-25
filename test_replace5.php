<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "->all() + ['__CREATE__' => '<span class=\"text-yellow-600 font-bold dark:text-yellow-400\">➕ إضافة رحلة جديدة</span>'])",
    "->all())",
    $content
);
$content = str_replace(
    "->options(fn (): array => Batch::query()",
    "->options(fn (): array => ['__CREATE__' => '<span class=\"text-yellow-600 font-bold dark:text-yellow-400\">➕ إضافة رحلة جديدة</span>'] + Batch::query()",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
