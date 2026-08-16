<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreSettingResource\Pages;
use App\Models\Category;
use App\Models\StoreSetting;
use App\Support\DeliveryZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreSettingResource extends Resource
{
    protected static ?string $model = StoreSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $modelLabel = 'настройки на магазина';

    protected static ?string $pluralModelLabel = 'настройки на магазина';

    protected static ?string $navigationLabel = 'Магазин';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна информация')
                    ->schema([
                        Forms\Components\TextInput::make('store_name')
                            ->label('Име на магазина')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('store_phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('store_phone_secondary')
                            ->label('Допълнителен телефон')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('store_email')
                            ->label('Имейл')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('store_address')
                            ->label('Адрес')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('store_lat')
                            ->label('Географска ширина')
                            ->numeric(),
                        Forms\Components\TextInput::make('store_lng')
                            ->label('Географска дължина')
                            ->numeric(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Зони за доставка')
                    ->description('Задайте района на картата и двете цени — вътре и извън полигона. Не се използва радиус в километри.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('delivery_inside_price')
                                    ->label('Доставка в района')
                                    ->helperText('Адрес вътре в очертания полигон.')
                                    ->numeric()
                                    ->prefix('€')
                                    ->required()
                                    ->default(2),
                                Forms\Components\TextInput::make('delivery_outside_price')
                                    ->label('Доставка извън района')
                                    ->helperText('Адрес извън полигона, но в зоната на обслужване.')
                                    ->numeric()
                                    ->prefix('€')
                                    ->required()
                                    ->default(3),
                            ]),
                        Forms\Components\Hidden::make('delivery_zone_polygon')
                            ->default(DeliveryZone::defaultPolygon()),
                        Forms\Components\ViewField::make('delivery_zone_map')
                            ->label('Карта на района')
                            ->view('filament.forms.components.delivery-zone-map')
                            ->viewData(fn (Get $get): array => [
                                'statePath' => 'data.delivery_zone_polygon',
                                'storeLat' => $get('store_lat') ?? 43.8407475,
                                'storeLng' => $get('store_lng') ?? 25.9549665,
                                'googleMapsKey' => config('services.google_maps.key'),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('free_delivery_over')
                            ->label('Безплатна доставка над')
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('minimum_order_amount')
                            ->label('Минимална поръчка')
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('average_delivery_time')
                            ->label('Средно време за доставка (мин.)')
                            ->numeric()
                            ->suffix('мин.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_store_open')
                            ->label('Магазинът е отворен')
                            ->default(true),
                        Forms\Components\Textarea::make('closed_message')
                            ->label('Съобщение при затворен магазин')
                            ->helperText('Оставете празно за автоматично съобщение с динамично работно време от настройките.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('4+1 промоция')
                    ->description('При активна промоция на всеки 5-ти продукт от избраните категории най-евтиният става безплатен. Не се комбинира с промо код.')
                    ->schema([
                        Forms\Components\Toggle::make('bundle_promotion_enabled')
                            ->label('Промоцията е активна')
                            ->default(true),
                        Forms\Components\Select::make('bundle_promotion_category_ids')
                            ->label('Категории')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => Category::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->helperText('За категория Пици важи само размер 30 см. За останалите избрани категории — всички варианти. Празен избор = само категория Пици.')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('SEO и аналитика')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title_suffix')
                            ->label('Суфикс на заглавията')
                            ->default('| Allo! Pizza')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('seo_default_description')
                            ->label('Стандартно meta описание')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('seo_default_image')
                            ->label('Стандартно OG изображение')
                            ->image()
                            ->directory('seo')
                            ->disk('public')
                            ->visibility('public'),
                        Forms\Components\Textarea::make('organization_description')
                            ->label('Описание на организацията')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('organization_logo')
                            ->label('Лого за schema.org')
                            ->image()
                            ->directory('seo')
                            ->disk('public')
                            ->visibility('public'),
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('Google Analytics 4 ID')
                            ->placeholder('G-XXXXXXXXXX'),
                        Forms\Components\TextInput::make('google_tag_manager_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('GTM-XXXXXXX'),
                        Forms\Components\TextInput::make('google_site_verification')
                            ->label('Google Search Console verification'),
                        Forms\Components\TextInput::make('bing_site_verification')
                            ->label('Bing Webmaster verification'),
                        Forms\Components\TextInput::make('meta_pixel_id')
                            ->label('Meta Pixel ID')
                            ->placeholder('1385265280465389'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store_name')
                    ->label('Име'),
                Tables\Columns\TextColumn::make('delivery_inside_price')
                    ->label('В района')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('delivery_outside_price')
                    ->label('Извън района')
                    ->money('EUR'),
                Tables\Columns\IconColumn::make('is_store_open')
                    ->label('Отворен')
                    ->boolean(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStoreSetting::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
