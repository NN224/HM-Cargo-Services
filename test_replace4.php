<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "['__CREATE__' => '➕ إضافة رحلة جديدة']",
    "['__CREATE__' => '<span class=\"text-yellow-600 font-bold dark:text-yellow-400\">➕ إضافة رحلة جديدة</span>']",
    $content
);
$content = str_replace(
    "->searchable()",
    "->searchable()\n                            ->allowHtml()",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
