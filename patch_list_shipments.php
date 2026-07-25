<?php
$content = file_get_contents('app/Filament/Resources/Shipments/Pages/ListShipments.php');

$useStmts = <<<EOT
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
EOT;

$subheading = <<<EOT
    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString('<style>.fi-ta-panel { background-color: transparent !important; box-shadow: none !important; border: none !important; ring: 0 !important; } .fi-ta-content { background-color: transparent !important; } .dark .fi-ta-panel { background-color: transparent !important; ring: 0 !important; border: none !important; box-shadow: none !important; } .fi-ta-header-toolbar { background-color: transparent !important; }</style>');
    }
EOT;

if (!str_contains($content, 'getSubheading')) {
    $content = preg_replace('/class ListShipments extends ListRecords\n\{/', "class ListShipments extends ListRecords\n{\n$subheading\n", $content);
    $content = preg_replace('/use Filament\\\\Resources\\\\Pages\\\\ListRecords;/', "use Filament\\Resources\\Pages\\ListRecords;\n$useStmts", $content);
    file_put_contents('app/Filament/Resources/Shipments/Pages/ListShipments.php', $content);
}
echo "Done\n";
