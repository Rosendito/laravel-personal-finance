<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Products;

use App\Filament\Clusters\PricesAndMerchants\PricesAndMerchantsCluster;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages\CreateProduct;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages\EditProduct;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages\ListProducts;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Pages\ViewProduct;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Schemas\ProductForm;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Clusters\PricesAndMerchants\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = PricesAndMerchantsCluster::class;

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
