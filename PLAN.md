# UK Take Home Pay Calculator Website Plan

## Project Name
+ UK Take Home Pay Calculator

## Goal
Create a polished PHP, HTML, and CSS website that helps UK users estimate their take-home pay quickly and confidently. The site should feel premium, fast, trustworthy, and ready for later monetization through website ads without damaging the user experience.

## Core Product Direction
The website should focus on clarity first:

+ fast salary input
+ clear tax and deduction breakdown
+ monthly, weekly, and yearly net pay views
+ strong mobile experience
+ visual trust signals for UK users
+ ad-ready layout with dedicated placements

The first release should be lightweight and server-rendered with PHP, with no heavy framework requirement.

## Design Direction
The site should look modern and editorial rather than generic.

+ Clean, high-contrast layout
+ Strong typography with a premium finance-tool feel
+ Soft gradients or subtle layered backgrounds
+ Bright accent color used sparingly for calls to action and key figures
+ Spacious card-based sections
+ Large result panels for net pay, tax, NI, pension, and deductions
+ Mobile-first responsive design

Suggested tone:

+ trustworthy
+ premium
+ simple
+ fast
+ professional

## Target Users
+ UK employees checking salary after tax
+ job seekers comparing offers
+ freelancers or contractors wanting a baseline salary estimate
+ users searching for monthly or annual take-home pay calculators

## Phase 1 Features

### Calculator Inputs
+ Annual salary
+ Monthly salary option
+ Tax year selector
+ Pension contribution
+ Student loan option
+ Tax code
+ Pay frequency
+ Bonus or additional income

### Calculator Outputs
+ Net annual pay
+ Net monthly pay
+ Net weekly pay
+ Income tax
+ National Insurance
+ Pension deduction
+ Student loan deduction
+ Effective tax rate
+ Total deductions

### UX Features
+ Instant recalculation on submit
+ Sticky results summary on desktop
+ Scroll-friendly stacked layout on mobile
+ Clear labels and helper text
+ Empty state for first load
+ Validation for invalid or missing salary values

## Future Feature Roadmap
+ Scottish tax band support
+ self-employed mode
+ contractor day rate conversion
+ side-by-side salary comparison
+ savings goal calculator
+ inflation-adjusted salary view
+ location-based salary insights
+ downloadable PDF summary
+ shareable result links

## Suggested Site Structure

### Pages
+ Home page with main calculator
+ About page explaining assumptions
+ Tax guide articles for SEO
+ FAQ page
+ Privacy policy
+ Cookie policy
+ Contact page

### Homepage Layout
1. Hero section
2. Main calculator card
3. Results breakdown section
4. Key benefits or trust section
5. SEO content block about UK tax
6. FAQ accordion
7. Ad placements integrated between lower-priority content blocks

## Ad Integration Plan
Ads should be built into the layout from the start so the page remains balanced once monetization is enabled.

### Ad Placement Strategy
+ One leaderboard slot near or below the hero area
+ One in-content ad slot between educational content sections
+ One sidebar slot on desktop beside lower-page content
+ One mobile sticky ad option only if performance and UX remain acceptable

### UX Rules For Ads
+ Never interrupt the calculator form
+ Never place ads between salary input and core result output
+ Keep primary results visible without ad clutter
+ Reserve consistent ad containers to avoid layout shift
+ Label ad areas clearly if required by the ad network

### Technical Ad Readiness
+ Use PHP include partials for ad blocks
+ Create placeholder containers even before live ad code is added
+ Ensure CSS supports standard ad sizes like 728x90, 300x250, and responsive units
+ Keep ad markup isolated for easy AdSense or other network integration

## Technical Stack
+ PHP for routing and template rendering
+ HTML5 semantic markup
+ CSS3 with custom properties
+ Minimal vanilla JavaScript only if needed for enhanced interactivity

## Suggested File Structure

```text
uk-take-home-pay-calculator/
├── .git/
├── PLAN.md
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   │   └── styles.css
│   │   ├── js/
│   │   │   └── app.js
│   │   └── img/
├── src/
│   ├── includes/
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── calculator-form.php
│   │   ├── results-panel.php
│   │   └── ad-slot.php
│   ├── data/
│   │   └── tax-bands.php
│   └── services/
│       └── TakeHomePayCalculator.php
└── docs/
    └── content-plan.md
```

## Calculation Logic Notes
The calculator should be designed so that tax logic is separated from the UI.

+ PHP service class handles calculations
+ tax rates and thresholds stored in dedicated config or data files
+ output formatting handled separately from business logic
+ future tax-year updates should require minimal code changes

## SEO Strategy
+ Target search phrases like "UK take home pay calculator", "salary after tax UK", and "monthly take home pay calculator UK"
+ Include plain-language explanatory sections below the calculator
+ Build article pages around tax bands, NI, pension, and student loans
+ Use FAQ schema and article schema where relevant
+ Make page speed and mobile layout a priority

## Accessibility Requirements
+ Strong color contrast
+ Keyboard-friendly form flow
+ Visible focus states
+ Proper label and input associations
+ Error messaging that is clear and screen-reader friendly
+ Semantic heading structure

## Performance Requirements
+ lightweight CSS
+ minimal JavaScript
+ optimized images
+ no blocking third-party code above the fold except where essential
+ ad scripts deferred where possible

## Content Trust Signals
+ Explain assumptions clearly
+ Show tax year in use
+ Include disclaimer that estimates may vary
+ Add short notes about PAYE, NI, pension, and student loan handling

## Implementation Order
1. Create base PHP project structure
2. Build homepage layout and visual system
3. Build calculator form
4. Implement UK tax calculation logic
5. Render results breakdown
6. Add ad placeholder components
7. Add SEO content sections and FAQ
8. Test mobile responsiveness and validation
9. Prepare content pages and legal pages

## Success Criteria
+ Users can calculate UK take-home pay in under 30 seconds
+ Results are easy to understand on mobile and desktop
+ Design feels modern enough to compete with commercial finance tools
+ Ad placements are built in without hurting the core utility of the page
+ Tax logic is maintainable for future updates
