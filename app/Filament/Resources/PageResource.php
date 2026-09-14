<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Support\ContentBlocks;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Lehed';

    protected static ?string $modelLabel = 'Leht';

    protected static ?string $pluralModelLabel = 'Lehed';

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
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create' && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn ($record) => $record && $record->slug === 'home')
                        ->dehydrated()
                        ->helperText('URL identifikaator: home, firmast, kontakt, ...'),
                    Forms\Components\Textarea::make('excerpt')
                        ->label('Lühikirjeldus')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('status')
                        ->label('Aktiivne')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Ülemine menüü')
                ->description('Kas ja millise järjekorraga see leht ilmub saidi päise menüüs.')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('in_menu')
                        ->label('Menüüs')
                        ->helperText('Kui sees, ilmub leht saidi ülemises navigatsioonis.')
                        ->default(false)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('menu_position')
                        ->label('Järjekord')
                        ->helperText('Väiksem = eespool. Kasuta 10, 20, 30 — jätab vahed uute jaoks.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(65535)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('menu_label')
                        ->label('Menüü nimi (valikuline)')
                        ->helperText('Kui menüünupp erineb lehe pealkirjast, nt "KKK". Tühjaks jätmisel kasutatakse pealkirja.')
                        ->maxLength(255)
                        ->columnSpan(1),
                ]),

            Forms\Components\Tabs::make('Sisu')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Sisublokid')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Forms\Components\Builder::make('content_blocks')
                                ->label('')
                                ->blocks(ContentBlocks::all())
                                ->addActionLabel('Lisa blokk')
                                ->collapsible()
                                ->collapsed()
                                ->cloneable()
                                ->blockNumbers(false),
                        ]),

                    Forms\Components\Tabs\Tab::make('Hero (avaleht)')
                        ->visible(fn ($record) => $record && $record->slug === 'home')
                        ->schema(static::homepageHeroSchema()),

                    Forms\Components\Tabs\Tab::make('Väärtuspakkumised (avaleht)')
                        ->visible(fn ($record) => $record && $record->slug === 'home')
                        ->schema([
                            Forms\Components\Repeater::make('data.valueProps')
                                ->label('Väärtuspakkumised')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('icon')->label('Ikoon')->placeholder('sprout'),
                                    Forms\Components\TextInput::make('title')->label('Pealkiri'),
                                    Forms\Components\TextInput::make('description')->label('Kirjeldus'),
                                ])
                                ->defaultItems(0)
                                ->reorderable()
                                ->collapsible(),
                        ]),

                    Forms\Components\Tabs\Tab::make('Lugu (avaleht)')
                        ->visible(fn ($record) => $record && $record->slug === 'home')
                        ->schema(static::homepageStorySchema()),

                    Forms\Components\Tabs\Tab::make('Alumine CTA (avaleht)')
                        ->visible(fn ($record) => $record && $record->slug === 'home')
                        ->schema([
                            Forms\Components\TextInput::make('data.cta.title')->label('Pealkiri'),
                            Forms\Components\Textarea::make('data.cta.description')->label('Kirjeldus')->rows(2),
                            Forms\Components\Section::make('Nupp')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('data.cta.button.label')->label('Tekst'),
                                    Forms\Components\TextInput::make('data.cta.button.href')->label('Link'),
                                    Forms\Components\TextInput::make('data.cta.button.icon')->label('Ikoon'),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }


    protected static function homepageHeroSchema(): array
    {
        return [
            Forms\Components\Section::make('Taustapildid (slideshow)')
                ->description('Lisa üks või mitu pilti. Üks = staatiline taust; mitu = automaatne slideshow (~5 sekundit pildi kohta, fade üleminek). Optimaalne suurus 1920×1080 või laiem.')
                ->schema([
                    Forms\Components\Repeater::make('data.hero.images')
                        ->label('Pildid')
                        ->schema([
                            Forms\Components\FileUpload::make('image')
                                ->label('Pilt')
                                ->image()
                                ->disk('public')
                                ->directory('hero')
                                ->imageEditor()
                                ->imageCropAspectRatio('16:9')
                                ->maxSize(5120),
                            Forms\Components\TextInput::make('alt')
                                ->label('Alt-tekst (SEO + accessibility)')
                                ->placeholder('nt: aiapidaja seemned muldas'),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => $state['alt'] ?? 'Pilt')
                        ->addActionLabel('Lisa pilt')
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0),

                    Forms\Components\TextInput::make('data.hero.slideshowInterval')
                        ->label('Slideshow üleminekuaeg (sekundites)')
                        ->helperText('Kehtib kui üleval on rohkem kui üks pilt. Vaikimisi 5 sekundit.')
                        ->numeric()
                        ->minValue(2)
                        ->maxValue(30)
                        ->placeholder('5'),
                ]),

            Forms\Components\Section::make('Märk (badge)')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('data.hero.badge.icon')->label('Ikoon')->placeholder('sprout'),
                    Forms\Components\TextInput::make('data.hero.badge.text')->label('Tekst'),
                ]),
            Forms\Components\Section::make('Pealkiri')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('data.hero.title.before')->label('Algustekst'),
                    Forms\Components\TextInput::make('data.hero.title.highlight')->label('Esiletõstetud tekst'),
                ]),
            Forms\Components\Textarea::make('data.hero.description')->label('Kirjeldus')->rows(3),
            Forms\Components\Section::make('Esmane nupp (CTA)')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('data.hero.primaryCta.label')->label('Tekst'),
                    Forms\Components\TextInput::make('data.hero.primaryCta.href')->label('Link'),
                    Forms\Components\TextInput::make('data.hero.primaryCta.icon')->label('Ikoon'),
                ]),
            Forms\Components\Section::make('Teisene nupp')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('data.hero.secondaryCta.label')->label('Tekst'),
                    Forms\Components\TextInput::make('data.hero.secondaryCta.href')->label('Link'),
                ]),
            Forms\Components\Repeater::make('data.hero.highlights')
                ->label('Esiletõstmised')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('icon')->label('Ikoon'),
                    Forms\Components\TextInput::make('text')->label('Tekst'),
                ])
                ->defaultItems(0)
                ->reorderable()
                ->collapsible(),
        ];
    }

    protected static function homepageStorySchema(): array
    {
        return [
            Forms\Components\Section::make('Lugu')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('data.story.id')->label('Anchor ID')->placeholder('lugu'),
                    Forms\Components\TextInput::make('data.story.eyebrow')->label('Eyebrow'),
                    Forms\Components\TextInput::make('data.story.title')->label('Pealkiri')->columnSpanFull(),
                ]),
            Forms\Components\Repeater::make('data.story.paragraphs')
                ->label('Lõigud')
                ->simple(
                    Forms\Components\Textarea::make('text')->label('Lõik')->rows(3)
                )
                ->defaultItems(0)
                ->reorderable()
                ->collapsible(),
            Forms\Components\Section::make('CTA nupp')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('data.story.cta.label')->label('Tekst'),
                    Forms\Components\TextInput::make('data.story.cta.href')->label('Link'),
                ]),
            Forms\Components\Section::make('Statistika')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('data.story.stat.value')->label('Väärtus'),
                    Forms\Components\TextInput::make('data.story.stat.label')->label('Tekst'),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\IconColumn::make('status')->boolean()->label('Aktiivne'),
                Tables\Columns\IconColumn::make('in_menu')->boolean()->label('Menüüs'),
                Tables\Columns\TextColumn::make('menu_position')->label('Järjek.')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y H:i')->label('Muudetud'),
            ])
            ->defaultSort('menu_position')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
