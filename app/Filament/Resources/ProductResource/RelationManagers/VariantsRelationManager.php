<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Варианти';

    protected static ?string $modelLabel = 'вариант';

    protected static ?string $pluralModelLabel = 'варианти';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Име')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('Цена')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\TextInput::make('extra_price_multiplier')
                    ->label('Множител за добавки')
                    ->helperText('Множител за цената на добавките при този размер')
                    ->numeric()
                    ->default(1.00)
                    ->minValue(0.01)
                    ->step(0.01)
                    ->required(),
                Forms\Components\TextInput::make('size_label')
                    ->label('Размер')
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight')
                    ->label('Тегло')
                    ->maxLength(255),
                Forms\Components\TextInput::make('diameter')
                    ->label('Диаметър')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('extra_price_multiplier')
                    ->label('Множ. добавки'),
                Tables\Columns\TextColumn::make('size_label')
                    ->label('Размер'),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Тегло'),
                Tables\Columns\TextColumn::make('diameter')
                    ->label('Диаметър'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
