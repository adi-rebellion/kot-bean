# Growth Playbook Library

This is a living catalog of GTM and growth mechanisms. It is not a list of tricks to copy. Each pattern works only when its underlying customer behavior, product shape, economics, and distribution access are present.

Use this library to generate candidate experiments. Do not present a company example as proof that the same tactic will work for Deepesh. Label each recommendation as `documented pattern`, `analogy`, or `new hypothesis`, then test it against the actual wedge.

## How to use the library

For each GTM problem:

1. Identify the bottleneck: access, trust, demand, conversion, activation, retention, referral, expansion, or economics.
2. Diagnose product/service shape, buyer behavior, time to value, trust burden, economics, reachable access, and delivery capacity.
3. Select no more than three patterns whose mechanism directly addresses that bottleneck and fits those conditions.
4. Score each candidate from 0 to 3 on customer behavior, time to evidence, trust/buying process, economics, access, delivery capacity, and repeatability.
5. Design one smallest experiment for the highest-fit candidate, with a fallback only if the first motion fails for a known reason.
6. Record the result, including what failed and why, in the active experiment log.
7. Promote a pattern only when the observed evidence supports it in this market, not because it worked elsewhere.

Reject a pattern when its required behavior is absent. For example, do not recommend product-led growth when the buyer cannot experience value without a long implementation; do not recommend referrals before customers have a proven win; do not recommend a marketplace listing when the target customer does not search that marketplace; and do not recommend broad content when the problem has no meaningful search or education path.

## Motion-fit worksheet

Use this before selecting a pattern:

```markdown
Product/service shape:
Buyer and user:
Time to value:
Trust/risk burden:
Price, margin, and sales cycle:
Adoption behavior:
Reachable access:
Delivery capacity:

| Candidate motion | Customer behavior | Time to evidence | Trust/buying process | Economics | Access | Delivery capacity | Repeatability | Total / 21 | Decision |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| <motion> | 0-3 | 0-3 | 0-3 | 0-3 | 0-3 | 0-3 | 0-3 | <sum> | test/reject/hold |
```

The score is a prioritization aid, not a substitute for judgment. Explain the strongest fit, the biggest mismatch, and the single experiment that can resolve the uncertainty.

## Parallel test portfolio

When speed matters, build a small portfolio around one bottleneck rather than choosing one tactic by instinct:

- one or two **fast-lane** tests that can produce a signal in days;
- zero or two **build-lane** tests that may create a durable product or partner loop; and
- no **scale-lane** test until a cheaper motion earns the right to spend more.

Run the tests in parallel only when they have separate owners, measurable outcomes, and enough follow-up capacity. Share common prerequisites instead of pretending they are independent. Prefer the test with the highest expected learning per dollar/day/effort, but preserve one alternative when the downside of being wrong is high.

## Pattern catalog

### 1. Founder-led first customers and design partners

**Mechanism:** The founder directly recruits a small number of customers, learns the problem and language through real conversations, and uses those customers to shape the product, positioning, proof, and references.

**Best fit:** Early products, complex or high-value offers, unclear positioning, markets where trust matters, and situations where the founder has direct access to credible buyers.

**First experiment:** Build a list of 10 highly specific prospects. Conduct direct conversations and ask for a paid pilot, design-partner commitment, or a concrete next step.

**Evidence:** Qualified conversations, a repeated painful problem, a buyer-approved outcome, a paid commitment, or a referenceable result.

**Failure mode:** The founder speaks only to friendly contacts, customizes every deal, or mistakes enthusiasm for willingness to pay.

**Source:** [Stripe Atlas: Your first 10 customers](https://stripe.com/guides/atlas/starting-sales)

### 2. Sales-led, high-touch enterprise motion

**Mechanism:** Human discovery, tailored demonstration, business-case development, procurement support, implementation, and executive sponsorship reduce risk for complex or expensive purchases.

**Best fit:** Large contracts, regulated industries, multi-person buying committees, workflow change, integration burden, and high cost of failure.

**First experiment:** Map one account's buying committee, trigger, economic buyer, champion, risk objections, and procurement path. Run a tightly scoped discovery-to-pilot sequence.

**Evidence:** A qualified champion, agreed success criteria, access to the economic buyer, a signed pilot, and a documented path to production.

**Failure mode:** Selling to an interested user without authority, accepting unpaid custom work, or confusing a demo with a buying process.

**Source:** [Atlassian: product-led versus sales-led growth](https://www.atlassian.com/agile/product-management/product-led-growth)

### 3. Product-led growth and self-service adoption

**Mechanism:** The product itself drives discovery, trial, activation, retention, and expansion through low-friction signup, transparent pricing, fast time to value, product education, and usage-based upgrade triggers.

**Best fit:** Products users can try safely, experience quickly, and adopt without heavy implementation or regulatory approval.

**First experiment:** Define the activation event, remove one signup or onboarding obstacle, and test whether a qualified user reaches value without a sales conversation.

**Evidence:** Activation rate, time to first value, retained usage, self-serve conversion, and expansion from real usage.

**Failure mode:** Adding a free tier before the product has a clear aha moment, optimizing signups instead of retained value, or using PLG for a product that requires high-touch deployment.

**Source:** [Atlassian: what is product-led growth](https://www.atlassian.com/agile/product-management/product-led-growth)

### 4. Collaboration and embedded sharing loops

**Mechanism:** A user's successful use naturally invites, exposes, or requires another person to participate, creating qualified product discovery inside the workflow.

**Best fit:** Collaboration, communication, file sharing, workflow, marketplace, and multi-user products.

**First experiment:** Identify the moment where a user needs another participant. Make the invite or shared artifact useful, explicit, and low-friction, then measure invited users who activate rather than invitations sent.

**Evidence:** Qualified invites, invited-user activation, team adoption, and net new users generated per active account.

**Failure mode:** Adding forced invites that create annoyance, measuring invitations instead of activated collaborators, or relying on a loop that does not exist in the real job.

**Source:** [Dropbox investor report: sharing, referrals, self-serve conversion, and expansion](https://investors.dropbox.com/static-files/08385c0e-30e7-4016-b21c-67542bb5a9f7)

### 5. Two-sided referral incentive

**Mechanism:** A satisfied customer gets a meaningful benefit for introducing a qualified new customer, and the new customer also receives a reason to accept the invitation.

**Best fit:** Products with satisfied users, clear referral moments, low marginal cost, and a reward that reinforces product value.

**First experiment:** Ask recently successful customers for one specific introduction or offer a two-sided reward tied to activation, not just signup.

**Evidence:** Referred users who activate, retain, and produce acceptable contribution margin after reward cost.

**Failure mode:** Rewarding low-quality signups, inviting abuse, or asking for referrals before the customer has experienced a clear win.

**Source:** [Dropbox referral program](https://www.dropbox.com/refer?preview=true)

### 6. Inbound content, education, and search intent

**Mechanism:** Helpful, specific content earns attention from people already trying to understand or solve a problem, then moves them toward a useful next step.

**Best fit:** Searchable problems, education-heavy categories, expert-led brands, long consideration cycles, and products where trust precedes purchase.

**First experiment:** Choose one high-intent question from the target buyer. Publish a genuinely useful answer with a specific conversion path, then measure qualified actions rather than pageviews.

**Evidence:** Qualified organic traffic, engaged prospects, conversion to a relevant next step, assisted pipeline, and eventual revenue.

**Failure mode:** Publishing broad low-intent content, optimizing for traffic, using generic AI output, or failing to connect content to a commercial motion.

**Source:** [HubSpot inbound and flywheel methodology](https://www.hubspot.com/inbound-marketing)

### 7. Templates, tools, and utility-led discovery

**Mechanism:** Free or low-friction assets solve a narrow job immediately and expose the creator's product, expertise, or workflow to people with that job.

**Best fit:** Repeated workflows, design and planning problems, operational templates, calculators, analyzers, checklists, and categories where users search for examples.

**First experiment:** Publish one high-value template or utility for a specific job, instrument the path from use to product discovery, and interview users who adopt it.

**Evidence:** Qualified users, repeated use, attribution to the core product, conversion, and organic sharing or duplication.

**Failure mode:** Creating an asset with no path to the paid product, attracting the wrong audience, or measuring downloads instead of business value.

**Sources:** [Figma Community resources](https://help.figma.com/hc/en-us/articles/360038510693-Guide-to-the-Figma-Community), [Figma template gallery](https://www.figma.com/templates/)

### 8. Integration, app marketplace, and ecosystem distribution

**Mechanism:** Being discoverable where a buyer already searches for compatible tools, then reducing adoption friction through integrations, templates, partner listings, and shared workflows.

**Best fit:** Software products that become more valuable when connected to existing systems or that sell into an established platform ecosystem.

**First experiment:** Identify the three integrations or marketplaces most strongly implied by the target customer's workflow. Build or list the smallest one and measure qualified discovery, activation, and downstream revenue.

**Evidence:** Marketplace discovery, integration activation, retained workflows, sourced pipeline, and partner-assisted revenue.

**Failure mode:** Building integrations without distribution, supporting every possible app, or confusing technical availability with customer demand.

**Sources:** [Zapier App Directory](https://help.zapier.com/hc/en-us/articles/21996626006541-Introduction-to-apps-on-Zapier), [Zapier Integration Partner Program](https://zapier.com/developer-platform/partner-program)

### 9. Partner, reseller, and channel-led growth

**Mechanism:** A partner transfers trust and access to a defined customer segment, while the company supplies a better offer, implementation capability, economics, or product that the partner can sell or deliver.

**Best fit:** Fragmented markets, local or vertical expertise, regulated buying environments, services-led workflows, and products that benefit from implementation or trusted introductions.

**First experiment:** Choose one partner with a specific customer motion. Define the target account list, partner action, introduction path, economics, enablement, and first revenue milestone before calling it a partnership.

**Evidence:** Qualified introductions, partner-generated pipeline, activated customers, partner effort, margin after channel cost, and repeatability beyond one relationship.

**Failure mode:** Signing logos with no activity, paying for introductions rather than outcomes, creating channel conflict, or making the partner responsible for work they cannot perform.

**Sources:** [Zapier Partner Program](https://zapier.com/developer-platform/partner-program), [Shopify partner ecosystem](https://www.shopify.com/news/a-world-with-more-builders)

### 10. Community and open-source-led adoption

**Mechanism:** A useful project, shared practice, or public body of work earns trust and participation, which creates contributors, users, advocates, hiring access, and eventually paid services or products.

**Best fit:** Developer tools, technical infrastructure, standards, education, professional communities, and products where credibility is earned through usefulness and participation.

**First experiment:** Publish a useful artifact with clear documentation, a real example, contribution or discussion path, and a defined audience. Show up consistently where that audience already gathers.

**Evidence:** Qualified users, repeat contributors, community problem reports, adoption by credible teams, inbound requests, and paid conversion or services demand.

**Failure mode:** Treating a repository or chat room as a community strategy, accepting unmaintainable contributions, or building attention without a sustainable value and business model.

**Sources:** [GitHub: building an open-source community](https://github.blog/open-source/maintainers/four-steps-toward-building-an-open-source-community/), [GitHub: promoting an open-source project](https://github.blog/open-source/maintainers/5-tips-for-promoting-your-open-source-project/)

### 11. Launch and event-led attention

**Mechanism:** A coordinated release, event, demo, conference, community launch, or public milestone concentrates attention and creates a short window for feedback, early users, partners, and social proof.

**Best fit:** Products with a clear story, usable experience, existing community or audience, and a team able to respond immediately after attention arrives.

**First experiment:** Define the launch goal before the announcement: activated users, qualified conversations, feedback, revenue, or partner interest. Prepare the demo, page, support, follow-up, and measurement before launch day.

**Evidence:** The chosen business outcome, not ranking or impressions alone; also record post-launch conversations, activation, retention, and follow-up conversion.

**Failure mode:** Launching before the product works, chasing votes, assuming one spike equals a repeatable channel, or failing to follow up with interested users.

**Source:** [Product Hunt Launch Guide](https://help.producthunt.com/en/articles/17350772-launch-guide)

### 12. Lifecycle activation, retention, and expansion

**Mechanism:** Behavior-triggered onboarding, education, support, success management, usage prompts, and targeted upgrade paths help customers reach value, continue using the product, and buy more.

**Best fit:** Products with measurable usage milestones, recurring revenue, multiple plans, or a large gap between signup and successful adoption.

**First experiment:** Identify the first durable value event and trigger one contextual intervention before or after it. Measure retained usage or expansion, not email opens alone.

**Evidence:** Time to value, activated retention, churn reduction, expansion revenue, successful handoffs, and customer-reported outcomes.

**Failure mode:** Adding automation before understanding the user journey, sending calendar-based noise, or optimizing engagement metrics that do not predict value.

**Sources:** [HubSpot inbound/flywheel](https://www.hubspot.com/inbound-marketing), [Intercom on product-led onboarding](https://www.intercom.com/blog/podcasts/growth-marketing-ramli-john-on-user-onboarding/)

## Pattern record template

When a new case study, experiment, or observed motion is added, record it like this:

```markdown
### <Pattern name>

- Mechanism:
- Customer/product fit:
- Bottleneck addressed:
- First experiment:
- Evidence threshold:
- Cost and time:
- Failure modes:
- What is documented versus inferred:
- Source or observation:
- Last reviewed: YYYY-MM-DD
```

## Library discipline

- Do not call a pattern “proven” because it is famous or because a large company used it.
- Keep the mechanism separate from the brand story.
- Prefer primary company documentation, investor filings, platform rules, and direct experiment evidence over recycled growth folklore.
- Record negative results and boundary conditions. Failed experiments are part of the library.
- Re-review patterns before using them in regulated, healthcare, financial, employment, or privacy-sensitive markets.
