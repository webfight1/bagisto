<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use OpenApi\Attributes as OA;

class PageController extends Controller
{
    #[OA\Get(
        path: '/api/v1/pages/{slug}',
        operationId: 'getAiamaailmPage',
        summary: 'Get CMS page (managed via Filament /cms)',
        description: 'Returns a structured page. `data` is filled for the homepage (slug=home) with hero/valueProps/story/cta blocks; `blocks` is the generic Builder content for other pages.',
        tags: ['Aiamaailm CMS'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, description: 'Page slug, e.g. home, firmast, kontakt', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'slug',    type: 'string', example: 'home'),
                    new OA\Property(property: 'title',   type: 'string', example: 'Avaleht'),
                    new OA\Property(property: 'excerpt', type: 'string', nullable: true),
                    new OA\Property(property: 'data',    type: 'object', nullable: true, description: 'Homepage-only structured data (hero, valueProps, story, cta)'),
                    new OA\Property(property: 'blocks',  type: 'array', items: new OA\Items(type: 'object', properties: [
                        new OA\Property(property: 'type', type: 'string', enum: ['text', 'text_image', 'image', 'gallery', 'quote', 'cta', 'html']),
                        new OA\Property(property: 'data', type: 'object'),
                    ])),
                ]
            )),
            new OA\Response(response: 404, description: 'Page not found'),
        ]
    )]
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('status', true)->first();

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        return response()->json([
            'slug'    => $page->slug,
            'title'   => $page->title,
            'excerpt' => $page->excerpt,
            'data'    => $page->data,
            'blocks'  => $page->content_blocks ?? [],
        ]);
    }
}
