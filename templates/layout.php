<?php

declare(strict_types=1);

use TakeHomePay\Support\Format;

/** @var string $page */
/** @var string $title */
/** @var array<string, array<string, mixed>> $taxYears */
/** @var array<string, mixed> $form */
/** @var array<string, mixed>|null $result */
/** @var array<int, string> $errors */
/** @var array<int, array<string, string>> $guides */
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
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="Calculate UK take-home pay with current tax, National Insurance, pension, and student loan assumptions.">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <a class="brand" href="index.php">Take Home Pay UK</a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="index.php">Calculator</a>
            <a href="index.php?page=guides">Guides</a>
            <a href="index.php?page=faq">FAQ</a>
        </nav>
    </header>

    <?php if ($page === 'home'): ?>
        <main class="calculator-layout">
            <section class="panel panel--form">
                <h2>Calculator</h2>
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

                <form method="post" action="index.php" class="calculator-form" data-calculator-form novalidate>
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
            </section>

            <section class="panel panel--results" data-calculator-results>
                <div class="results-header">
                    <h2>Your results</h2>
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
        </main>

        <main class="hero-layout">
            <section class="hero-card">
                <div class="eyebrow">UK PAYE estimate</div>
                <h1>Calculate your UK take-home pay in seconds.</h1>
                <p class="lede">Built for salary comparisons, offer decisions, and fast after-tax estimates with pension and student loan support.</p>
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
                <div class="ad-slot__label">Ad placement</div>
                <div class="ad-slot__box ad-slot__box--leaderboard">728 x 90 leaderboard</div>
            </aside>
        </main>

        <section class="content-band">
            <article class="content-panel">
                <h2>Why this calculator is useful</h2>
                <p>Offer comparisons are usually framed around gross salary. What matters in practice is the cash left after PAYE tax, National Insurance, pension, and loan deductions. This calculator keeps that view front and centre.</p>
            </article>
            <aside class="ad-slot" aria-label="Advertisement">
                <div class="ad-slot__label">Ad placement</div>
                <div class="ad-slot__box ad-slot__box--rectangle">300 x 250 in-content slot</div>
            </aside>
        </section>

        <section class="guides-grid">
            <?php foreach ($guides as $guide): ?>
                <article class="guide-card">
                    <h3><?= htmlspecialchars($guide['title']) ?></h3>
                    <p><?= htmlspecialchars($guide['body']) ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="faq-band">
            <div class="faq-card">
                <h2>Frequently asked questions</h2>
                <details>
                    <summary>Does this cover Scotland?</summary>
                    <p>Yes. Scottish tax bands are available when you choose Scotland or use a tax code that starts with <code>S</code>.</p>
                </details>
                <details>
                    <summary>Can I include student loans and pension?</summary>
                    <p>Yes. The calculator supports undergraduate student loan plans, postgraduate loans, and three pension treatments.</p>
                </details>
                <details>
                    <summary>Where would ads appear?</summary>
                    <p>Dedicated ad containers sit around supporting content, not between the core input form and the results.</p>
                </details>
            </div>
        </section>
    <?php else: ?>
        <main class="page-content">
            <?php if ($page === 'guides'): ?>
                <section class="legal-card">
                    <h1>UK tax guides</h1>
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
                </section>
            <?php elseif ($page === 'privacy'): ?>
                <section class="legal-card">
                    <h1>Privacy policy</h1>
                    <p>This demo calculator does not store salary inputs on the server. If analytics or advertising is added later, the policy should be expanded before launch.</p>
                </section>
            <?php elseif ($page === 'cookies'): ?>
                <section class="legal-card">
                    <h1>Cookie policy</h1>
                    <p>This site can operate without functional cookies. Advertising or analytics tags should only be added with a clear cookie notice and consent flow where required.</p>
                </section>
            <?php endif; ?>
        </main>
    <?php endif; ?>

    <footer class="site-footer">
        <div>Estimate only. Figures can differ from payroll outputs.</div>
        <nav aria-label="Footer navigation">
            <a href="index.php?page=privacy">Privacy</a>
            <a href="index.php?page=cookies">Cookies</a>
            <a href="index.php?page=faq">FAQ</a>
        </nav>
    </footer>
</div>
<?php if ($page === 'home'): ?>
    <script>
        window.takeHomePayCalculatorConfig = <?= json_encode($calculatorBootstrap, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>
    <script src="assets/js/take-home-pay-calculator.js"></script>
    <script src="assets/js/calculator-form.js"></script>
<?php endif; ?>
</body>
</html>
