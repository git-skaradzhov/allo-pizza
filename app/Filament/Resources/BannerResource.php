<?php

namespace App\Filament\Resources;

use App\Enums\BannerPosition;
use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?string $modelLabel = 'банер';

    protected static ?string $pluralModelLabel = 'банери';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subtitle')
                            ->label('Подзаглавие')
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image')
                            ->label(fn (Forms\Get $get): string => $get('position') === BannerPosition::HomeHero->value
                                ? 'Изображение (десктоп)'
                                : 'Изображение')
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->helperText(fn (Forms\Get $get): string => $get('position') === BannerPosition::HomeHero->value
                                ? 'Препоръчителен размер: 2100×800 px (21:8).'
                                : 'По желание. Ако липсва, началната страница показва цветна промо карта.')
                            ->visibility('public'),
                        Forms\Components\FileUpload::make('mobile_image')
                            ->label('Изображение (мобилен)')
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->helperText('650×650 px (1:1). По желание — ако липсва, се ползва десктопното.')
                            ->visibility('public')
                            ->visible(fn (Forms\Get $get): bool => $get('position') === BannerPosition::HomeHero->value),
                        Forms\Components\Toggle::make('image_only')
                            ->label('Само изображение')
                            ->helperText('Скрива текста и градиента върху hero банера. Използвай, когато текстът е вграден в самото изображение.')
                            ->default(false)
                            ->visible(fn (Forms\Get $get): bool => $get('position') === BannerPosition::HomeHero->value),
                        Forms\Components\TextInput::make('button_text')
                            ->label('Текст на бутона')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('button_url')
                            ->label('URL на бутона')
                            ->helperText('Може да бъде вътрешен линк, напр. /menu или /product/margarita.')
                            ->maxLength(255),
                        Forms\Components\Select::make('position')
                            ->label('Позиция')
                            ->options(collect(BannerPosition::cases())->mapWithKeys(
                                fn (BannerPosition $position) => [$position->value => $position->label()]
                            ))
                            ->required()
                            ->live()
                            ->native(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Подредба')
                            ->numeric()
                            ->default(0),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Начало')
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Край')
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Изображение')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Позиция')
                    ->formatStateUsing(fn (BannerPosition $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активен')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Край')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label('Позиция')
                    ->options(collect(BannerPosition::cases())->mapWithKeys(
                        fn (BannerPosition $position) => [$position->value => $position->label()]
                    )),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
