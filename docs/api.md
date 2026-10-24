# Core JSON API

Core routes under /api/v1 use the same services as web forms. Authentication is
the Shield caresys_session cookie. Browser mutations require X-CSRF-TOKEN and
X-Workspace-Nonce from the current workspace form. Workspace switching invalidates
the previous nonce. Public references never replace membership or consent checks.

| Method | Endpoint | Behavior |
| --- | --- | --- |
| GET, PATCH | /me/profile | Own profile and exact edit version |
| POST | /me/profile/publish | Explicit preview publication or pause |
| GET | /agencies/{agency}/candidates | Approved summaries; purpose required; 25 per page |
| POST | /agencies/{agency}/history-requests | History request after candidate account confirmation |
| POST | /me/history-requests/{request}/decision | Selected entry versions; approve/decline/withdraw |
| GET | /agencies/{agency}/history-requests/{request}/entries | Named recipient and current grant only |
| POST | /me/contracts/{contract}/accept | Individual exact-version decision |
| POST | /agencies/{agency}/contracts/{contract}/accept | Authorized agency agreement |
| POST | /me/joining-requests/{joining}/decision | Separate joining authorization |
| POST | /agencies/{agency}/joining/{joining}/activate | Idempotent activation; Idempotency-Key required |

References are 32 lowercase hexadecimal characters. Writable fields are allowlisted.
Profile edits include version. Contract decisions include version, digest, decision,
confirm=yes and the current password for step-up. Never place a password in a URL.

Responses contain data and correlation_id, or error:{code,message,correlation_id}.
Statuses include 401, 403, 404, 409, 422 and 429. Browser unauthenticated requests
may follow Shield's redirect-to-login behavior. Protected data uses no-store caching.

API timestamp fields use ISO-8601 UTC strings; decimal values remain strings.
A core OpenAPI contract is in openapi.yaml. Full specification-wide schemas and
integration-token authentication remain integration work.

Operational POST routes are also implemented under /agencies/{agency}: visits,
clients, clock-events, leave-requests, assets, expenses, notes, payroll-exports and
invoices. Versioned review routes include timesheets/{ref}/approve,
leave-requests/{ref}/decision, assets/{ref}/custody, expenses/{ref}/decision,
invoices/{ref}/receipt and invoices/{ref}/credit. These use the same explicit
field allowlists and authorization services as their server-rendered forms.
