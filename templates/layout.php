<?php

declare(strict_types=1);

use TakeHomePay\Support\Format;
use TakeHomePay\Support\BasePath;

/** @var string $page */
/** @var string $title */
/** @var string $metaDescription */
/** @var string $canonicalUrl */
/** @var string $robotsMeta */
/** @var string $openGraphType */
/** @var string $siteName */
/** @var string $basePath */
/** @var string $originUrl */
/** @var string $siteUrl */
/** @var string $sitemapUrl */
/** @var string $ogImageUrl */
/** @var array<int, array<string, mixed>> $jsonLd */
/** @var array<string, array<string, mixed>> $taxYears */
/** @var array<string, mixed> $form */
/** @var array<string, mixed>|null $result */
/** @var array<int, string> $errors */
/** @var array<int, array<string, string>> $guides */
/** @var array<int, array{question:string, answer:string}> $faqItems */
/** @var Format $format */

$calculatorBootstrap = [
    'taxYears' => $taxYears,
    'defaults' => $form,
    'initialResult' => $result,
    'initialErrors' => $errors,
    'assumptions' => [
        'PAYE estimate using annualised earnings.',
        'National Insurance calculated using annual thresholds.',
        'Pension can be treated as salary sacrifice, net pay arrangement, or post-tax deduction.',
        'Student loan repayments use current published thresholds for the selected tax year.',
    ],
];
$assetUrl = static fn (string $path): string => BasePath::asset($path, $basePath);
$routeUrl = static fn (string $targetPage = 'home'): string => BasePath::route($targetPage, $basePath);
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="<?= htmlspecialchars($robotsMeta) ?>">
    <meta name="author" content="No Cap Tools">
    <meta name="format-detection" content="telephone=no">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <link rel="alternate" hreflang="en-GB" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:locale" content="en_GB">
    <meta property="og:type" content="<?= htmlspecialchars($openGraphType) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImageUrl) ?>">
    <meta property="og:image:alt" content="No Cap Tools UK take-home pay calculator preview">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImageUrl) ?>">
    <link rel="icon" href="<?= htmlspecialchars($assetUrl('assets/favicons/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= htmlspecialchars($assetUrl('assets/favicons/favicon-32x32.png')) ?>" sizes="32x32" type="image/png">
    <link rel="icon" href="<?= htmlspecialchars($assetUrl('assets/favicons/favicon-16x16.png')) ?>" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($assetUrl('assets/favicons/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= htmlspecialchars($assetUrl('assets/favicons/site.webmanifest')) ?>">
    <meta name="theme-color" content="#d95d39">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetUrl('assets/css/styles.css')) ?>">
    <?php foreach ($jsonLd as $schema): ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endforeach; ?>
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <a class="brand" href="<?= htmlspecialchars($routeUrl()) ?>">Take Home Pay UK</a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="<?= htmlspecialchars($routeUrl()) ?>">Calculator</a>
            <a href="<?= htmlspecialchars($routeUrl('guides')) ?>">Guides</a>
            <a href="<?= htmlspecialchars($routeUrl('faq')) ?>">FAQ</a>
        </nav>
    </header>

    <?php if ($page === 'home'): ?>
        <main>
        <section class="hero-layout">
            <section class="hero-card">
                <div class="eyebrow">UK PAYE estimate</div>
                <h1>UK take-home pay calculator for salary, pension and student loan estimates.</h1>
                <p class="lede">Calculate 2026/27 UK net salary using PAYE tax, National Insurance, bonus income, pension deductions, and student loan repayments across England, Wales, Scotland, and Northern Ireland.</p>
                <nav class="seo-links" aria-label="On-page sections">
                    <a href="#calculator">Use the calculator</a>
                    <a href="#salary-guides">Read the tax guides</a>
                    <a href="#salary-faq">Check the FAQ</a>
                </nav>
                <div class="hero-metrics">
                    <div>
                        <span>Tax year</span>
                        <strong>2026/27 ready</strong>
                    </div>
                    <div>
                        <span>Includes</span>
                        <strong>Income Tax, NI, pension</strong>
                    </div>
                    <div>
                        <span>Student loans</span>
                        <strong>Plan 1, 2, 4, 5 and PG</strong>
                    </div>
                </div>
            </section>

            <aside class="ad-slot ad-slot--hero" aria-label="Advertisement">
                <div class="ad-slot__label">Sponsored</div>
                <div class="ad-slot__box ad-slot__box--leaderboard">300 x 250 above-the-fold feature ad</div>
            </aside>
        </section>

        <section class="content-band">
            <article class="content-panel">
                <h2>Estimate UK net pay quickly, then inspect the deduction breakdown.</h2>
                <p>The calculator is designed for salary comparisons, budgeting, and sense-checking job offers. It annualises your earnings, applies the selected tax year, and shows how Income Tax, National Insurance, pension treatment, and student loan plans affect your monthly and weekly take-home pay.</p>
                <p>Use the supporting <a href="<?= htmlspecialchars($routeUrl('guides')) ?>">guides</a> for the methodology and the <a href="<?= htmlspecialchars($routeUrl('faq')) ?>">FAQ</a> for common edge cases such as Scottish tax bands, postgraduate loans, or pension salary sacrifice.</p>
            </article>
            <aside class="ad-slot" aria-label="Advertisement">
                <div class="ad-slot__label">Sponsored</div>
                <div class="ad-slot__box ad-slot__box--rectangle">300 x 250 in-content slot</div>
            </aside>
        </section>

        <section id="calculator" class="calculator-layout">
            <section class="panel panel--form">
                <h2>Calculator</h2>
                <button class="mobile-results-bar" type="button" data-mobile-results-bar hidden aria-label="Jump to your results">
                    <span class="mobile-results-bar__prompt">Current take-home pay</span>
                    <span class="mobile-results-bar__grid">
                        <span><strong data-mobile-result="net_annual"><?= $result !== null ? htmlspecialchars($format::currency((float) $result['net_annual'])) : '£0.00' ?></strong><small>Annual</small></span>
                        <span><strong data-mobile-result="net_monthly"><?= $result !== null ? htmlspecialchars($format::currency((float) $result['net_monthly'])) : '£0.00' ?></strong><small>Monthly</small></span>
                        <span><strong data-mobile-result="net_weekly"><?= $result !== null ? htmlspecialchars($format::currency((float) $result['net_weekly'])) : '£0.00' ?></strong><small>Weekly</small></span>
                    </span>
                    <span class="mobile-results-bar__link">Full breakdown</span>
                </button>
                <p class="section-copy">Choose your salary, tax setup, pension treatment, and student loan settings.</p>

                <?php if ($errors !== []): ?>
                    <div class="alert" role="alert" data-calculator-errors>
                        <h3>Check your inputs</h3>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars($routeUrl()) ?>" class="calculator-form" data-calculator-form novalidate>
                    <label>
                        <span>Salary</span>
                        <input type="number" step="0.01" min="0" name="salary" value="<?= htmlspecialchars((string) $form['salary']) ?>" required>
                    </label>

                    <label>
                        <span>Salary period</span>
                        <select name="salary_period">
                            <option value="annual" <?= $form['salary_period'] === 'annual' ? 'selected' : '' ?>>Annual</option>
                            <option value="monthly" <?= $form['salary_period'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="weekly" <?= $form['salary_period'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        </select>
                    </label>

                    <label>
                        <span>Bonus or additional income</span>
                        <input type="number" step="0.01" min="0" name="bonus" value="<?= htmlspecialchars((string) $form['bonus']) ?>">
                    </label>

                    <label>
                        <span>Tax year</span>
                        <select name="tax_year">
                            <?php foreach ($taxYears as $key => $taxYear): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $form['tax_year'] === $key ? 'selected' : '' ?>><?= htmlspecialchars((string) $taxYear['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Region</span>
                        <select name="region">
                            <option value="england" <?= $form['region'] === 'england' ? 'selected' : '' ?>>England</option>
                            <option value="wales" <?= $form['region'] === 'wales' ? 'selected' : '' ?>>Wales</option>
                            <option value="northern-ireland" <?= $form['region'] === 'northern-ireland' ? 'selected' : '' ?>>Northern Ireland</option>
                            <option value="scotland" <?= $form['region'] === 'scotland' ? 'selected' : '' ?>>Scotland</option>
                        </select>
                    </label>

                    <label>
                        <span>Tax code</span>
                        <input type="text" name="tax_code" value="<?= htmlspecialchars((string) $form['tax_code']) ?>" placeholder="1257L">
                    </label>

                    <label>
                        <span>Pension contribution (%)</span>
                        <input type="number" step="0.1" min="0" max="100" name="pension_percent" value="<?= htmlspecialchars((string) $form['pension_percent']) ?>">
                    </label>

                    <label>
                        <span>Pension method</span>
                        <select name="pension_method">
                            <option value="salary_sacrifice" <?= $form['pension_method'] === 'salary_sacrifice' ? 'selected' : '' ?>>Salary sacrifice</option>
                            <option value="net_pay" <?= $form['pension_method'] === 'net_pay' ? 'selected' : '' ?>>Net pay arrangement</option>
                            <option value="post_tax" <?= $form['pension_method'] === 'post_tax' ? 'selected' : '' ?>>Post-tax deduction</option>
                        </select>
                    </label>

                    <label>
                        <span>Student loan plan</span>
                        <select name="student_loan_plan">
                            <option value="none" <?= $form['student_loan_plan'] === 'none' ? 'selected' : '' ?>>None</option>
                            <option value="plan1" <?= $form['student_loan_plan'] === 'plan1' ? 'selected' : '' ?>>Plan 1</option>
                            <option value="plan2" <?= $form['student_loan_plan'] === 'plan2' ? 'selected' : '' ?>>Plan 2</option>
                            <option value="plan4" <?= $form['student_loan_plan'] === 'plan4' ? 'selected' : '' ?>>Plan 4</option>
                            <option value="plan5" <?= $form['student_loan_plan'] === 'plan5' ? 'selected' : '' ?>>Plan 5</option>
                        </select>
                    </label>

                    <label class="checkbox">
                        <input type="hidden" name="has_postgraduate_loan" value="0">
                        <input type="checkbox" name="has_postgraduate_loan" value="1" <?= $form['has_postgraduate_loan'] === '1' ? 'checked' : '' ?> data-postgraduate-toggle>
                        <span>Also repay a postgraduate loan</span>
                    </label>

                    <button type="submit" data-calculator-submit>Calculate take-home pay</button>
                    <p class="live-note" data-live-note hidden>Results update instantly as you edit the form.</p>
                </form>

                <aside class="ad-slot ad-slot--form-compact" aria-label="Advertisement">
                    <div class="ad-slot__label">Sponsored</div>
                    <div class="ad-slot__box ad-slot__box--compact">320 x 100 sticky companion</div>
                </aside>
            </section>

            <section class="panel panel--results" data-calculator-results>
                <div class="results-header">
                    <h2 data-results-heading>Your results</h2>
                    <p class="section-copy">Annualised estimate based on the selected tax year and deductions.</p>
                </div>

                <?php if ($result === null): ?>
                    <div class="empty-state" data-empty-state>
                        <h3>Enter your salary to see your take-home pay.</h3>
                        <p>Your annual, monthly, and weekly net pay will appear here together with a full deduction breakdown.</p>
                    </div>
                    <div class="alert" role="alert" data-calculator-errors hidden>
                        <h3>Check your inputs</h3>
                        <ul></ul>
                    </div>
                    <div class="result-shell" data-result-shell hidden>
                        <div class="result-hero">
                            <div>
                                <span>Net annual pay</span>
                                <strong data-result-field="net_annual"></strong>
                            </div>
                            <div>
                                <span>Net monthly pay</span>
                                <strong data-result-field="net_monthly"></strong>
                            </div>
                            <div>
                                <span>Net weekly pay</span>
                                <strong data-result-field="net_weekly"></strong>
                            </div>
                        </div>

                        <div class="result-grid">
                            <article class="result-card">
                                <span>Gross annual pay</span>
                                <strong data-result-field="gross_annual"></strong>
                            </article>
                            <article class="result-card">
                                <span>Income Tax</span>
                                <strong data-result-field="income_tax"></strong>
                            </article>
                            <article class="result-card">
                                <span>National Insurance</span>
                                <strong data-result-field="national_insurance"></strong>
                            </article>
                            <article class="result-card">
                                <span>Pension</span>
                                <strong data-result-field="pension"></strong>
                            </article>
                            <article class="result-card">
                                <span>Student loans</span>
                                <strong data-result-field="student_loan"></strong>
                            </article>
                            <article class="result-card">
                                <span>Total deductions</span>
                                <strong data-result-field="total_deductions"></strong>
                            </article>
                        </div>

                        <aside class="ad-slot ad-slot--results-inline" aria-label="Advertisement">
                            <div class="ad-slot__label">Sponsored</div>
                            <div class="ad-slot__box ad-slot__box--inline">Responsive in-results ad slot</div>
                        </aside>

                        <div class="breakdown-table" data-breakdown-table>
                            <div><span>Tax year</span><strong data-result-meta="tax_year_label"></strong></div>
                            <div><span>Region</span><strong data-result-meta="region"></strong></div>
                            <div><span>Tax code</span><strong data-result-meta="tax_code"></strong></div>
                            <div><span>Effective deduction rate</span><strong data-result-meta="effective_tax_rate"></strong></div>
                        </div>

                        <div class="assumptions">
                            <h3>Assumptions</h3>
                            <ul data-assumptions-list></ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert" role="alert" data-calculator-errors hidden>
                        <h3>Check your inputs</h3>
                        <ul></ul>
                    </div>
                    <div class="empty-state" data-empty-state hidden>
                        <h3>Enter your salary to see your take-home pay.</h3>
                        <p>Your annual, monthly, and weekly net pay will appear here together with a full deduction breakdown.</p>
                    </div>
                    <div class="result-shell" data-result-shell>
                        <div class="result-hero">
                        <div>
                            <span>Net annual pay</span>
                            <strong data-result-field="net_annual"><?= htmlspecialchars($format::currency((float) $result['net_annual'])) ?></strong>
                        </div>
                        <div>
                            <span>Net monthly pay</span>
                            <strong data-result-field="net_monthly"><?= htmlspecialchars($format::currency((float) $result['net_monthly'])) ?></strong>
                        </div>
                        <div>
                            <span>Net weekly pay</span>
                            <strong data-result-field="net_weekly"><?= htmlspecialchars($format::currency((float) $result['net_weekly'])) ?></strong>
                        </div>
                    </div>

                    <div class="result-grid">
                        <article class="result-card">
                            <span>Gross annual pay</span>
                            <strong data-result-field="gross_annual"><?= htmlspecialchars($format::currency((float) $result['gross_annual'])) ?></strong>
                        </article>
                        <article class="result-card">
                            <span>Income Tax</span>
                            <strong data-result-field="income_tax"><?= htmlspecialchars($format::currency((float) $result['income_tax'])) ?></strong>
                        </article>
                        <article class="result-card">
                            <span>National Insurance</span>
                            <strong data-result-field="national_insurance"><?= htmlspecialchars($format::currency((float) $result['national_insurance'])) ?></strong>
                        </article>
                        <article class="result-card">
                            <span>Pension</span>
                            <strong data-result-field="pension"><?= htmlspecialchars($format::currency((float) $result['pension'])) ?></strong>
                        </article>
                        <article class="result-card">
                            <span>Student loans</span>
                            <strong data-result-field="student_loan"><?= htmlspecialchars($format::currency((float) $result['student_loan'])) ?></strong>
                        </article>
                        <article class="result-card">
                            <span>Total deductions</span>
                            <strong data-result-field="total_deductions"><?= htmlspecialchars($format::currency((float) $result['total_deductions'])) ?></strong>
                        </article>
                    </div>

                    <aside class="ad-slot ad-slot--results-inline" aria-label="Advertisement">
                        <div class="ad-slot__label">Sponsored</div>
                        <div class="ad-slot__box ad-slot__box--inline">Responsive in-results ad slot</div>
                    </aside>

                    <div class="breakdown-table" data-breakdown-table>
                        <div><span>Tax year</span><strong data-result-meta="tax_year_label"><?= htmlspecialchars((string) $result['tax_year_label']) ?></strong></div>
                        <div><span>Region</span><strong data-result-meta="region"><?= htmlspecialchars(ucwords(str_replace('-', ' ', (string) $result['region']))) ?></strong></div>
                        <div><span>Tax code</span><strong data-result-meta="tax_code"><?= htmlspecialchars((string) $result['tax_code']) ?></strong></div>
                        <div><span>Effective deduction rate</span><strong data-result-meta="effective_tax_rate"><?= htmlspecialchars($format::percent((float) $result['effective_tax_rate'])) ?></strong></div>
                        <?php foreach ($result['student_loan_breakdown'] as $loanLabel => $loanValue): ?>
                            <div><span><?= htmlspecialchars((string) $loanLabel) ?></span><strong><?= htmlspecialchars($format::currency((float) $loanValue)) ?></strong></div>
                        <?php endforeach; ?>
                    </div>

                    <div class="assumptions">
                        <h3>Assumptions</h3>
                        <ul data-assumptions-list>
                            <?php foreach ($result['assumptions'] as $assumption): ?>
                                <li><?= htmlspecialchars((string) $assumption) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    </div>
                <?php endif; ?>
            </section>
        </section>

        <section id="salary-guides" class="guides-grid">
            <?php foreach ($guides as $guide): ?>
                <article class="guide-card">
                    <h3><?= htmlspecialchars($guide['title']) ?></h3>
                    <p><?= htmlspecialchars($guide['body']) ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section id="salary-faq" class="faq-band">
            <div class="faq-card">
                <h2>Frequently asked questions about UK take-home pay</h2>
                <?php foreach ($faqItems as $faqItem): ?>
                    <details>
                        <summary><?= htmlspecialchars($faqItem['question']) ?></summary>
                        <p><?= htmlspecialchars($faqItem['answer']) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
        </main>
    <?php else: ?>
        <main class="page-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= htmlspecialchars($routeUrl()) ?>">Calculator</a>
                <span>/</span>
                <span><?= htmlspecialchars(match ($page) {
                    'guides' => 'Guides',
                    'faq' => 'FAQ',
                    'privacy' => 'Privacy Policy',
                    'cookies' => 'Cookie Policy',
                    default => 'Page',
                }) ?></span>
            </nav>
            <?php if ($page === 'guides'): ?>
                <section class="legal-card">
                    <h1>UK tax guides</h1>
                    <p>These guides explain how the calculator translates gross salary into annual, monthly, and weekly take-home pay. They are written to make the assumptions behind PAYE deductions explicit rather than hiding the calculation steps.</p>
                    <?php foreach ($guides as $guide): ?>
                        <article class="guide-row">
                            <h2><?= htmlspecialchars($guide['title']) ?></h2>
                            <p><?= htmlspecialchars($guide['body']) ?></p>
                            <p class="guide-formula"><code><?= htmlspecialchars((string) $guide['formula']) ?></code></p>
                            <ol class="guide-steps">
                                <?php foreach ($guide['steps'] as $step): ?>
                                    <li><?= htmlspecialchars((string) $step) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php elseif ($page === 'faq'): ?>
                <section class="legal-card">
                    <h1>Frequently asked questions</h1>
                    <p>The calculator uses the published thresholds included in the selected tax year and displays the assumptions used for each result.</p>
                    <?php foreach ($faqItems as $faqItem): ?>
                        <article class="guide-row">
                            <h2><?= htmlspecialchars($faqItem['question']) ?></h2>
                            <p><?= htmlspecialchars($faqItem['answer']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php elseif ($page === 'privacy'): ?>
                <section class="legal-card">
                    <h1>Privacy policy</h1>
                    <p>This calculator does not require an account and does not intentionally store the salary, tax code, pension, or student loan values that you enter. Any future analytics, advertising, or user-tracking changes should be documented here before they are released.</p>
                    <p>For product and methodology context, return to the <a href="<?= htmlspecialchars($routeUrl()) ?>">calculator</a> or review the <a href="<?= htmlspecialchars($routeUrl('faq')) ?>">FAQ</a>.</p>
                </section>
            <?php elseif ($page === 'cookies'): ?>
                <section class="legal-card">
                    <h1>Cookie policy</h1>
                    <p>This site can operate without functional cookies. If analytics, advertising, or personalisation tags are added later, they should only be deployed with a clear notice and any consent controls required by UK law.</p>
                    <p>For product and methodology context, return to the <a href="<?= htmlspecialchars($routeUrl()) ?>">calculator</a> or review the <a href="<?= htmlspecialchars($routeUrl('guides')) ?>">guides</a>.</p>
                </section>
            <?php elseif ($page === 'not-found'): ?>
                <section class="legal-card">
                    <h1>Page not found</h1>
                    <p>The page you requested does not exist. Use the main calculator, guides, or FAQ to continue exploring the site.</p>
                    <p><a href="<?= htmlspecialchars($routeUrl()) ?>">Return to the UK take-home pay calculator</a></p>
                </section>
            <?php endif; ?>
        </main>
    <?php endif; ?>

    <footer class="site-footer">
        <div>Estimate only. Figures can differ from payroll outputs.</div>
        <nav aria-label="Footer navigation">
            <a href="<?= htmlspecialchars($routeUrl('privacy')) ?>">Privacy</a>
            <a href="<?= htmlspecialchars($routeUrl('cookies')) ?>">Cookies</a>
            <a href="<?= htmlspecialchars($routeUrl('faq')) ?>">FAQ</a>
        </nav>
    </footer>
</div>
<?php if ($page === 'home'): ?>
    <script>
        window.takeHomePayCalculatorConfig = <?= json_encode($calculatorBootstrap, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>
    <script src="<?= htmlspecialchars($assetUrl('assets/js/take-home-pay-calculator.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('assets/js/calculator-form.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
