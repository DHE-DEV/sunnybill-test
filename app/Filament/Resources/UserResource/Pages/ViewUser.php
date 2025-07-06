<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Benutzerinformationen')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('email')
                            ->label('E-Mail')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('phone')
                            ->label('Telefon')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Nicht angegeben'),

                        Infolists\Components\TextEntry::make('department')
                            ->label('Abteilung')
                            ->icon('heroicon-o-building-office')
                            ->placeholder('Nicht angegeben'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Berechtigung & Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('role')
                            ->label('Rolle')
                            ->formatStateUsing(fn (string $state): string => \App\Models\User::getRoles()[$state] ?? $state)
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'admin' => 'danger',
                                'manager' => 'warning',
                                'user' => 'success',
                                'viewer' => 'gray',
                                default => 'gray'
                            }),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        Infolists\Components\IconEntry::make('email_verified_at')
                            ->label('E-Mail verifiziert')
                            ->boolean()
                            ->getStateUsing(fn ($record) => $record->email_verified_at !== null)
                            ->trueIcon('heroicon-o-shield-check')
                            ->falseIcon('heroicon-o-shield-exclamation')
                            ->trueColor('success')
                            ->falseColor('warning'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Aktivität')
                    ->schema([
                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label('Letzte Anmeldung')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Noch nie angemeldet')
                            ->icon('heroicon-o-clock'),

                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('E-Mail verifiziert am')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nicht verifiziert')
                            ->icon('heroicon-o-shield-check'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Zuletzt geändert')
                            ->dateTime('d.m.Y H:i')
                            ->icon('heroicon-o-pencil'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Notizen')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Keine Notizen vorhanden')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record->notes)),
            ]);
    }
}