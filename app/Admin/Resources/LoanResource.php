<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Admin\Resources\LoanResource\Pages\ManageLoans;
use App\Enums\LoanType;
use App\Enums\Status;
use App\Models\Loan;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationGroup = 'Financial Accounts';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('provider')
                    ->required()
                    ->string()
                    ->maxLength(191),
                TextInput::make('account_no')
                    ->maxLength(255),
                Select::make('type')
                    ->options(LoanType::class)
                    ->required(),
                TextInput::make('principal_amount')
                    ->mask(RawJs::make('$money($input)'))
                    ->prefixIcon('heroicon-o-currency-rupee')
                    ->stripCharacters(',')->numeric()->inputMode('decimal')
                    ->minValue(0.1)
                    ->required(),
                TextInput::make('interest_rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Annual, reducing balance'),
                TextInput::make('emi_amount')
                    ->label('EMI Amount')
                    ->mask(RawJs::make('$money($input)'))
                    ->prefixIcon('heroicon-o-currency-rupee')
                    ->stripCharacters(',')->numeric()->inputMode('decimal')
                    ->minValue(0.1)
                    ->required()
                    ->helperText(fn (Get $get): ?string => $get('total_emis')
                        ? sprintf('%s / %s EMIs paid', $get('emis_paid'), $get('total_emis'))
                        : null),
                TextInput::make('total_emis')
                    ->label('Total EMIs')
                    ->reactive()
                    ->numeric(),
                TextInput::make('emis_paid')
                    ->label('EMIs Paid')
                    ->numeric()
                    ->reactive()
                    ->default(0),
                DatePicker::make('start_date')
                    ->native(false)
                    ->default(today())
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Set $set): mixed => $set('next_emi_due', Carbon::parse($state)->addMonth()))
                    ->required(),
                DatePicker::make('next_emi_due')
                    ->label('Next EMI Due')
                    ->native(false)
                    ->default(today()->addMonth())
                    ->required(),
                Toggle::make('autopay')
                    ->label('Auto Pay')
                    ->default(false)
                    ->helperText('Enable to automatically pay EMIs on due date'),
                Select::make('status')
                    ->options(Status::class)
                    ->required()
                    ->default('active'),
                RichEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('account_no')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('principal_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('interest_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('emi_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_emis')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('emis_paid')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('next_emi_due')
                    ->date()
                    ->sortable(),
                IconColumn::make('autopay')
                    ->boolean(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLoans::route('/'),
        ];
    }
}
