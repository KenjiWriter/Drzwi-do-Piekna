<?php

namespace App\Filament\Resources\TreatmentCategories;

use App\Filament\Resources\TreatmentCategories\Pages\CreateTreatmentCategory;
use App\Filament\Resources\TreatmentCategories\Pages\EditTreatmentCategory;
use App\Filament\Resources\TreatmentCategories\Pages\ListTreatmentCategories;
use App\Filament\Resources\TreatmentCategories\Schemas\TreatmentCategoryForm;
use App\Filament\Resources\TreatmentCategories\Tables\TreatmentCategoriesTable;
use App\Models\TreatmentCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TreatmentCategoryResource extends Resource
{
    protected static ?string $model = TreatmentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Treści';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'obszar';

    protected static ?string $pluralModelLabel = 'obszary';

    protected static ?string $navigationLabel = 'Obszary zabiegów';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TreatmentCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreatmentCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTreatmentCategories::route('/'),
            'create' => CreateTreatmentCategory::route('/create'),
            'edit' => EditTreatmentCategory::route('/{record}/edit'),
        ];
    }
}
