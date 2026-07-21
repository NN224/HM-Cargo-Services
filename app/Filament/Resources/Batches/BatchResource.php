<?php

namespace App\Filament\Resources\Batches;

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Filament\Resources\Batches\Pages\BatchReport;
use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\Pages\ViewBatch;
use App\Filament\Resources\Batches\Schemas\BatchForm;
use App\Filament\Resources\Batches\Tables\BatchesTable;
use App\Models\Batch;
use App\Models\Route;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'الرحلات';

    protected static ?string $modelLabel = 'رحلة';

    protected static ?string $pluralModelLabel = 'الرحلات';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return BatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BatchesTable::configure($table);
    }

    /** @return Builder<Batch> */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User || $user->isAdministrator()) {
            return $query;
        }

        // A route is operationally relevant at its origin, transit and final
        // warehouse. A capability may grant an action, never a wider dataset.
        return $query->whereHas('route', fn (Builder $route) => static::scopeRouteQuery($route));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::PriceShipments)
            && parent::canCreate();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Batch
            && static::isVisibleToCurrentUser($record)
            && parent::canView($record);
    }

    public static function canEdit(Model $record): bool
    {
        // Dispatch is the commercial cut-off: ordinary edits stop here.
        // Later corrections belong to the audited administrator flow.
        return $record instanceof Batch
            && static::currentUserCanPrice()
            && static::isVisibleToCurrentUser($record)
            && $record->status === BatchStatus::Open
            && parent::canEdit($record);
    }

    public static function canDispatch(Batch $batch): bool
    {
        return static::currentUserCanPrice()
            && static::isVisibleToCurrentUser($batch)
            && $batch->status === BatchStatus::Open;
    }

    public static function scopeRouteQuery(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->isAdministrator()) {
            return $query;
        }

        return $query->where(fn (Builder $warehouses) => $warehouses
            ->where('origin_warehouse_id', $user->warehouse_id ?? 0)
            ->orWhere('transit_warehouse_id', $user->warehouse_id ?? 0)
            ->orWhere('destination_warehouse_id', $user->warehouse_id ?? 0));
    }

    /**
     * Whether a user may operate on a batch travelling this route.
     *
     * @param  ?User  $user  Defaults to the current request's user for every
     *                       existing Filament call site. BatchIntakeService
     *                       passes its own $actor explicitly instead, matching
     *                       that service's rule that authorization must be
     *                       checked against the actor it was given, never
     *                       against global auth() state, so the guard is
     *                       testable with an arbitrary actor.
     */
    public static function canUseRoute(Route $route, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->canAccessWarehouse($route->origin_warehouse_id)
            || $user->canAccessWarehouse($route->transit_warehouse_id)
            || $user->canAccessWarehouse($route->destination_warehouse_id);
    }

    private static function currentUserCanPrice(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::PriceShipments);
    }

    private static function isVisibleToCurrentUser(Batch $batch): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        return static::canUseRoute($batch->route);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatches::route('/'),
            'create' => CreateBatch::route('/create'),
            'view' => ViewBatch::route('/{record}'),
            'edit' => EditBatch::route('/{record}/edit'),
            'report' => BatchReport::route('/{record}/report'),
        ];
    }
}
