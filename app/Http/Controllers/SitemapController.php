<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController
{
    /**
     * GET /sitemap.xml — dünaamiline sitemap Lovable frontend'i URL-mustri järgi.
     *
     * Sisaldab:
     *  - koduleht /
     *  - kõik aktiivsed pages.slug (v.a "home") kui /{slug}
     *  - kõik avalikud news_posts kui /uudised/{slug}
     *  - kõik aktiivsed Bagisto kategooriad kui /pood?cat={slug}
     *  - kõik nähtavad Bagisto tooted (status=1, visible_individually=1, mitte-variant)
     *    kui /toode/{url_key}
     *
     * Response cache: 30 min public HTTP cache — Google crawler ei fetchi liiga sageli.
     */
    public function index(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $now  = now()->toIso8601String();

        $urls = [];

        // Homepage
        $urls[] = ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $now];

        // CMS pages
        Page::query()
            ->where('status', true)
            ->where('slug', '!=', 'home')
            ->orderBy('menu_position')
            ->get(['slug', 'updated_at'])
            ->each(function ($p) use (&$urls, $base) {
                $urls[] = [
                    'loc'        => $base . '/' . $p->slug,
                    'priority'   => '0.7',
                    'changefreq' => 'weekly',
                    'lastmod'    => optional($p->updated_at)->toIso8601String(),
                ];
            });

        // News posts
        NewsPost::query()->public()->get(['slug', 'updated_at'])->each(function ($n) use (&$urls, $base) {
            $urls[] = [
                'loc'        => $base . '/uudised/' . $n->slug,
                'priority'   => '0.6',
                'changefreq' => 'monthly',
                'lastmod'    => optional($n->updated_at)->toIso8601String(),
            ];
        });

        // Bagisto categories — clean /pood/{slug} path format.
        DB::table('category_translations as ct')
            ->join('categories as c', 'c.id', '=', 'ct.category_id')
            ->where('c.status', 1)
            ->where('c.parent_id', '!=', null)
            ->where('ct.locale', 'en')
            ->select('ct.slug', 'c.updated_at')
            ->orderBy('c.position')
            ->get()
            ->each(function ($cat) use (&$urls, $base) {
                if (! $cat->slug || $cat->slug === 'root') return;
                $urls[] = [
                    'loc'        => $base . '/pood/' . rawurlencode($cat->slug),
                    'priority'   => '0.8',
                    'changefreq' => 'daily',
                    'lastmod'    => $cat->updated_at,
                ];
            });

        // Bagisto products
        DB::table('product_flat as pf')
            ->join('products as p', 'p.id', '=', 'pf.product_id')
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            ->whereNull('p.parent_id')
            ->where('pf.locale', 'en')
            ->select('pf.url_key', 'pf.updated_at')
            ->distinct()
            ->get()
            ->each(function ($prod) use (&$urls, $base) {
                if (! $prod->url_key) return;
                $urls[] = [
                    'loc'        => $base . '/toode/' . $prod->url_key,
                    'priority'   => '0.9',
                    'changefreq' => 'weekly',
                    'lastmod'    => $prod->updated_at,
                ];
            });

        $xml = $this->render($urls);

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    /**
     * GET /robots.txt — viitab sitemapile.
     */
    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $txt  = "User-agent: *\n"
              . "Allow: /\n"
              . "Disallow: /admin\n"
              . "Disallow: /cms\n"
              . "Disallow: /api\n"
              . "Disallow: /konto\n"
              . "Disallow: /kassa\n"
              . "Disallow: /ostukorv\n"
              . "Disallow: /login\n"
              . "Disallow: /aitah\n"
              . "\n"
              . "Sitemap: {$base}/sitemap.xml\n";

        return response($txt, 200, [
            'Content-Type'  => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function render(array $urls): string
    {
        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
            }
            if (! empty($u['changefreq'])) {
                $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
            }
            if (! empty($u['priority'])) {
                $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }
}
