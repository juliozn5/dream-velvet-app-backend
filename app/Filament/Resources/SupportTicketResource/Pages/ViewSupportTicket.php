<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Gestionar'),

            Actions\Action::make('reply')
                ->label('Responder')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->visible(fn() => $this->record->status !== 'closed')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('Tu respuesta')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000),
                ])
                ->action(function (array $data) {
                    TicketMessage::create([
                        'ticket_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'message' => $data['message'],
                        'is_admin_reply' => true,
                    ]);

                    // Cambiar estado a "en progreso" si estaba abierto
                    if ($this->record->status === 'open') {
                        $this->record->update([
                            'status' => 'in_progress',
                            'assigned_to' => auth()->id(),
                        ]);
                    }

                    $this->record->touch();

                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('markResolved')
                ->label('Marcar Resuelto')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => in_array($this->record->status, ['open', 'in_progress']))
                ->action(fn() => $this->record->markAsResolved()),

            Actions\Action::make('markClosed')
                ->label('Cerrar Ticket')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status !== 'closed')
                ->action(fn() => $this->record->markAsClosed()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Ticket')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Usuario'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('user.role')
                            ->label('Rol')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'modelo' => 'success',
                                'cliente' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Asunto'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Estado')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'open' => 'danger',
                                'in_progress' => 'warning',
                                'resolved' => 'success',
                                'closed' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => \App\Models\SupportTicket::statuses()[$state] ?? $state),
                        Infolists\Components\TextEntry::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'low' => 'gray',
                                'normal' => 'info',
                                'high' => 'warning',
                                'critical' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => \App\Models\SupportTicket::priorities()[$state] ?? $state),
                        Infolists\Components\TextEntry::make('category')
                            ->label('Categoría')
                            ->formatStateUsing(fn(string $state): string => \App\Models\SupportTicket::categories()[$state] ?? $state),
                        Infolists\Components\TextEntry::make('assignedAdmin.name')
                            ->label('Asignado a')
                            ->default('Sin asignar'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última actividad')
                            ->since(),
                    ])->columns(3),

                Infolists\Components\Section::make('💬 Conversación')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('messages')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('De')
                                    ->weight('bold')
                                    ->color(fn($record) => $record->is_admin_reply ? 'primary' : 'gray')
                                    ->suffix(fn($record) => $record->is_admin_reply ? ' (Soporte)' : ' (Usuario)'),
                                Infolists\Components\TextEntry::make('message')
                                    ->label('')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('')
                                    ->since()
                                    ->color('gray'),
                            ])->columns(2),
                    ]),
            ]);
    }
}
