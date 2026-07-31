<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvitationResource\Pages;
use App\Filament\Resources\InvitationResource\RelationManagers;
use App\Models\Invitation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Grid;


class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Inputan Dasar tetap di luar Tabs
            Section::make('Informasi Utama')
                ->schema([
                    TextInput::make('title')->required(),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true)->prefix('v/'),
                    Forms\Components\Select::make('theme_id')
                        ->relationship('theme', 'name')
                        ->required(),
                ])->columns(3),

            // Tab untuk data JSON 'content'
            Tabs::make('Konten Undangan')
                ->tabs([
                    
                    // TAB 1: DATA MEMPELAI
                    Tabs\Tab::make('Mempelai')
                        ->icon('heroicon-o-heart')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    // Mempelai Pria
                                    Section::make('Mempelai Pria')
                                        ->schema([
                                            TextInput::make('content.mempelai.pria.nama_lengkap')->label('Nama Lengkap')->required(),
                                            TextInput::make('content.mempelai.pria.nama_panggilan')->label('Nama Panggilan'),
                                            TextInput::make('content.mempelai.pria.ayah')->label('Nama Ayah'),
                                            TextInput::make('content.mempelai.pria.ibu')->label('Nama Ibu'),
                                            FileUpload::make('content.mempelai.pria.foto_url')->label('Foto Pria')->directory('mempelai'),
                                        ]),
                                    
                                    // Mempelai Wanita
                                    Section::make('Mempelai Wanita')
                                        ->schema([
                                            TextInput::make('content.mempelai.wanita.nama_lengkap')->label('Nama Lengkap')->required(),
                                            TextInput::make('content.mempelai.wanita.nama_panggilan')->label('Nama Panggilan'),
                                            TextInput::make('content.mempelai.wanita.ayah')->label('Nama Ayah'),
                                            TextInput::make('content.mempelai.wanita.ibu')->label('Nama Ibu'),
                                            FileUpload::make('content.mempelai.wanita.foto_url')->label('Foto Wanita')->directory('mempelai'),
                                        ]),
                                ]),
                        ]),

                    // TAB 2: LOKASI & ACARA (Pake Repeater biar bisa nambah banyak acara)
                    Tabs\Tab::make('Acara & Lokasi')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Repeater::make('content.acara')
                                ->label('Daftar Acara')
                                ->schema([
                                    TextInput::make('tipe')->placeholder('Akad Nikah / Resepsi / Ngunduh Mantu')->required(),
                                    DatePicker::make('tanggal')->required(),
                                    TextInput::make('jam')->placeholder('08:00 - Selesai')->required(),
                                    TextInput::make('lokasi')->label('Nama Tempat/Gedung')->required(),
                                    Forms\Components\Textarea::make('alamat')->required(),
                                    TextInput::make('map_url')->label('Link Google Maps')->url(),
                                ])
                                ->columns(2)
                                ->itemLabel(fn (array $state): ?string => $state['tipe'] ?? null),
                        ]),
                    
                    // TAB 3: GALLERY & HERO
                    Tabs\Tab::make('Visual')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            FileUpload::make('content.hero.background_url')->label('Background Utama'),
                            FileUpload::make('content.hero.music_url')->label('Musik Latar (MP3)')->acceptedFileTypes(['audio/mpeg']),
                           Repeater::make('content.gallery')
                                ->label('Foto Gallery')
                                ->schema([
                                    FileUpload::make('image')
                                        ->directory('gallery')
                                        ->image(),
                                ])
                                ->columns(1)
                        ]),
                ])->columnSpanFull(),
        ]);
}

    // ===============================
    // TABLE
    // ===============================
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('slug')->copyable(),
            Tables\Columns\TextColumn::make('theme.name')->label('Tema'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GuestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvitations::route('/'),
            'create' => Pages\CreateInvitation::route('/create'),
            'edit'   => Pages\EditInvitation::route('/{record}/edit'),
        ];
    }
}