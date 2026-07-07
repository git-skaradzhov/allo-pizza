<?php

namespace App\Filament\Forms;

use Filament\Forms;

class SeoFormSection
{
    public static function make(bool $collapsed = true): Forms\Components\Section
    {
        return Forms\Components\Section::make('SEO')
            ->schema([
                Forms\Components\TextInput::make('seo_title')
                    ->label('Meta заглавие')
                    ->maxLength(255)
                    ->helperText('Препоръчително до 60 символа. Суфиксът на сайта се добавя автоматично.'),
                Forms\Components\TextInput::make('focus_keyword')
                    ->label('Фокус ключова дума')
                    ->maxLength(255),
                Forms\Components\Textarea::make('seo_description')
                    ->label('Meta описание')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Препоръчително до 160 символа.'),
                Forms\Components\TextInput::make('canonical_url')
                    ->label('Canonical URL')
                    ->url()
                    ->helperText('Оставете празно за автоматично генериране.'),
                Forms\Components\Select::make('meta_robots')
                    ->label('Robots')
                    ->options([
                        '' => 'По подразбиране (index, follow)',
                        'index,follow' => 'index, follow',
                        'noindex,follow' => 'noindex, follow',
                        'index,nofollow' => 'index, nofollow',
                        'noindex,nofollow' => 'noindex, nofollow',
                    ]),
                Forms\Components\Fieldset::make('Open Graph')
                    ->schema([
                        Forms\Components\TextInput::make('og_title')
                            ->label('OG заглавие')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('og_description')
                            ->label('OG описание')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('og_image')
                            ->label('OG изображение')
                            ->image()
                            ->directory('seo')
                            ->disk('public')
                            ->visibility('public'),
                    ])
                    ->columns(2),
                Forms\Components\Fieldset::make('Twitter')
                    ->schema([
                        Forms\Components\TextInput::make('twitter_title')
                            ->label('Twitter заглавие')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('twitter_description')
                            ->label('Twitter описание')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('twitter_image')
                            ->label('Twitter изображение')
                            ->image()
                            ->directory('seo')
                            ->disk('public')
                            ->visibility('public'),
                    ])
                    ->columns(2),
                Forms\Components\Placeholder::make('seo_preview')
                    ->label('SEO преглед')
                    ->content(function (Forms\Get $get): string {
                        $title = $get('seo_title') ?: 'Заглавие на страницата';
                        $description = $get('seo_description') ?: 'Кратко описание, което ще се покаже в резултатите от търсене.';

                        return "{$title}\n{$description}";
                    })
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsed($collapsed);
    }
}
