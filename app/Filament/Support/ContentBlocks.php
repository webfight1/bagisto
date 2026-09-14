<?php

namespace App\Filament\Support;

use Filament\Forms;

/**
 * Shared content-block library used by any Filament resource that renders
 * body content as a Builder (PageResource, NewsPostResource, etc.).
 *
 * Adding a new block type: append it here → both pages and news posts get it.
 * Rendering on the frontend lives in the SPA (src/components/shop/PageBlocks.tsx).
 */
class ContentBlocks
{
    public static function all(): array
    {
        return [
            self::text(),
            self::textImage(),
            self::image(),
            self::gallery(),
            self::quote(),
            self::cta(),
            self::html(),
            self::faq(),
        ];
    }

    /**
     * Toolbar buttons for RichEditor (TipTap-based WYSIWYG).
     * Filament v3 RichEditor supported buttons:
     *   attachFiles, blockquote, bold, bulletList, codeBlock,
     *   h2, h3, italic, link, orderedList, redo, strike, underline, undo
     */
    private static function richToolbar(): array
    {
        return [
            'bold', 'italic', 'underline', 'strike', 'link',
            'h2', 'h3',
            'bulletList', 'orderedList',
            'blockquote', 'codeBlock',
            'attachFiles', 'undo', 'redo',
        ];
    }

    private static function text(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('text')
            ->label('Tekst')
            ->icon('heroicon-o-document-text')
            ->schema([
                Forms\Components\RichEditor::make('body')
                    ->label('Tekst')
                    ->toolbarButtons(self::richToolbar())
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('page-attachments'),
            ]);
    }

    private static function textImage(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('text_image')
            ->label('Tekst + pilt')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\Radio::make('layout')
                    ->label('Paigutus')
                    ->options([
                        'text-left'  => 'Tekst vasakul, pilt paremal',
                        'image-left' => 'Pilt vasakul, tekst paremal',
                    ])
                    ->default('text-left')
                    ->inline()
                    ->inlineLabel(false),
                Forms\Components\FileUpload::make('image')
                    ->label('Pilt')
                    ->image()
                    ->disk('public')
                    ->directory('page-blocks')
                    ->imageEditor(),
                Forms\Components\TextInput::make('caption')
                    ->label('Pildi pealkiri (alt-tekst)'),
                Forms\Components\RichEditor::make('body')
                    ->label('Tekst')
                    ->toolbarButtons(self::richToolbar())
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('page-attachments'),
            ]);
    }

    private static function image(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('image')
            ->label('Üks pilt')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\FileUpload::make('image')
                    ->label('Pilt')
                    ->image()
                    ->disk('public')
                    ->directory('page-blocks')
                    ->imageEditor(),
                Forms\Components\TextInput::make('caption')->label('Pealkiri / alt-tekst'),
                Forms\Components\Select::make('width')
                    ->label('Laius')
                    ->options(['full' => 'Kogu laius', 'wide' => '80%', 'narrow' => '50%'])
                    ->default('full'),
            ]);
    }

    private static function gallery(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('gallery')
            ->label('Galerii')
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                Forms\Components\FileUpload::make('images')
                    ->label('Pildid')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('page-blocks')
                    ->imageEditor(),
                Forms\Components\Select::make('columns')
                    ->label('Veerge')
                    ->options(['2' => '2 veergu', '3' => '3 veergu', '4' => '4 veergu'])
                    ->default('3'),
                Forms\Components\Toggle::make('lightbox')
                    ->label('Klikitavad (suurendus)')
                    ->default(true),
            ]);
    }

    private static function quote(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('quote')
            ->label('Tsitaat')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->schema([
                Forms\Components\Textarea::make('body')->label('Tsitaat')->rows(3),
                Forms\Components\TextInput::make('author')->label('Autor'),
            ]);
    }

    private static function cta(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('cta')
            ->label('Üleskutse (CTA)')
            ->icon('heroicon-o-megaphone')
            ->schema([
                Forms\Components\TextInput::make('title')->label('Pealkiri'),
                Forms\Components\Textarea::make('description')->label('Kirjeldus')->rows(2),
                Forms\Components\TextInput::make('button_label')->label('Nupu tekst'),
                Forms\Components\TextInput::make('button_href')->label('Nupu link'),
            ]);
    }

    private static function html(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('html')
            ->label('Raw HTML / embed')
            ->icon('heroicon-o-code-bracket')
            ->schema([
                Forms\Components\Textarea::make('body')
                    ->label('HTML')
                    ->rows(6)
                    ->helperText('Ole ettevaatlik: täielik kontroll, ka tarbetu või katkine HTML jõuab live-i.'),
            ]);
    }

    private static function faq(): Forms\Components\Builder\Block
    {
        return Forms\Components\Builder\Block::make('faq')
            ->label('KKK / FAQ')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Pealkiri (valikuline)')
                    ->placeholder('Korduma kippuvad küsimused'),
                Forms\Components\Repeater::make('items')
                    ->label('Küsimused')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->label('Küsimus')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('answer')
                            ->label('Vastus')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'link',
                                'bulletList', 'orderedList',
                                'blockquote',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->addActionLabel('Lisa küsimus')
                    ->defaultItems(1)
                    ->reorderable()
                    ->collapsible()
                    ->cloneable(),
            ]);
    }
}
