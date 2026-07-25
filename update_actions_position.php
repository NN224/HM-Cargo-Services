<?php
$content = file_get_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php');

// Add the use statement if missing
if (!str_contains($content, 'use Filament\Tables\Enums\RecordActionsPosition;')) {
    $content = preg_replace('/use Filament\\\\Tables\\\\Table;/', "use Filament\\Tables\\Table;\nuse Filament\\Tables\\Enums\\RecordActionsPosition;", $content);
}

// Add the actionsPosition
if (!str_contains($content, '->actionsPosition(')) {
    $content = str_replace(
        "->actions([",
        "->actionsPosition(RecordActionsPosition::BeforeColumns)\n            ->actions([",
        $content
    );
}

file_put_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php', $content);
echo "Done\n";
