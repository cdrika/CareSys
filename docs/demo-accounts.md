# CareSys demo access

Open http://localhost:8080/login. All twelve human demo accounts use:

**Password: `CareSysDemo!2026`**

The login page has a role selector. Accounts and records are fictional and intended
for local development. Separate internal job principals use random credentials and
cannot be accessed with the shared demo password.

| Email | Role and access |
| --- | --- |
| platform@caresys.test | Agency verification, manual commercial agreements, disputes, provider queue. No automatic tenant content access. |
| owner@caresys.test | Willow agency owner; all purchased agency modules |
| recruiter@caresys.test | Willow recruitment, history requests, contracts and joining |
| scheduler@caresys.test | Willow scheduling, people, care and operational reports |
| supervisor@caresys.test | Willow operational approvals, people, care, assets and expenses |
| finance@caresys.test | Willow payroll mapping/export, expenses, invoices and receipts |
| caregiver@caresys.test | Willow worker; assigned visits, clocks, notes, leave and expenses |
| candidate@caresys.test | Independent candidate with a discoverable profile and an application |
| former@caresys.test | Former employee; independent profile and source-labelled career history |
| unresolved@caresys.test | Private candidate with unresolved identity; not eligible for protected joining |
| owner2@caresys.test | Harbor owner; isolated from Willow records |
| caregiver2@caresys.test | Harbor worker; own assigned services only |

## Demonstration journeys

1. Sign in as the recruiter, select Willow, and open Applications. Request history
   for Alex Chen with a recruiting purpose and duration.
2. In a separate browser profile, sign in as the candidate. Open Privacy & sharing,
   select the exact career entries and approve. Return to the recruiter's History
   requests to view them. Withdraw as the candidate and confirm the next read fails.
3. As the owner or recruiter, issue a contract against Alex's application. Both the
   agency and candidate must confirm their own passwords and the exact contract
   version. The candidate separately authorizes joining. The agency records its
   reviewed conditions, then activates only once the agreed start date arrives.
4. As the caregiver, select Willow and open Today. Clock in, record real breaks,
   clock out and submit a care note. The supervisor reviews the resulting time.
5. Submit leave and expenses as the caregiver. Review them as supervisor. The
   finance user can export approved worked-time snapshots, issue invoices for
   approved completed visits and record evidenced receipts.
6. Sign in as Harbor's owner and try the same Willow URL. It must be unavailable.

Demo verification and subscription states are synthetic fixtures. They do not
represent actual identity checking or payments. New registrations remain pending
verification. The shared-password seeder is prohibited in production and refuses
to reset existing accounts on repeated runs.
