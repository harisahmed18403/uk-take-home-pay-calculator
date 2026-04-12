class TakeHomePayCalculator {
    constructor(config) {
        this.taxYears = config.taxYears || {};
        this.assumptions = config.assumptions || [];
    }

    validate(input) {
        const errors = [];
        const salary = this.toNumber(input.salary);
        const bonus = this.toNumber(input.bonus);
        const pensionPercent = this.toNumber(input.pension_percent);

        if (String(input.salary ?? "").trim() === "" || salary <= 0) {
            errors.push("Enter a salary greater than zero.");
        }

        if (String(input.bonus ?? "").trim() !== "" && bonus < 0) {
            errors.push("Bonus must be zero or more.");
        }

        if (String(input.pension_percent ?? "").trim() !== "" && (pensionPercent < 0 || pensionPercent > 100)) {
            errors.push("Pension contribution must be between 0 and 100 percent.");
        }

        return errors;
    }

    calculate(rawInput) {
        const input = this.normalizeInput(rawInput);
        const taxYearData = this.taxYears[input.tax_year] || this.taxYears["2026-2027"];
        const taxCode = this.parseTaxCode(input.tax_code);
        const region = taxCode.region_hint || input.region;

        const grossAnnual = this.round(this.annualizeSalary(input.salary, input.salary_period) + input.bonus);
        const pensionAnnual = this.round(grossAnnual * (input.pension_percent / 100));
        const taxableEarnings = grossAnnual - (input.pension_method === "post_tax" ? 0 : pensionAnnual);
        const niableEarnings = grossAnnual - (input.pension_method === "salary_sacrifice" ? pensionAnnual : 0);

        const incomeTax = this.round(this.calculateIncomeTax(taxableEarnings, taxCode, taxYearData, region));
        const nationalInsurance = this.round(this.calculateNationalInsurance(niableEarnings, taxYearData));
        const studentLoan = this.calculateStudentLoan(
            grossAnnual,
            input.student_loan_plan,
            input.has_postgraduate_loan,
            taxYearData
        );
        const postTaxPension = input.pension_method === "post_tax" ? pensionAnnual : 0;
        const totalDeductions = this.round(incomeTax + nationalInsurance + studentLoan.total + pensionAnnual);
        const netAnnual = this.round(Math.max(0, grossAnnual - totalDeductions));

        return {
            inputs: input,
            tax_year: input.tax_year,
            tax_year_label: taxYearData.label,
            region,
            tax_code: taxCode.code,
            gross_annual: grossAnnual,
            taxable_annual: this.round(taxableEarnings),
            niable_annual: this.round(niableEarnings),
            income_tax: incomeTax,
            national_insurance: nationalInsurance,
            student_loan: this.round(studentLoan.total),
            student_loan_breakdown: studentLoan.breakdown,
            pension: pensionAnnual,
            post_tax_pension: this.round(postTaxPension),
            net_annual: netAnnual,
            net_monthly: this.round(netAnnual / 12),
            net_weekly: this.round(netAnnual / 52),
            total_deductions: totalDeductions,
            effective_tax_rate: grossAnnual > 0 ? this.round(totalDeductions / grossAnnual, 4) : 0,
            assumptions: [...this.assumptions],
        };
    }

    normalizeInput(input) {
        const salaryPeriod = input.salary_period;
        const region = input.region;
        const pensionMethod = input.pension_method;
        const studentLoanPlan = input.student_loan_plan;

        return {
            salary: Math.max(0, this.toNumber(input.salary)),
            salary_period: ["annual", "monthly", "weekly"].includes(salaryPeriod) ? salaryPeriod : "annual",
            bonus: Math.max(0, this.toNumber(input.bonus)),
            tax_year: input.tax_year in this.taxYears ? input.tax_year : "2026-2027",
            region: ["england", "wales", "northern-ireland", "scotland"].includes(region) ? region : "england",
            tax_code: String(input.tax_code || "1257L").trim(),
            pension_percent: Math.min(100, Math.max(0, this.toNumber(input.pension_percent))),
            pension_method: ["salary_sacrifice", "net_pay", "post_tax"].includes(pensionMethod) ? pensionMethod : "salary_sacrifice",
            student_loan_plan: ["none", "plan1", "plan2", "plan4", "plan5"].includes(studentLoanPlan) ? studentLoanPlan : "none",
            has_postgraduate_loan: Boolean(input.has_postgraduate_loan),
        };
    }

    parseTaxCode(rawCode) {
        let code = String(rawCode || "").trim().toUpperCase();
        let regionHint = null;

        if (code === "") {
            code = "1257L";
        }

        if (code.startsWith("S")) {
            regionHint = "scotland";
            code = code.slice(1);
        } else if (code.startsWith("C")) {
            regionHint = "wales";
            code = code.slice(1);
        }

        if (code === "BR") {
            return { code: rawCode !== "" ? String(rawCode).trim().toUpperCase() : "1257L", allowance: 0, mode: "basic_only", region_hint: regionHint };
        }

        if (code === "D0") {
            return { code: rawCode !== "" ? String(rawCode).trim().toUpperCase() : "1257L", allowance: 0, mode: "higher_only", region_hint: regionHint };
        }

        if (code === "D1") {
            return { code: rawCode !== "" ? String(rawCode).trim().toUpperCase() : "1257L", allowance: 0, mode: "additional_only", region_hint: regionHint };
        }

        if (code === "NT") {
            return { code: rawCode !== "" ? String(rawCode).trim().toUpperCase() : "1257L", allowance: 0, mode: "no_tax", region_hint: regionHint };
        }

        const negativeAllowance = code.match(/^K(\d+)[A-Z]*$/);
        if (negativeAllowance) {
            return {
                code: `${regionHint === "scotland" ? "S" : regionHint === "wales" ? "C" : ""}${code}`,
                allowance: -(Number(negativeAllowance[1]) * 10),
                mode: "standard",
                region_hint: regionHint,
            };
        }

        const positiveAllowance = code.match(/^(\d+)[A-Z]*$/);
        if (positiveAllowance) {
            return {
                code: `${regionHint === "scotland" ? "S" : regionHint === "wales" ? "C" : ""}${code}`,
                allowance: Number(positiveAllowance[1]) * 10,
                mode: "standard",
                region_hint: regionHint,
            };
        }

        return {
            code: "1257L",
            allowance: 12570,
            mode: "standard",
            region_hint: regionHint,
        };
    }

    annualizeSalary(salary, period) {
        if (period === "monthly") {
            return salary * 12;
        }

        if (period === "weekly") {
            return salary * 52;
        }

        return salary;
    }

    calculateIncomeTax(taxableEarnings, taxCode, taxYearData, region) {
        if (taxCode.mode === "no_tax") {
            return 0;
        }

        if (taxCode.mode === "basic_only") {
            return taxableEarnings * 0.2;
        }

        if (taxCode.mode === "higher_only") {
            return taxableEarnings * 0.4;
        }

        if (taxCode.mode === "additional_only") {
            return taxableEarnings * 0.45;
        }

        let allowance = taxCode.allowance ?? taxYearData.income_tax.personal_allowance;

        if (allowance > 0 && taxableEarnings > taxYearData.income_tax.allowance_taper_start) {
            allowance = Math.max(0, allowance - Math.floor((taxableEarnings - taxYearData.income_tax.allowance_taper_start) / 2));
        }

        let taxableAfterAllowance = Math.max(0, taxableEarnings - allowance);
        if (allowance < 0) {
            taxableAfterAllowance = taxableEarnings + Math.abs(allowance);
        }

        const bands = taxYearData.income_tax.regions[region] || taxYearData.income_tax.regions.england;

        return this.applyBands(taxableAfterAllowance, bands);
    }

    applyBands(taxableAmount, bands) {
        let remaining = taxableAmount;
        let lastLimit = 0;
        let tax = 0;

        for (const band of bands) {
            if (remaining <= 0) {
                break;
            }

            if (band.limit === null) {
                tax += remaining * band.rate;
                break;
            }

            const bandWidth = band.limit - lastLimit;
            const portion = Math.min(remaining, bandWidth);
            tax += portion * band.rate;
            remaining -= portion;
            lastLimit = band.limit;
        }

        return tax;
    }

    calculateNationalInsurance(niableEarnings, taxYearData) {
        const primaryThreshold = taxYearData.ni.primary_threshold;
        const upperEarningsLimit = taxYearData.ni.upper_earnings_limit;
        const mainBand = Math.max(0, Math.min(niableEarnings, upperEarningsLimit) - primaryThreshold);
        const additionalBand = Math.max(0, niableEarnings - upperEarningsLimit);

        return (mainBand * taxYearData.ni.main_rate) + (additionalBand * taxYearData.ni.additional_rate);
    }

    calculateStudentLoan(grossAnnual, studentLoanPlan, hasPostgraduateLoan, taxYearData) {
        const breakdown = {};
        let total = 0;
        const plans = taxYearData.student_loans;

        if (studentLoanPlan !== "none" && plans[studentLoanPlan]) {
            const undergrad = Math.max(0, (grossAnnual - plans[studentLoanPlan].threshold) * plans[studentLoanPlan].rate);
            breakdown[plans[studentLoanPlan].label] = this.round(undergrad);
            total += undergrad;
        }

        if (hasPostgraduateLoan) {
            const postgraduate = Math.max(0, (grossAnnual - plans.postgraduate.threshold) * plans.postgraduate.rate);
            breakdown[plans.postgraduate.label] = this.round(postgraduate);
            total += postgraduate;
        }

        return {
            total: this.round(total),
            breakdown,
        };
    }

    toNumber(value) {
        const parsed = Number(value);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    round(value, precision = 2) {
        return Number(value.toFixed(precision));
    }
}

window.TakeHomePayCalculator = TakeHomePayCalculator;
