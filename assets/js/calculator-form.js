(function () {
    const config = window.takeHomePayCalculatorConfig;
    const Calculator = window.TakeHomePayCalculator;

    if (!config || !Calculator) {
        return;
    }

    const form = document.querySelector("[data-calculator-form]");
    const errorsBox = document.querySelector("[data-calculator-errors]");
    const emptyState = document.querySelector("[data-empty-state]");
    const resultShell = document.querySelector("[data-result-shell]");
    const breakdownTable = document.querySelector("[data-breakdown-table]");
    const assumptionsList = document.querySelector("[data-assumptions-list]");
    const liveNote = document.querySelector("[data-live-note]");
    const submitButton = document.querySelector("[data-calculator-submit]");
    const postgraduateToggle = document.querySelector("[data-postgraduate-toggle]");
    const mobileResultsBar = document.querySelector("[data-mobile-results-bar]");
    const resultsSection = document.querySelector("[data-calculator-results]");

    if (!form || !errorsBox || !emptyState || !resultShell || !breakdownTable || !assumptionsList || !resultsSection) {
        return;
    }

    const calculator = new Calculator(config);
    const currencyFormatter = new Intl.NumberFormat("en-GB", {
        style: "currency",
        currency: "GBP",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const percentFormatter = new Intl.NumberFormat("en-GB", {
        style: "percent",
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    });
    const resultFields = new Map(
        Array.from(document.querySelectorAll("[data-result-field]")).map((node) => [node.getAttribute("data-result-field"), node])
    );
    const resultMeta = new Map(
        Array.from(document.querySelectorAll("[data-result-meta]")).map((node) => [node.getAttribute("data-result-meta"), node])
    );
    const mobileResultFields = new Map(
        Array.from(document.querySelectorAll("[data-mobile-result]")).map((node) => [node.getAttribute("data-mobile-result"), node])
    );
    const baseBreakdownCount = 4;
    let resultsAreInView = false;
    let resultsObserver = null;

    form.dataset.live = "true";

    if (submitButton) {
        submitButton.textContent = "Recalculate";
    }

    if (liveNote) {
        liveNote.hidden = false;
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        update();
    });

    form.addEventListener("input", update);
    form.addEventListener("change", update);

    if (postgraduateToggle) {
        postgraduateToggle.addEventListener("change", update);
    }

    if (mobileResultsBar) {
        mobileResultsBar.addEventListener("click", function () {
            mobileResultsBar.hidden = true;

            resultsSection.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        });

        window.addEventListener("scroll", syncMobileResultsBar, { passive: true });
        window.addEventListener("resize", syncMobileResultsBar);
        initializeResultsObserver();
    }

    update();

    function update() {
        const rawInput = readForm();
        const salaryIsBlank = String(form.elements.salary.value || "").trim() === "";
        const errors = calculator.validate(rawInput);

        renderErrors(errors);

        if (salaryIsBlank) {
            showEmptyState();
            return;
        }

        if (errors.length > 0) {
            hideResults();
            return;
        }

        const result = calculator.calculate(rawInput);
        renderResult(result);
    }

    function readForm() {
        return {
            salary: form.elements.salary.value,
            salary_period: form.elements.salary_period.value,
            bonus: form.elements.bonus.value,
            tax_year: form.elements.tax_year.value,
            region: form.elements.region.value,
            tax_code: form.elements.tax_code.value,
            pension_percent: form.elements.pension_percent.value,
            pension_method: form.elements.pension_method.value,
            student_loan_plan: form.elements.student_loan_plan.value,
            has_postgraduate_loan: Boolean(postgraduateToggle && postgraduateToggle.checked),
        };
    }

    function renderErrors(errors) {
        const list = errorsBox.querySelector("ul");
        list.innerHTML = "";

        if (errors.length === 0) {
            errorsBox.hidden = true;
            return;
        }

        for (const error of errors) {
            const item = document.createElement("li");
            item.textContent = error;
            list.appendChild(item);
        }

        errorsBox.hidden = false;
    }

    function showEmptyState() {
        errorsBox.hidden = true;
        emptyState.hidden = false;
        resultShell.hidden = true;
        syncMobileResultsBar();
    }

    function hideResults() {
        emptyState.hidden = true;
        resultShell.hidden = true;
        syncMobileResultsBar();
    }

    function renderResult(result) {
        emptyState.hidden = true;
        resultShell.hidden = false;

        setText(resultFields, "net_annual", currencyFormatter.format(result.net_annual));
        setText(resultFields, "net_monthly", currencyFormatter.format(result.net_monthly));
        setText(resultFields, "net_weekly", currencyFormatter.format(result.net_weekly));
        setText(resultFields, "gross_annual", currencyFormatter.format(result.gross_annual));
        setText(resultFields, "income_tax", currencyFormatter.format(result.income_tax));
        setText(resultFields, "national_insurance", currencyFormatter.format(result.national_insurance));
        setText(resultFields, "pension", currencyFormatter.format(result.pension));
        setText(resultFields, "student_loan", currencyFormatter.format(result.student_loan));
        setText(resultFields, "total_deductions", currencyFormatter.format(result.total_deductions));

        setText(resultMeta, "tax_year_label", result.tax_year_label);
        setText(resultMeta, "region", formatRegion(result.region));
        setText(resultMeta, "tax_code", result.tax_code);
        setText(resultMeta, "effective_tax_rate", percentFormatter.format(result.effective_tax_rate));

        renderBreakdown(result.student_loan_breakdown);
        renderAssumptions(result.assumptions);
        setText(mobileResultFields, "net_annual", currencyFormatter.format(result.net_annual));
        setText(mobileResultFields, "net_monthly", currencyFormatter.format(result.net_monthly));
        setText(mobileResultFields, "net_weekly", currencyFormatter.format(result.net_weekly));
        syncMobileResultsBar();
    }

    function renderBreakdown(studentLoanBreakdown) {
        while (breakdownTable.children.length > baseBreakdownCount) {
            breakdownTable.removeChild(breakdownTable.lastChild);
        }

        Object.entries(studentLoanBreakdown).forEach(function ([label, value]) {
            const row = document.createElement("div");
            const labelNode = document.createElement("span");
            const valueNode = document.createElement("strong");

            labelNode.textContent = label;
            valueNode.textContent = currencyFormatter.format(value);

            row.appendChild(labelNode);
            row.appendChild(valueNode);
            breakdownTable.appendChild(row);
        });
    }

    function renderAssumptions(assumptions) {
        assumptionsList.innerHTML = "";

        assumptions.forEach(function (assumption) {
            const item = document.createElement("li");
            item.textContent = assumption;
            assumptionsList.appendChild(item);
        });
    }

    function setText(map, key, value) {
        const node = map.get(key);

        if (node) {
            node.textContent = value;
        }
    }

    function syncMobileResultsBar() {
        if (!mobileResultsBar) {
            return;
        }

        const isMobile = window.matchMedia("(max-width: 640px)").matches;
        const hasVisibleResults = !resultShell.hidden;

        if (!isMobile || !hasVisibleResults) {
            mobileResultsBar.hidden = true;
            return;
        }

        const resultsVisible = resultsAreInView || isResultsSectionVisible();

        mobileResultsBar.hidden = resultsVisible;
    }

    function initializeResultsObserver() {
        if (!("IntersectionObserver" in window)) {
            return;
        }

        resultsObserver = new IntersectionObserver(function (entries) {
            const entry = entries[0];
            resultsAreInView = entry ? entry.isIntersecting : false;
            syncMobileResultsBar();
        }, {
            threshold: [0, 0.01],
        });

        resultsObserver.observe(resultsSection);
    }

    function isResultsSectionVisible() {
        const resultsRect = resultsSection.getBoundingClientRect();

        return resultsRect.top < window.innerHeight && resultsRect.bottom > 0;
    }

    function formatRegion(region) {
        return region
            .split("-")
            .map(function (part) {
                return part.charAt(0).toUpperCase() + part.slice(1);
            })
            .join(" ");
    }
}());
