<?php

namespace App\Filament\Resources\InvitationResource\Pages;

use App\Filament\Resources\InvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
{
    // Paksa user_id pake ID orang yang lagi login di dashboard
    $data['user_id'] = auth()->id();
 
    return $data;
}
}
