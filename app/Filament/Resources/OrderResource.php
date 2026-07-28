<?php

namespace App\Filament\Resources;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Поръчки';

    protected static ?string $modelLabel = 'поръчка';

    protected static ?string $pluralModelLabel = 'поръчки';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Поръчка')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Номер')
                            ->required()
                            ->maxLength(255)
                            ->default(fn (): string => Order::generateOrderNumber())
                            ->disabled(fn (?Order $record): bool => $record !== null)
                            ->dehydrated(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(collect(OrderStatus::cases())->mapWithKeys(
                                fn (OrderStatus $status) => [$status->value => $status->label()]
                            ))
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('customer_id')
                            ->label('Клиент')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Данни за клиента')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Име')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_email')
                            ->label('Имейл')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Телефон')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Доставка')
                    ->schema([
                        Forms\Components\Select::make('delivery_type')
                            ->label('Тип')
                            ->options(collect(DeliveryType::cases())->mapWithKeys(
                                fn (DeliveryType $type) => [$type->value => $type->label()]
                            ))
                            ->required()
                            ->live()
                            ->native(false),
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Адрес')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get): bool => $get('delivery_type') === DeliveryType::Delivery->value)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('delivery_lat')
                            ->label('Географска ширина')
                            ->numeric()
                            ->visible(fn (Forms\Get $get): bool => $get('delivery_type') === DeliveryType::Delivery->value),
                        Forms\Components\TextInput::make('delivery_lng')
                            ->label('Географска дължина')
                            ->numeric()
                            ->visible(fn (Forms\Get $get): bool => $get('delivery_type') === DeliveryType::Delivery->value),
                        Forms\Components\TextInput::make('delivery_price')
                            ->label('Цена на доставка')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        Forms\Components\Toggle::make('delivery_quote_required')
                            ->label('Доставката се уточнява допълнително')
                            ->inline(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Плащане')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Метод')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(
                                fn (PaymentMethod $method) => [$method->value => $method->label()]
                            ))
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Междинна сума')
                            ->required()
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('discount')
                            ->label('Отстъпка')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        Forms\Components\TextInput::make('total')
                            ->label('Общо')
                            ->required()
                            ->numeric()
                            ->prefix('€'),
                    ])
                    ->columns(4),
                Forms\Components\Section::make('Бележки')
                    ->schema([
                        Forms\Components\Textarea::make('customer_note')
                            ->label('Бележка от клиента')
                            ->rows(3),
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Админ бележка')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::New => 'info',
                        OrderStatus::Accepted => 'primary',
                        OrderStatus::Preparing => 'warning',
                        OrderStatus::OnDelivery => 'warning',
                        OrderStatus::Completed => 'success',
                        OrderStatus::Cancelled => 'danger',
                    }),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->label('Доставка')
                    ->formatStateUsing(fn (DeliveryType $state): string => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')
                    ->label('Общо')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $status) => [$status->value => $status->label()]
                    )),
                Tables\Filters\SelectFilter::make('delivery_type')
                    ->label('Тип доставка')
                    ->options(collect(DeliveryType::cases())->mapWithKeys(
                        fn (DeliveryType $type) => [$type->value => $type->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('accept')
                    ->label('Приета')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::New)
                    ->requiresConfirmation()
                    ->modalHeading('Приемане на поръчка')
                    ->modalDescription(fn (Order $record): string => "Сигурни ли сте, че искате да приемете поръчка {$record->order_number}?")
                    ->action(fn (Order $record) => $record->update(['status' => OrderStatus::Accepted])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
