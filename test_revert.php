<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');

$content = str_replace(
    "['__CREATE__' => '<span style=\"color: #eab308; font-weight: bold;\">➕ إضافة رحلة جديدة</span>'] + Batch::query()",
    "Batch::query()",
    $content
);

$content = str_replace(
    "->searchable()\n                            ->allowHtml()",
    "->searchable()",
    $content
);

$content = str_replace(
    "->searchable()\n+                            ->allowHtml()", // If it was with extra space
    "->searchable()",
    $content
);

$content = preg_replace(
    "/->afterStateUpdated.*?->suffixActions\(\[\]\)\n/s",
    "",
    $content
);

$content = str_replace("->suffixActions([])\n", "", $content);
$content = str_replace("->allowHtml()\n", "", $content);

file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Reverted successfully\n";
