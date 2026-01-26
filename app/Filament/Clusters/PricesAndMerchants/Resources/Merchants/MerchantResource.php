<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Merchants;

use App\Filament\Clusters\PricesAndMerchants\PricesAndMerchantsCluster;
use App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Pages\ManageMerchants;
use App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Schemas\MerchantForm;
use App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Schemas\MerchantInfolist;
use App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Tables\MerchantsTable;
use App\Models\Merchant\Merchant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = PricesAndMerchantsCluster::class;

    protected static ?string $modelLabel = 'Comercio';

    protected static ?string $pluralModelLabel = 'Comercios';

    protected static ?string $navigationLabel = 'Comercios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MerchantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantsTable::configure($table);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMerchants::route('/'),
        ];
    }
}
