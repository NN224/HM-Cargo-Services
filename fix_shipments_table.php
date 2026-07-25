<?php
$content = file_get_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php');

$useStatements = <<<EOT
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
EOT;

// I'll just rewrite the whole file cleanly to avoid issues.
