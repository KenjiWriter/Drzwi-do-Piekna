<?php

namespace App\Filament\Resources\PriceGroups;

use App\Filament\Resources\PriceGroups\Pages\CreatePriceGroup;
use App\Filament\Resources\PriceGroups\Pages\EditPriceGroup;
use App\Filament\Resources\PriceGroups\Pages\ListPriceGroups;
use App\Filament\Resources\PriceGroups\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\PriceGroups\Schemas\PriceGroupForm;
use App\Filament\Resources\PriceGroups\Tables\PriceGroupsTable;
use App\Models\PriceGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PriceGroupResource extends Resource
{
    protected static ?string $model = PriceGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Treści';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'grupa cennika';

    protected static ?string $pluralModelLabel = 'cennik';

    protected static ?string $navigationLabel = 'Cennik';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PriceGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceGroups::route('/'),
            'create' => CreatePriceGroup::route('/create'),
            'edit' => EditPriceGroup::route('/{record}/edit'),
        ];
    }
}
