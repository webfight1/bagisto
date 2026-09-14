<?php

namespace App\Http\Controllers\Api;

use App\Models\NewsPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsController
{
    /**
     * GET /api/v1/news
     *   ?per_page=12   (default 10, max 50)
     *   ?page=1
     *
     * Returns paginated public posts newest-first with cover image URL,
     * excerpt, author, publish date and slug. NO block bodies here — those
     * come via /api/v1/news/{slug}.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $posts = NewsPost::query()
            ->public()
            ->paginate($perPage)
            ->through(fn (NewsPost $p) => [
                'slug'         => $p->slug,
                'title'        => $p->title,
                'excerpt'      => $p->excerpt,
                'author'       => $p->author,
                'published_at' => optional($p->published_at)->toIso8601String(),
                'cover_image'  => $this->coverUrl($p->cover_image),
            ]);

        return response()->json([
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/news/{slug} — full post incl. content_blocks
     */
    public function show(string $slug): JsonResponse
    {
        $post = NewsPost::query()->public()->where('slug', $slug)->first();

        if (! $post) {
            return response()->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'slug'           => $post->slug,
            'title'          => $post->title,
            'excerpt'        => $post->excerpt,
            'author'         => $post->author,
            'published_at'   => optional($post->published_at)->toIso8601String(),
            'cover_image'    => $this->coverUrl($post->cover_image),
            'content_blocks' => $post->content_blocks ?? [],
        ]);
    }

    private function coverUrl(?string $path): ?string
    {
        if (! $path) return null;
        if (preg_match('#^https?://#', $path)) return $path;
        return url('storage/'.ltrim($path, '/'));
    }
}
