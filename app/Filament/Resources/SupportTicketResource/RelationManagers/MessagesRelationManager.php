<?php

namespace App\Filament\Resources\SupportTicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = '💬 Conversación';

    protected static ?string $modelLabel = 'Mensaje';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->label('Mensaje')
                    ->required()
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('De')
                    ->weight('bold')
                    ->color(fn($record) => $record->is_admin_reply ? 'primary' : 'gray')
                    ->description(fn($record) => $record->is_admin_reply ? '🛡️ Soporte' : '👤 Usuario'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\Action::make('viewUserTransactions')
                    ->label('Transacciones de usuario')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->modalHeading('Transacciones de Monedas del Usuario')
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false) // removes the submit button
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn() => view('filament.components.user-coin-transactions-modal', [
                        'userId' => $this->ownerRecord->user_id,
                    ])),

                Tables\Actions\Action::make('adminReply')
                    ->label('Responder como Soporte')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Tu respuesta')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->action(function (array $data) {
                        $this->ownerRecord->messages()->create([
                            'user_id' => auth()->id(),
                            'message' => $data['message'],
                            'is_admin_reply' => true,
                        ]);

                        if ($this->ownerRecord->status === 'open') {
                            $this->ownerRecord->update([
                                'status' => 'in_progress',
                                'assigned_to' => auth()->id(),
                            ]);
                        }

                        $this->ownerRecord->touch();
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'asc');
    }
}
