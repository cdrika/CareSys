# Implementation and acceptance status

The current application is a working local implementation of the principal
Release A and agency operation workflows. It is not a claim that every item in the
three specifications is production complete. The following boundaries remain
explicit so unfinished integrations are not presented as completed outcomes.

## Working persistent workflows

- Shield account login/registration; independent personal accounts; MySQL-backed
  sessions; CSRF; scoped role and entitlement policies; stale-workspace rejection.
- Two agencies and twelve role-based demo users; agency registration and platform
  verification; scoped administrative access; auditable manual module agreements
  and dependencies; quote requests without simulated purchases.
- Private profile editing, geographic preferences, recruiter preview, discovery
  pause, self-declared qualifications and agency credential acceptance.
- Guided form controls with contextual help and examples; standard choices plus
  custom entries; searchable selections for skills, qualifications and languages;
  care-setting and travel-distance choices; country/state/county/city selectors
  backed by a locally imported GeoNames catalog.
- Approved directory projection, recruiting-purpose searches, application stages,
  account invitations, private candidate response and application withdrawal.
- Named-recipient history requests, candidate-selected career versions, expiry,
  withdrawal, attribution, disputes and immutable responsibility corrections.
- Contract revisions and digests, separate party agreement with password step-up,
  separate joining authorization, reviewed conditions, pending future starts,
  idempotent activation, offboarding and preservation of previous relationships.
- Client records, caregiver assignment, overlap/leave checks, required credential
  validity, online immutable clock events and actual elapsed work excluding breaks.
- Exact-version time review and correction requests, leave balance posting and
  reversal, opening adjustments, care notes, incidents and acknowledgement.
- Asset registration, exclusive custody, returns and custody history; expenses,
  independent review and evidenced reimbursement reconciliation.
- Immutable approved worked-time export snapshots, provider identity mapping,
  checksums and CSV formula escaping; invoice source allocation, partial receipts
  and full credit notes for unpaid invoices; scoped dashboard metrics.
- Versioned core and operational JSON endpoints sharing the domain services; metadata-only audit
  and durable provider-pending notifications; expiry and due-activation CLI worker.

## Remaining work and external dependencies

- Production email delivery and verification/recovery setup, privileged MFA,
  identity evidence policy and legally validated contract signing. Password
  confirmation is implemented; it is not MFA or a certified digital signature.
- Subscription payment provider, priced versioned product catalog, taxes, seat
  metering, proration and signed/reconciled provider callbacks. Manual agreements
  are auditable; they do not claim payment.
- Authorized reference responses, granular qualification/reference/contact
  disclosure, private binary uploads, malware scanning, retention/legal-hold
  execution, imports and their preview/reconciliation workflows.
- Branch and assignment access scopes beyond the single agency scope, detailed
  structured availability, travel buffers, recurring/multi-caregiver visits,
  time accrual/holiday calendars, advanced coverage and qualification alerts.
- Provider-specific payroll formats, paid leave and reimbursement allocations,
  finalized-period adjustment/reversal handling and actual payroll submission.
- Partial visit billing, tax rules, partial credits/refunds, accounting adapters
  and a full balanced native ledger. No statutory or full-ledger claim is made.
- Production deployment, TLS/secrets infrastructure, independent authorization
  review, accessibility audit, load targets, encrypted backups and a measured
  restore exercise. Local browser checks do not establish WCAG conformance.
- Restricted birth-date matching, sensitive screening and operational multiagency
  overlap policy remain disabled pending owner/jurisdiction decisions.

## Verification

`php vendor/bin/phpunit --no-coverage` currently runs 27 tests and 87 assertions
against MySQL 8.4. Tests include directory privacy, eligibility, tenant denial,
grant expiry/withdrawal, immutable correction scope, version-specific contracts,
future dates, activation retry, scheduling overlap, break duration, duplicate clock
events, leave reversal, credential expiry, time correction, asset custody, payroll,
billing allocation, credits and CSV injection.

The asset race test starts two independent PHP processes and synchronizes their
start; MySQL permits one custody assignment and rejects the competing request.
Other race scenarios still require expanded concurrency coverage.

`tests/Browser/smoke.cjs` checked all twelve human demo accounts and 161 permitted
page visits with no rendering exceptions or viewport overflow. Desktop and mobile
screenshots were inspected. A final focused sweep passed 25 page visits plus
login/sign-out for all twelve accounts using database-backed sessions. The generated results and screenshots are internal QA
artifacts under build/qa. Later changes to qualifications, session storage and
agreement administration also receive focused verification.

## Functional acceptance mapping

| Specification tests | Evidence / remaining qualification |
| --- | --- |
| AT 01–03 | Independent demo account, profile/region preview and visibility tests; detailed radius matching pending |
| AT 04–06 | Agency/entitlement denial, private invitations; birth-date lookup intentionally disabled |
| AT 07–10 | Named grant/version/withdrawal/expiry and source/dispute tests; granular extra categories pending |
| AT 11–16 | Contract revision, party step-up, joining, future date, withdrawal and retry tests; activation race suite still to expand |
| AT 17–18 | Two-agency denial and server workspace nonce; file/cache/export extension coverage pending |
| AT 19–22 | Overlap, actual seconds, duplicate clock, leave reversal, asset race; broader leave/shift race tests pending |
| AT 23–24 | Snapshot payroll, duplicate allocation, invoice receipts/credits; real provider contract tests pending |
| AT 25 | Audited manual agreement/dependency checks; payment callbacks pending provider selection |
| AT 26 | Offboarding service preserves person/history and revokes worker membership; advanced coverage actions pending |
| AT 27 | Responsive/role browser checks performed; formal accessibility and restore exercise pending |
