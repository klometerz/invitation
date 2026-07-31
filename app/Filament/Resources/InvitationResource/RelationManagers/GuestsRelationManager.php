<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Imports\GuestsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class GuestsRelationManager extends RelationManager
{
    // 1. Pastikan property ini ada dan tipenya string
    protected static string $relationship = 'guests';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('city')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guest_code')
                    ->label('Kode')
                    ->copyable(),
                Tables\Columns\ViewColumn::make('qr_code')
                    ->label('QR')
                    ->view('filament.tables.columns.guest-qr'), 
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                
                // TOMBOL IMPORT EXCEL
                Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('success')
                    ->form([
                        FileUpload::make('attachment')
                            ->label('Pilih File Excel')
                            ->directory('temp-imports')
                            ->storeFiles(true)
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel', 
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                                'text/csv'
                            ]),
                    ])
                    ->action(function (array $data) {
                        $filePath = is_array($data['attachment']) 
                            ? array_key_first($data['attachment']) 
                            : $data['attachment'];

                        if (!$filePath) {
                            Notification::make()->title('File tidak ditemukan')->danger()->send();
                            return;
                        }

                        $fullPath = Storage::disk('public')->path($filePath);

                        try {
                            Excel::import(new GuestsImport($this->getOwnerRecord()->id), $fullPath);
                            Storage::disk('public')->delete($filePath);

                            Notification::make()
                                ->title('Berhasil!')
                                ->description('Data tamu berhasil di-import.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Import')
                                ->description('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Action::make('printLabel')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('admin.guests.print', ['id' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}