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
        $canonicalRedirect = $this->canonicalHostRedirect();
        if ($canonicalRedirect !== null) {
            return [
                'status' => 301,
                'content' => '',
                'headers' => ['Location: ' . $canonicalRedirect],
            ];
        }

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

    private function canonicalHostRedirect(): ?string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return null;
        }

        $configuredRoot = $_ENV['APP_ROOT_URL']
            ?? $_SERVER['APP_ROOT_URL']
            ?? getenv('APP_ROOT_URL')
            ?: '';

        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $currentHost = preg_replace('/:\d+$/', '', $currentHost) ?: $currentHost;

        if ($configuredRoot === '') {
            if ($currentHost !== 'no-cap-tools.com') {
                return null;
            }

            $configuredRoot = 'https://www.no-cap-tools.com';
        }

        $targetHost = parse_url((string) $configuredRoot, PHP_URL_HOST);
        $targetScheme = parse_url((string) $configuredRoot, PHP_URL_SCHEME) ?: 'https';

        if ($targetHost === null || strtolower($targetHost) === strtolower($currentHost)) {
            return null;
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        return $targetScheme . '://' . $targetHost . $requestUri;
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
                'title' => 'UK Salary Calculator 2026/27 | Take Home Pay & Salary Exchange',
                'description' => 'Free UK salary calculator for 2026/27 take-home pay. Estimate salary after tax, monthly net pay, PAYE, NI, pension salary exchange, bonus sacrifice, and student loans.',
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
                'question' => 'Is this a salary calculator for the 2026/27 tax year?',
                'answer' => 'Yes. The default tax year is 2026/27, so you can use it as a UK salary calculator 2026 27 for take-home pay after Income Tax, National Insurance, pension, and student loan deductions.',
            ],
            [
                'question' => 'Does this calculator cover Scotland and the rest of the UK?',
                'answer' => 'Yes. Scottish tax bands are applied when you choose Scotland or enter a tax code that starts with S, and the calculator is built for UK take-home pay estimates.',
            ],
            [
                'question' => 'Can I include student loans, pension deductions, and salary sacrifice?',
                'answer' => 'Yes. The calculator supports undergraduate student loan plans, postgraduate loans, salary sacrifice, salary exchange, and three pension contribution treatments.',
            ],
            [
                'question' => 'Can I use this as a UK contributions calculator?',
                'answer' => 'Yes. Enter your salary, pension percentage, pension method, student loan plan, and bonus to estimate payroll contributions and deductions for Income Tax, National Insurance, pension, and student loans.',
            ],
            [
                'question' => 'Can I use this as a final salary calculator?',
                'answer' => 'Yes, if you mean final salary after tax and deductions. It estimates the final annual, monthly, and weekly take-home pay from a gross UK salary, but it is not a defined benefit final salary pension scheme calculator.',
            ],
            [
                'question' => 'Is this a salary calculator with student loan deductions?',
                'answer' => 'Yes. Choose Plan 1, Plan 2, Plan 4, Plan 5, and postgraduate loan options to estimate UK salary after tax with student loan repayments included.',
            ],
            [
                'question' => 'Is this a salary calculator with salary sacrifice?',
                'answer' => 'Yes. Enter your pension percentage and choose salary sacrifice to estimate salary after tax with salary sacrifice, including the effect on taxable pay, National Insurance, and take-home pay.',
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
                'answer' => 'Yes. Salary sacrifice, also called salary exchange, reduces both taxable pay and NI-able pay, while net pay reduces taxable pay only and post-tax pension does not reduce either calculation before deductions.',
            ],
            [
                'question' => 'Can I use this as a salary exchange calculator?',
                'answer' => 'Yes. Choose salary sacrifice as the pension method to estimate salary exchange pension deductions and see the effect on take-home pay, Income Tax, and National Insurance.',
            ],
            [
                'question' => 'Can I use this for salary exchange calculator 2019/20 searches?',
                'answer' => 'This calculator currently includes 2025/26 and 2026/27 tax years, not 2019/20 thresholds. For a salary exchange calculator 2019/20 query, use this page to understand how salary exchange reduces taxable pay and National Insurance, then use the tax year selector only for current-year estimates.',
            ],
            [
                'question' => 'What changed since salary exchange calculator 2019/20 searches?',
                'answer' => 'The salary exchange method is still based on sacrificing gross pay before PAYE and National Insurance, but tax bands, allowances, NI thresholds, student loan thresholds, and pension settings have changed since 2019/20. Use the current tax year options for a current take-home pay estimate.',
            ],
            [
                'question' => 'Can I include a bonus or additional income in my take-home pay estimate?',
                'answer' => 'Yes. Bonus income is added to gross annual pay before tax, National Insurance, pension, and student loan deductions are calculated for your UK estimate.',
            ],
            [
                'question' => 'Can I use it as a bonus sacrifice calculator?',
                'answer' => 'Yes. Add the bonus amount, choose salary sacrifice as the pension method, and enter the pension percentage to estimate how bonus sacrifice affects taxable pay, National Insurance, student loans, and take-home pay.',
            ],
            [
                'question' => 'Will this help compare UK job offers at different salaries?',
                'answer' => 'Yes. The calculator is useful for comparing gross UK salary offers by converting each offer into annual, monthly, and weekly take-home pay using the same tax assumptions.',
            ],
            [
                'question' => 'Can I estimate the effect of a different UK tax code?',
                'answer' => 'Yes. You can enter common UK tax codes such as 1257L, S1257L, BR, D0, D1, NT, or K codes to see how they change the estimate.',
            ],
            [
                'question' => 'Can I check common UK salaries such as £25,000, £30,000, £40,000, or £50,000 after tax?',
                'answer' => 'Yes. Enter the gross salary, keep the salary period as annual, and the calculator will show estimated yearly, monthly, and weekly take-home pay after PAYE deductions.',
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
                'body' => 'Income Tax, National Insurance, and pension are calculated from slightly different versions of your pay. That matters because salary sacrifice, also called salary exchange, affects National Insurance differently from net pay or post-tax pension contributions.',
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
            [
                'title' => '4. Compare 2026/27 salary examples',
                'body' => 'Salary calculator 2026/27 searches often start with a gross annual figure such as £25,000, £30,000, £40,000, or £50,000. Using the same tax year, region, tax code, pension, and loan settings keeps each after-tax comparison consistent.',
                'formula' => 'monthly_take_home = net_annual / 12',
                'steps' => [
                    'Enter the gross salary as an annual amount.',
                    'Keep the same tax year and deduction settings for each comparison.',
                    'Compare the annual, monthly, and weekly net pay figures in the results panel.',
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
                default => 'Calculate UK salary after tax and take-home pay for 2026/27. Compare annual, monthly, and weekly net pay with PAYE Income Tax, National Insurance, pension salary exchange, bonus income, and student loans across England, Wales, Scotland, and Northern Ireland.',
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
            'relatedLink' => [
                'https://www.no-cap-tools.com/e-comm-calculator/',
            ],
            'mentions' => [
                [
                    '@type' => 'WebApplication',
                    'name' => 'Ecommerce pricing calculator',
                    'url' => 'https://www.no-cap-tools.com/e-comm-calculator/',
                    'applicationCategory' => 'BusinessApplication',
                ],
            ],
        ];

        if ($page === 'home') {
            $graph[count($graph) - 1]['mainEntity'] = [
                '@type' => 'SoftwareApplication',
                'name' => 'UK Take Home Pay Calculator 2026/27',
                'alternateName' => 'UK salary calculator with pension contributions and student loan deductions',
                'applicationCategory' => 'FinanceApplication',
                'operatingSystem' => 'Any',
                'url' => $canonicalUrl,
                'keywords' => 'UK salary calculator 2026/27, salary calculator 2026 27, take home pay calculator, salary after tax, pension salary exchange calculator, salary exchange calculator, salary exchange calculator 2019/20, salary sacrifice calculator, contributions calculator, salary calculator with salary sacrifice, salary calculator student loan, salary calculator with student loan, bonus sacrifice calculator, final salary calculator, estimate net paycheck, monthly salary after tax, net pay calculator',
                'potentialAction' => [
                    '@type' => 'UseAction',
                    'target' => $canonicalUrl . '#calculator',
                    'name' => 'Calculate UK take-home pay',
                ],
            ];
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
                'name' => 'UK Take Home Pay Calculator 2026/27',
                'alternateName' => [
                    'UK salary after tax calculator',
                    'Salary calculator 2026 27',
                    'Pension salary exchange calculator',
                    'Salary exchange calculator',
                    'Salary exchange calculator 2019/20',
                    'Salary calculator with salary sacrifice',
                    'Salary calculator with student loan',
                    'Bonus sacrifice calculator',
                    'UK contributions calculator',
                    'Final salary calculator',
                    'Estimate net paycheck',
                    'UK net pay calculator',
                    'Monthly take-home pay calculator',
                ],
                'applicationCategory' => 'FinanceApplication',
                'operatingSystem' => 'Any',
                'isAccessibleForFree' => true,
                'url' => $canonicalUrl,
                'description' => 'Free UK take-home pay calculator for the 2026/27 tax year, including monthly salary after tax, PAYE Income Tax, National Insurance, pension salary exchange, bonus sacrifice, and student loan deductions.',
                'featureList' => [
                    'Annual, monthly, and weekly salary inputs',
                    'PAYE Income Tax and National Insurance estimates',
                    'England, Wales, Scotland, and Northern Ireland regions',
                    'Student loan and postgraduate loan deductions',
                    'Pension contribution estimates using salary sacrifice, salary exchange, net pay, and post-tax methods',
                ],
                'keywords' => 'UK take home pay calculator, UK salary calculator 2026/27, salary calculator 2026 27, pension salary exchange calculator, salary exchange calculator, salary exchange calculator 2019/20, salary calculator with salary sacrifice, salary calculator with student loan, bonus sacrifice calculator, final salary calculator, contributions calculator, estimate net paycheck, UK salary after tax calculator, net pay calculator, monthly take-home pay calculator, PAYE calculator',
                'audience' => [
                    '@type' => 'Audience',
                    'audienceType' => 'UK employees comparing salary after tax and net pay',
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'GBP',
                ],
                'potentialAction' => [
                    '@type' => 'UseAction',
                    'target' => $canonicalUrl . '#calculator',
                    'name' => 'Calculate salary after tax',
                ],
                'dateModified' => $lastUpdatedIso,
            ];

            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => 'UK salary calculator scenarios',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Salary calculator 2026/27',
                        'url' => $canonicalUrl . '#salary-calculator-2026-27',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Salary exchange calculator',
                        'url' => $canonicalUrl . '#salary-exchange-calculator',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => 'Salary calculator with salary sacrifice',
                        'url' => $canonicalUrl . '#salary-sacrifice-calculator',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 4,
                        'name' => 'Salary calculator with student loan',
                        'url' => $canonicalUrl . '#student-loan-salary-calculator',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 5,
                        'name' => 'Bonus sacrifice calculator',
                        'url' => $canonicalUrl . '#bonus-sacrifice-calculator',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 6,
                        'name' => 'Pension contributions calculator',
                        'url' => $canonicalUrl . '#pension-contributions-calculator',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 7,
                        'name' => 'Final salary calculator',
                        'url' => $canonicalUrl . '#final-salary-calculator',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 8,
                        'name' => 'Salary exchange calculator 2019/20 notes',
                        'url' => $canonicalUrl . '#salary-exchange-calculator-2019-20',
                    ],
                ],
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
            [Site::absoluteUrl(BasePath::route('home', $basePath)), 'weekly', '1.0'],
            [Site::absoluteUrl(BasePath::route('guides', $basePath)), 'monthly', '0.8'],
            [Site::absoluteUrl(BasePath::route('faq', $basePath)), 'monthly', '0.8'],
            [Site::absoluteUrl(BasePath::route('privacy', $basePath)), 'yearly', '0.3'],
            [Site::absoluteUrl(BasePath::route('cookies', $basePath)), 'yearly', '0.3'],
        ];

        $items = array_map(
            static fn (array $url): string => "  <url>\n    <loc>{$url[0]}</loc>\n    <lastmod>{$lastModified}</lastmod>\n    <changefreq>{$url[1]}</changefreq>\n    <priority>{$url[2]}</priority>\n  </url>",
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
