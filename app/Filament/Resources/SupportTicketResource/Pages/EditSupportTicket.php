<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markResolved')
                ->label('Marcar Resuelto')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => in_array($this->record->status, ['open', 'in_progress']))
                ->action(function () {
                    $this->record->markAsResolved();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('markClosed')
                ->label('Cerrar Ticket')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status !== 'closed')
                ->action(function () {
                    $this->record->markAsClosed();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
