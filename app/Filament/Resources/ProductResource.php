<?php

namespace App\Filament\Resources;

use App\Filament\Forms\SeoFormSection;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Services\ProductImageProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?string $modelLabel = 'продукт';

    protected static ?string $pluralModelLabel = 'продукти';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна информация')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Име')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('short_description')
                            ->label('Кратко описание')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Основно изображение')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('products')
                            ->disk('public')
                            ->visibility('public')
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, ?Product $record, Get $get): string {
                                $product = $record ?? new Product;
                                $product->slug = $product->slug ?: $get('slug');

                                if (blank($product->slug)) {
                                    throw \Illuminate\Validation\ValidationException::withMessages([
                                        'image' => 'Първо попълнете slug, след това качете снимка.',
                                    ]);
                                }

                                return app(ProductImageProcessor::class)->storeMain($product, $file);
                            })
                            ->deleteUploadedFileUsing(fn (?string $file) => app(ProductImageProcessor::class)->deleteVariants($file))
                            ->helperText('JPG, PNG или WebP. Минимум 1024×1024 px. Запазват се автоматично като WebP.'),
                        Forms\Components\TextInput::make('image_alt')
                            ->label('Alt текст на изображението')
                            ->maxLength(255)
                            ->helperText('Описание за достъпност и SEO. Оставете празно за името на продукта.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Цени')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Базова цена')
                            ->required()
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('old_price')
                            ->label('Стара цена')
                            ->numeric()
                            ->prefix('€'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Статус и етикети')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Видим на сайта')
                            ->helperText('Изключете, за да скриете продукта от менюто и страниците на сайта.')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Препоръчан'),
                        Forms\Components\Toggle::make('is_promo')
                            ->label('Промо'),
                        Forms\Components\Toggle::make('is_new')
                            ->label('Нов'),
                        Forms\Components\CheckboxList::make('newMenuHighlights')
                            ->label('Ново в менюто')
                            ->relationship(
                                name: 'newMenuHighlights',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'),
                            )
                            ->helperText('Включва продукта в страницата /novo-v-menuto.')
                            ->columns(1)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_spicy')
                            ->label('Лют'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Подредба')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Съставки')
                    ->schema([
                        Forms\Components\Select::make('ingredients')
                            ->label('Съставки')
                            ->relationship('ingredients', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
                SeoFormSection::make(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Изображение')
                    ->getStateUsing(fn (Product $record): ?string => product_image_admin_url($record->image, 'small')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Име')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Цена')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Видим на сайта')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Препоръчан')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_promo')
                    ->label('Промо')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_new')
                    ->label('Нов')
                    ->boolean(),
                Tables\Columns\TextColumn::make('newMenuHighlights.title')
                    ->label('Ново в менюто')
                    ->badge()
                    ->limitList(1),
                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Варианти')
                    ->counts('variants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Видим на сайта'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Препоръчан'),
                Tables\Filters\TernaryFilter::make('is_new')
                    ->label('Нов'),
                Tables\Filters\Filter::make('in_new_menu')
                    ->label('В секция Ново в менюто')
                    ->query(fn ($query) => $query->whereHas('newMenuHighlights')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('show')
                        ->label('Покажи на сайта')
                        ->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('hide')
                        ->label('Скрий от сайта')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
