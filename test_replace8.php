<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = str_replace(
    "<span class=\"text-yellow-600 font-bold dark:text-yellow-400\">",
    "<span style=\"color: #eab308; font-weight: bold;\">",
    $content
);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
echo "Replaced successfully\n";
