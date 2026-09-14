<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsPostResource\Pages;
use App\Filament\Support\ContentBlocks;
use App\Models\NewsPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $modelLabel = 'Uudis';

    protected static ?string $pluralModelLabel = 'Uudised';

    protected static ?string $navigationLabel = 'Uudised (blog)';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Üldine')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Pealkiri')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get, $context) {
                            if ($context === 'create' && ! $get('slug')) {
                                $set('slug', Str::slug((string) $state));
                            }
                        })
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->helperText('Näiteks: uus-seemnehooaeg-2026. Leht avaneb aadressil /uudised/{slug}.')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Textarea::make('excerpt')
                        ->label('Lühikirjeldus (nimekirja jaoks)')
                        ->rows(3)
                        ->helperText('Näidatakse uudiste nimekirjas kaardi peal ja meta descriptionis.')
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('cover_image')
                        ->label('Kaanepilt')
                        ->image()
                        ->disk('public')
                        ->directory('news-covers')
                        ->imageEditor()
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Avaldamise aeg')
                        ->helperText('Uudis muutub avalikult nähtavaks alles siis, kui see aeg on käes.')
                        ->default(now())
                        ->native(false)
                        ->displayFormat('d.m.Y H:i')
                        ->seconds(false),

                    Forms\Components\TextInput::make('author')
                        ->label('Autor')
                        ->maxLength(255),

                    Forms\Components\Toggle::make('status')
                        ->label('Aktiivne')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Sisu')
                ->schema([
                    Forms\Components\Builder::make('content_blocks')
                        ->label('')
                        ->blocks(ContentBlocks::all())
                        ->addActionLabel('Lisa blokk')
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->blockNumbers(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->height(40)
                    ->width(60),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('slug')->searchable()->toggleable(),
                Tables\Columns\IconColumn::make('status')->boolean()->label('Aktiivne'),
                Tables\Columns\TextColumn::make('published_at')->dateTime('d.m.Y H:i')->label('Avaldatud')->sortable(),
                Tables\Columns\TextColumn::make('author')->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y H:i')->label('Muudetud')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNewsPosts::route('/'),
            'create' => Pages\CreateNewsPost::route('/create'),
            'edit'   => Pages\EditNewsPost::route('/{record}/edit'),
        ];
    }
}
