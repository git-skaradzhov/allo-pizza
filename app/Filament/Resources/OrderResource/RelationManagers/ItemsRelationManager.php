<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OrderItemOptionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Артикули';

    protected static ?string $modelLabel = 'артикул';

    protected static ?string $pluralModelLabel = 'артикули';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Продукт')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('product_name')
                    ->label('Име на продукт')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('variant_name')
                    ->label('Вариант')
                    ->maxLength(255),
                Forms\Components\TextInput::make('quantity')
                    ->label('Количество')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Единична цена')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\TextInput::make('total_price')
                    ->label('Обща цена')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\Textarea::make('note')
                    ->label('Бележка')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('options'))
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Продукт'),
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Вариант'),
                Tables\Columns\TextColumn::make('options_summary')
                    ->label('Добавки')
                    ->html()
                    ->state(function ($record): string {
                        return $record->options
                            ->map(function ($option) {
                                if ($option->option_type === OrderItemOptionType::ExtraAdded) {
                                    return '+ '.e($option->name);
                                }

                                return '<span style="color:#dc2626;font-weight:700;">✕</span> '.e($option->name);
                            })
                            ->implode(', ');
                    })
                    ->placeholder('—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Бележка')
                    ->placeholder('—')
                    ->wrap()
                    ->color(fn (?string $state): ?string => filled($state) ? 'warning' : null)
                    ->weight(fn (?string $state): ?string => filled($state) ? 'bold' : null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Кол.'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Ед. цена')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Общо')
                    ->money('EUR'),
            ])
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
