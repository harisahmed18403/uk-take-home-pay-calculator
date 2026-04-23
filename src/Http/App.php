<?php

declare(strict_types=1);

namespace TakeHomePay\Http;

use TakeHomePay\Data\TaxYears;
use TakeHomePay\Services\TakeHomePayCalculator;
use TakeHomePay\Support\BasePath;
use TakeHomePay\Support\Format;
use TakeHomePay\Support\Site;

final class App
{
    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @return array{status:int, content:string, headers?:array<int, string>}
     */
    public function handle(array $get, array $post): array
    {
        $basePath = BasePath::current();
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $relativePath = BasePath::stripFromRequestPath((string) ($requestPath ?: '/'), $basePath);

        if ($relativePath === '/robots.txt') {
            return [
                'status' => 200,
                'content' => $this->renderRobots($basePath),
                'headers' => ['Content-Type: text/plain; charset=UTF-8'],
            ];
        }

        if ($relativePath === '/sitemap.xml') {
            return [
                'status' => 200,
                'content' => $this->renderSitemap($basePath),
                'headers' => ['Content-Type: application/xml; charset=UTF-8'],
            ];
        }

        $route = $this->resolvePage($relativePath, $get, $basePath);
        if ($route['redirect'] !== null) {
            return [
                'status' => 301,
                'content' => '',
                'headers' => ['Location: ' . $route['redirect']],
            ];
        }

        $page = $route['page'] ?? 'not-found';
        $status = $page === 'not-found' ? 404 : 200;
        $pageMeta = $this->pageMeta($page, $basePath);
        $faqItems = $this->faqItems();
        $guides = $this->guides();
        $lastUpdated = $this->lastUpdated();

        $data = [
            'page' => $page,
            'title' => $pageMeta['title'],
            'metaDescription' => $pageMeta['description'],
            'canonicalUrl' => $pageMeta['canonical'],
            'robotsMeta' => $pageMeta['robots'],
            'openGraphType' => $pageMeta['og_type'],
            'siteName' => 'No Cap Tools',
            'basePath' => $basePath,
            'originUrl' => Site::originUrl(),
            'siteUrl' => Site::siteUrl($basePath),
            'sitemapUrl' => Site::absoluteUrl(BasePath::sitemap($basePath)),
            'ogImageUrl' => Site::absoluteUrl(BasePath::asset('assets/seo/og-image.png', $basePath)),
            'lastUpdatedIso' => gmdate('c', $lastUpdated),
            'lastUpdatedHuman' => gmdate('j F Y', $lastUpdated),
            'jsonLd' => $this->jsonLd($page, $pageMeta['canonical'], $faqItems, $basePath, $lastUpdated),
            'taxYears' => TaxYears::all(),
            'form' => $this->defaultFormState(),
            'result' => null,
            'errors' => [],
            'guides' => $guides,
            'faqItems' => $faqItems,
        ];

        if ($page === 'home' && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $data['form'] = array_merge($data['form'], $post);
            $errors = $this->validate($data['form']);
            $data['errors'] = $errors;

            if ($errors === []) {
                $calculator = new TakeHomePayCalculator();
                $data['result'] = $calculator->calculate([
                    'salary' => $data['form']['salary'] ?? 0,
                    'salary_period' => $data['form']['salary_period'] ?? 'annual',
                    'bonus' => $data['form']['bonus'] ?? 0,
                    'tax_year' => $data['form']['tax_year'] ?? '2026-2027',
                    'region' => $data['form']['region'] ?? 'england',
                    'tax_code' => $data['form']['tax_code'] ?? '1257L',
                    'pension_percent' => $data['form']['pension_percent'] ?? 0,
                    'pension_method' => $data['form']['pension_method'] ?? 'salary_sacrifice',
                    'student_loan_plan' => $data['form']['student_loan_plan'] ?? 'none',
                    'has_postgraduate_loan' => isset($data['form']['has_postgraduate_loan']) && $data['form']['has_postgraduate_loan'] === '1',
                ]);
            }
        }

        ob_start();
        $format = new Format();
        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/templates/layout.php';
        $content = (string) ob_get_clean();

        return ['status' => $status, 'content' => $content];
    }

    /**
     * @param array<string, mixed> $get
     * @return array{page:?string, redirect:?string}
     */
    private function resolvePage(string $relativePath, array $get, string $basePath): array
    {
        if ($relativePath === '/index.php') {
            $legacyPage = (string) ($get['page'] ?? '');
            if ($legacyPage !== '' && in_array($legacyPage, ['guides', 'faq', 'privacy', 'cookies'], true)) {
                return ['page' => null, 'redirect' => BasePath::route($legacyPage, $basePath)];
            }

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
                return ['page' => null, 'redirect' => BasePath::route('home', $basePath)];
            }

            return ['page' => 'home', 'redirect' => null];
        }

        $pageFromPath = BasePath::pageFromPath($relativePath);
        if ($pageFromPath !== null) {
            return ['page' => $pageFromPath, 'redirect' => null];
        }

        $legacyPage = (string) ($get['page'] ?? '');
        if ($legacyPage !== '' && in_array($legacyPage, ['guides', 'faq', 'privacy', 'cookies'], true)) {
            return ['page' => null, 'redirect' => BasePath::route($legacyPage, $basePath)];
        }

        return ['page' => null, 'redirect' => null];
    }

    /**
     * @return array{title:string, description:string, canonical:string, robots:string, og_type:string}
     */
    private function pageMeta(string $page, string $basePath): array
    {
        $route = $page === 'not-found' ? BasePath::route('home', $basePath) : BasePath::route($page, $basePath);
        $canonical = Site::absoluteUrl($route);

        return match ($page) {
            'guides' => [
                'title' => 'UK Take Home Pay Calculator Guides | Salary After Tax 2026/27 | No Cap Tools',
                'description' => 'See how the UK salary after tax calculator annualises pay, applies PAYE income tax and National Insurance, and handles pension and student loan deductions for 2026/27.',
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
                'og_type' => 'article',
            ],
            'faq' => [
                'title' => 'UK Take Home Pay Calculator FAQ | Salary After Tax Questions | No Cap Tools',
                'description' => 'Answers to common questions about the UK salary after tax calculator, salary after tax, net pay, Scotland, tax codes, student loans, pension treatments, and estimate accuracy for 2026/27.',
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
                'og_type' => 'article',
            ],
            'privacy' => [
                'title' => 'Privacy Policy | No Cap Tools',
                'description' => 'Read the privacy policy for the UK Take-Home Pay Calculator and understand what data is and is not stored when you use the site in the UK.',
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
                'og_type' => 'article',
            ],
            'cookies' => [
                'title' => 'Cookie Policy | No Cap Tools',
                'description' => 'Read the cookie policy for the UK Take-Home Pay Calculator, including how functional, analytics, and advertising cookies would be handled for UK visitors.',
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
                'og_type' => 'article',
            ],
            'not-found' => [
                'title' => 'Page Not Found | No Cap Tools',
                'description' => 'The page you requested could not be found on the UK Take-Home Pay Calculator site.',
                'canonical' => $canonical,
                'robots' => 'noindex,follow',
                'og_type' => 'website',
            ],
            default => [
                'title' => 'UK Take Home Pay Calculator 2026/27 | Salary After Tax, Net Pay & PAYE | No Cap Tools',
                'description' => 'Estimate UK take-home pay, salary after tax, and net pay for 2026/27. Compare annual, monthly, and weekly earnings with PAYE income tax, National Insurance, pension deductions, bonus income, and student loans across England, Wales, Scotland, and Northern Ireland.',
                'canonical' => $canonical,
                'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
                'og_type' => 'website',
            ],
        };
    }

    /**
     * @return array<int, array{question:string, answer:string}>
     */
    private function faqItems(): array
    {
        return [
            [
                'question' => 'Does this calculator cover Scotland and the rest of the UK?',
                'answer' => 'Yes. Scottish tax bands are applied when you choose Scotland or enter a tax code that starts with S, and the calculator is built for UK take-home pay estimates.',
            ],
            [
                'question' => 'Can I include student loans, pension deductions, and salary sacrifice?',
                'answer' => 'Yes. The calculator supports undergraduate student loan plans, postgraduate loans, salary sacrifice, and three pension treatments.',
            ],
            [
                'question' => 'How accurate is this UK take-home pay estimate?',
                'answer' => 'It is designed as an annualised estimate using published UK thresholds for the selected tax year. Actual payroll output can vary because of payroll timing, benefits, or employer-specific settings.',
            ],
            [
                'question' => 'Can I use monthly salary or weekly pay instead of annual salary for UK calculations?',
                'answer' => 'Yes. The calculator annualises monthly pay by multiplying by 12 and weekly pay by multiplying by 52 before applying deductions.',
            ],
            [
                'question' => 'Does salary sacrifice pension reduce National Insurance as well as Income Tax in the UK?',
                'answer' => 'Yes. Salary sacrifice reduces both taxable pay and NI-able pay, while net pay reduces taxable pay only and post-tax pension does not reduce either calculation before deductions.',
            ],
            [
                'question' => 'Can I include a bonus or additional income in my take-home pay estimate?',
                'answer' => 'Yes. Bonus income is added to gross annual pay before tax, National Insurance, pension, and student loan deductions are calculated for your UK estimate.',
            ],
            [
                'question' => 'Will this help compare UK job offers at different salaries?',
                'answer' => 'Yes. The calculator is useful for comparing gross UK salary offers by converting them into annual, monthly, and weekly take-home pay using the same tax assumptions.',
            ],
            [
                'question' => 'Can I estimate the effect of a different UK tax code?',
                'answer' => 'Yes. You can enter common UK tax codes such as 1257L, S1257L, BR, D0, D1, NT, or K codes to see how they change the estimate.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string|array<int, string>>>
     */
    private function guides(): array
    {
        return [
            [
                'title' => '1. Annualise your pay first',
                'body' => 'The calculator converts your pay into an annual figure before any deductions are worked out. That makes annual salary, monthly pay, weekly pay, and bonuses comparable inside a single PAYE model.',
                'formula' => 'gross_annual = annual salary or (monthly salary × 12) or (weekly salary × 52) + bonus',
                'steps' => [
                    'Start with the salary amount you entered.',
                    'Convert monthly pay to annual by multiplying by 12, or weekly pay by multiplying by 52.',
                    'Add any bonus or additional income to get gross annual pay.',
                ],
            ],
            [
                'title' => '2. Work out taxable pay and deductions',
                'body' => 'Income Tax, National Insurance, and pension are calculated from slightly different versions of your pay. That matters because salary sacrifice affects National Insurance differently from net pay or post-tax pension contributions.',
                'formula' => 'net_annual = gross_annual - income_tax - national_insurance - student_loan - pension',
                'steps' => [
                    'Pension is gross annual pay multiplied by your pension percentage.',
                    'Taxable pay is reduced by pension for salary sacrifice and net pay arrangements.',
                    'NI-able pay is reduced only for salary sacrifice pension.',
                    'Income Tax is applied band by band after your personal allowance and tax code adjustments.',
                    'National Insurance is charged at the main rate up to the upper earnings limit and the additional rate above it.',
                ],
            ],
            [
                'title' => '3. Add student loans and derive take-home pay',
                'body' => 'Student loan deductions are added after their threshold checks, and then the calculator converts the final net figure into monthly and weekly views. This makes it easier to compare job offers and budget using the same assumptions.',
                'formula' => 'student_loan = max(0, gross_annual - threshold) × rate',
                'steps' => [
                    'For each selected student loan plan, only the earnings above its threshold are charged.',
                    'If a postgraduate loan is selected, it stacks on top of the undergraduate plan.',
                    'Total deductions are added together and subtracted from gross annual pay.',
                    'Monthly net pay is net annual pay divided by 12, and weekly net pay is net annual pay divided by 52.',
                ],
            ],
        ];
    }

    /**
     * @param array<int, array{question:string, answer:string}> $faqItems
     * @return array<int, array<string, mixed>>
     */
    private function jsonLd(string $page, string $canonicalUrl, array $faqItems, string $basePath, int $lastUpdated): array
    {
        $siteUrl = Site::siteUrl($basePath);
        $siteName = 'No Cap Tools';
        $lastUpdatedIso = gmdate('c', $lastUpdated);
        $graph = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => Site::originUrl(),
                'logo' => Site::absoluteUrl(BasePath::asset('assets/favicons/favicon.svg', $basePath)),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl . '/',
                'inLanguage' => 'en-GB',
            ],
        ];

        $graph[] = [
            '@context' => 'https://schema.org',
            '@type' => match ($page) {
                'guides' => 'CollectionPage',
                'faq' => 'FAQPage',
                default => 'WebPage',
            },
            'name' => match ($page) {
                'guides' => 'UK take home pay calculator guides',
                'faq' => 'UK take home pay calculator frequently asked questions',
                'privacy' => 'Privacy policy',
                'cookies' => 'Cookie policy',
                default => 'UK take home pay calculator',
            },
            'description' => match ($page) {
                'guides' => 'See how the UK salary after tax calculator annualises pay, applies PAYE income tax and National Insurance, and handles pension and student loan deductions for 2026/27.',
                'faq' => 'Answers to common questions about the UK salary after tax calculator, Scotland, tax codes, student loans, pension treatments, and estimate accuracy for 2026/27.',
                'privacy' => 'Read the privacy policy for the UK Take-Home Pay Calculator and understand what data is and is not stored when you use the site in the UK.',
                'cookies' => 'Read the cookie policy for the UK Take-Home Pay Calculator, including how functional, analytics, and advertising cookies would be handled for UK visitors.',
                default => 'Estimate UK take-home pay, salary after tax, and net pay for 2026/27. Compare annual, monthly, and weekly earnings with PAYE income tax, National Insurance, pension deductions, bonus income, and student loans across England, Wales, Scotland, and Northern Ireland.',
            },
            'url' => $canonicalUrl,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl . '/',
            ],
            'primaryImageOfPage' => Site::absoluteUrl(BasePath::asset('assets/seo/og-image.png', $basePath)),
            'inLanguage' => 'en-GB',
            'dateModified' => $lastUpdatedIso,
        ];

        if ($page === 'home') {
            $graph[count($graph) - 1]['about'] = [
                [
                    '@type' => 'Thing',
                    'name' => 'UK take home pay calculator',
                ],
                [
                    '@type' => 'Thing',
                    'name' => 'Salary after tax',
                ],
                [
                    '@type' => 'Thing',
                    'name' => 'PAYE income tax',
                ],
                [
                    '@type' => 'Thing',
                    'name' => 'National Insurance',
                ],
            ];
        }

        if ($page === 'home') {
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'UK Salary After Tax Calculator',
                'applicationCategory' => 'FinanceApplication',
                'operatingSystem' => 'Any',
                'isAccessibleForFree' => true,
                'url' => $canonicalUrl,
                'description' => 'Calculate UK take-home pay, salary after tax, and net pay with PAYE income tax, National Insurance, pension, bonus, and student loan deductions.',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'GBP',
                ],
                'dateModified' => $lastUpdatedIso,
            ];
        }

        if ($page === 'guides') {
            $guideSteps = [];
            $position = 1;
            foreach ($this->guides() as $guide) {
                foreach ($guide['steps'] as $step) {
                    $guideSteps[] = [
                        '@type' => 'HowToStep',
                        'position' => $position,
                        'name' => $step,
                        'text' => $step,
                    ];
                    $position++;
                }
            }

            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => 'How UK take-home pay is calculated',
                'description' => 'A step-by-step outline of how the UK take-home pay calculator annualises income, applies deductions, and derives net pay.',
                'step' => $guideSteps,
                'totalTime' => 'PT5M',
                'dateModified' => $lastUpdatedIso,
            ];
        }

        if (in_array($page, ['home', 'faq'], true)) {
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    static fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $item['answer'],
                        ],
                    ],
                    $faqItems
                ),
            ];
        }

        $graph[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $this->breadcrumbList($page, $canonicalUrl, $basePath),
        ];

        return $graph;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function breadcrumbList(string $page, string $canonicalUrl, string $basePath): array
    {
        $breadcrumbs = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'UK Take-Home Pay Calculator',
                'item' => Site::absoluteUrl(BasePath::route('home', $basePath)),
            ],
        ];

        if ($page !== 'home' && $page !== 'not-found') {
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => match ($page) {
                    'guides' => 'Guides',
                    'faq' => 'FAQ',
                    'privacy' => 'Privacy Policy',
                    'cookies' => 'Cookie Policy',
                    default => 'Page',
                },
                'item' => $canonicalUrl,
            ];
        }

        return $breadcrumbs;
    }

    private function renderSitemap(string $basePath): string
    {
        $lastModified = gmdate('Y-m-d', $this->lastUpdated());
        $urls = [
            Site::absoluteUrl(BasePath::route('home', $basePath)),
            Site::absoluteUrl(BasePath::route('guides', $basePath)),
            Site::absoluteUrl(BasePath::route('faq', $basePath)),
            Site::absoluteUrl(BasePath::route('privacy', $basePath)),
            Site::absoluteUrl(BasePath::route('cookies', $basePath)),
        ];

        $items = array_map(
            static fn (string $url): string => "  <url>\n    <loc>{$url}</loc>\n    <lastmod>{$lastModified}</lastmod>\n  </url>",
            $urls
        );

        $template = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
%s
</urlset>
XML;

        return sprintf($template, implode("\n", $items));
    }

    private function renderRobots(string $basePath): string
    {
        $sitemapUrl = Site::absoluteUrl(BasePath::sitemap($basePath));

        return <<<TXT
User-agent: *
Allow: /

Sitemap: {$sitemapUrl}
TXT;
    }

    private function lastUpdated(): int
    {
        $paths = [
            dirname(__DIR__, 2) . '/src/Data/TaxYears.php',
            dirname(__DIR__, 2) . '/src/Http/App.php',
            dirname(__DIR__, 2) . '/templates/layout.php',
        ];

        $timestamps = array_map(
            static fn (string $path): int => file_exists($path) ? (int) filemtime($path) : time(),
            $paths
        );

        return max($timestamps);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormState(): array
    {
        return [
            'salary' => '',
            'salary_period' => 'annual',
            'bonus' => '0',
            'tax_year' => '2026-2027',
            'region' => 'england',
            'tax_code' => '1257L',
            'pension_percent' => '5',
            'pension_method' => 'salary_sacrifice',
            'student_loan_plan' => 'none',
            'has_postgraduate_loan' => '0',
        ];
    }

    /**
     * @param array<string, mixed> $form
     * @return array<int, string>
     */
    private function validate(array $form): array
    {
        $errors = [];

        if (!is_numeric((string) ($form['salary'] ?? '')) || (float) $form['salary'] <= 0) {
            $errors[] = 'Enter a salary greater than zero.';
        }

        if (!is_numeric((string) ($form['bonus'] ?? '0')) || (float) $form['bonus'] < 0) {
            $errors[] = 'Bonus must be zero or more.';
        }

        if (!is_numeric((string) ($form['pension_percent'] ?? '0')) || (float) $form['pension_percent'] < 0 || (float) $form['pension_percent'] > 100) {
            $errors[] = 'Pension contribution must be between 0 and 100 percent.';
        }

        return $errors;
    }
}
