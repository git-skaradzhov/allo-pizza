<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?string $modelLabel = 'промо код';

    protected static ?string $pluralModelLabel = 'промо кодове';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна информация')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('Отстъпка')
                    ->schema([
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Сума отстъпка')
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Процент отстъпка')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('minimum_order_amount')
                            ->label('Минимална поръчка')
                            ->numeric()
                            ->prefix('€'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Използване')
                    ->schema([
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Лимит на използване')
                            ->numeric(),
                        Forms\Components\TextInput::make('used_count')
                            ->label('Използван')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Сума')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('Процент')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Използван')
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('Лимит'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Край')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
