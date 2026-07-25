<?php
$content = file_get_contents('app/Filament/Pages/ReceiveIntoBatch.php');
$content = preg_replace("/\\s+->createOptionForm\(\\[/", "\n                            ->createOptionForm([", $content);
file_put_contents('app/Filament/Pages/ReceiveIntoBatch.php', $content);
