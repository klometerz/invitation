<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThemeResource\Pages;
use App\Filament\Resources\ThemeResource\RelationManagers;
use App\Models\Theme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

   public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Katalog Tema')
                ->description('Input desain undangan yang tersedia untuk customer.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Tema')
                        ->required()
                        ->placeholder('Contoh: Modern Elegant')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('Slug Tema')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('modern-elegant'),

                    TextInput::make('svelte_path')
                        ->label('Path File Svelte')
                        ->required()
                        ->placeholder('Contoh: Wedding')
                        ->helperText('Nama file di Resources/js/Pages/ (Tanpa .svelte)'),

                    FileUpload::make('preview_image')
                        ->label('Gambar Preview')
                        ->directory('theme-previews')
                        ->image(),

                    Toggle::make('is_premium')
                        ->label('Tema Premium')
                        ->default(false),
                ])->columns(2)
        ]);
}

   public static function table(Tables\Table $table): Tables\Table
{
    return $table
        ->columns([
            Tables\Columns\ImageColumn::make('preview_image')->label('Preview'),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('svelte_path'),
            Tables\Columns\IconColumn::make('is_premium')->boolean(),
        ]);
}

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit' => Pages\EditTheme::route('/{record}/edit'),
        ];
    }
}
