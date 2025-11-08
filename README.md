# WAPTLab — Web Application Penetration Testing Lab

**Author:** Mohamed Selim  
**Created:** 2025-10-22  
**Difficulty:** Advanced

---

## Synopsis

WAPTLab is an intentionally vulnerable, multi-tenant CRM-style application built for penetration testing training and security research. The app is built with Laravel and includes CSV/XML ingestion pipelines, Elasticsearch-based search, and an EAV (Entity–Attribute–Value) data model for flexible customer attributes. The goal is to provide a production-like lab that contains only planned vulnerabilities (no accidental secrets) so security teams can practice realistic attack chains in a safe, controlled environment.

**Important:** This project is designed to run **only** inside Docker. Do not run it on production systems or expose it to public networks.

---

## Key Features

- Multi-tenant architecture (SaaS simulation)  
- Backend: Laravel  
- Flexible EAV data model for customer attributes  
- Elasticsearch for search and index-related workflows  
- CSV ingestion handled by a background worker  
- XML / XSLT pipelines used for reporting  
- Simulated WAF / bot-detection component to practice bypass techniques

---

## Docker-only Requirements

- Docker (Engine)  
- Docker Compose

Everything required to run WAPTLab is packaged in Docker images and configured via ````docker-compose````.

---

## Run the Lab

1. Clone the repository  
````bash
git clone https://github.com/<your-username>/WAPTLab.git
cd WAPTLab
composer install
````

2. Start containers  
````bash
docker-compose up -d
````

Typical services started:
- web — Laravel application  
- db — MySQL  
- elasticsearch — search/index service (may be optional)  
- worker, redis, and other helper services as configured

3. Access the application
- User Dashboard: http://localhost:8080/dashboard  
- Admin Dashboard: http://localhost:8080/admin  
- API: http://localhost:8080/api/*

4. Stop and remove containers  
````bash
docker-compose down
````

---

## How to Use This Lab

- Use the lab only in an isolated network or local environment.  
- All learning and testing should be performed inside the Docker environment.  
- This README intentionally does not include public exploit payloads or step-by-step PoCs. Instead it provides hints, concepts, and recommended study topics so you can learn and validate findings safely.

---

## Assessment Guide — Hints & Recommendations

For each included vulnerability category, the notes below list what to study, what to look for during testing, and recommended verification approaches (non-actionable, conceptual). These notes are meant to guide learning and to help you understand how each issue could manifest in a lab environment.

### 1) SQL Injection — header-assisted (e.g., X-Forwarded-For)
- Study: error-based, time-based, and blind SQL injection techniques.  
- Look for: HTTP headers being logged or interpolated into SQL queries (server-side logging, analytics, or audit queries).  
- Verify conceptually: observe anomalous DB errors or timing changes when header values are changed inside a controlled lab.

### 2) SQL Injection — CSV ingestion
- Study: how CSV parsers handle untrusted input; interaction between ingestion job and database queries.  
- Look for: CSV fields passed into DB queries or into query-building code by background workers.  
- Verify conceptually: inspect ingestion worker code inside the container to trace how CSV rows are processed and used.

### 3) Path Traversal — encoded/JWT-encoded path parameter
- Study: path normalization, canonicalization, and how encoded inputs (base64/JWT payloads) are decoded and used.  
- Look for: endpoints that accept tokens or encoded paths and then read files based on their content.  
- Verify conceptually: review server-side path handling to confirm presence/absence of sanitization and normalization.

### 4) Open Redirect — double-slash host trick
- Study: URL parsing and how browsers interpret `//host` path segments vs absolute URLs.  
- Look for: endpoints that reflect path components directly into redirects or Location headers.  
- Verify conceptually: inspect routing and redirect code paths for unvalidated path components.

### 5) Open Redirect — OAuth redirect_uri not validated
- Study: OAuth authorization flows and proper redirect_uri whitelisting.  
- Look for: redirect_uri parameters accepted without strict exact-match whitelists.  
- Verify conceptually: review OAuth client configuration and validation code in the app.

### 6) IDOR — insecure direct object reference (download endpoint)
- Study: object-level authorization, ownership checks, and predictable identifiers.  
- Look for: endpoints serving files or resources using numeric or guessable IDs without authorization checks.  
- Verify conceptually: confirm server-side authorization logic that enforces ownership for resource access.

### 7) IDOR / DB context-switching via query parameter
- Study: multi-tenant DB architectures, context switching, and parameter-based context selection pitfalls.  
- Look for: db, tenant, or context query parameters that influence which database or schema is used.  
- Verify conceptually: inspect code paths that accept such parameters and how they derive DB connection or tenant context.

### 8) SSTI — server-side template injection (email/training endpoint)
- Study: templating engines, safe variable rendering, and the dangers of evaluating user-provided templates.  
- Look for: template rendering methods that accept user-supplied content in server-side templates.  
- Verify conceptually: review server-side template rendering and see whether inputs are escaped or compiled.

### 9) SSTI — unescaped template tags in export flows
- Study: templating contexts within exported documents, and how tags are escaped or executed during rendering/export.  
- Look for: export endpoints that accept template-like tags in JSON or data, and whether those tags are evaluated when generating documents.  
- Verify conceptually: review export and serialization logic for escaping or template rendering hooks.

### 10) SSRF — Swagger UI url parameter
- Study: SSRF attack vectors, internal service access (127.0.0.1, 169.254.x.x), and URL parameter handling.  
- Look for: tooling endpoints (Swagger UI, API proxies) that accept URLs and cause server-side fetches.  
- Verify conceptually: review code that processes the url parameter and examine fetch logic and allowed/blocked host checks.

### 11) SSRF — image fetch endpoint
- Study: remote resource fetching logic, allowed-scheme/host validation, and safety checks (timeouts, DNS resolution).  
- Look for: image/profile fetch endpoints that accept external URLs and fetch them server-side.  
- Verify conceptually: inspect fetch implementation for hostname/scheme validation and network restrictions.

### 12) Stored XSS — profile fields
- Study: stored vs reflected XSS, output encoding, and sanitization strategies (HTML sanitizer libraries).  
- Look for: profile fields that are stored and later rendered without proper escaping.  
- Verify conceptually: review rendering templates and sanitization rules for stored profile content.

### 13) XSS via malformed quoting in name/email
- Study: attribute context vs HTML context vs JavaScript context and how quotes are handled.  
- Look for: user inputs interpolated directly into attributes or script contexts without proper escaping.  
- Verify conceptually: inspect templates where name/email are output inside attributes or inline scripts.

### 14) Path Traversal via Elasticsearch snapshot/index handling
- Study: Elasticsearch snapshot APIs and how path components may be handled by proxying services.  
- Look for: endpoints that forward snapshot or path values to backend services (Elasticsearch) and whether path normalization is enforced.  
- Verify conceptually: examine the code that constructs ES snapshot paths and ensure `..` and other traversal tricks are sanitized.

### 15) Information Disclosure — publicly accessible backup files
- Study: secure storage practices, access controls for object storage, and how backups are named and exposed.  
- Look for: public storage endpoints serving backup files (e.g., composer.lock.bak, exported dumps) without auth.  
- Verify conceptually: check storage configuration and public URL access controls inside the container.

### 16) Debug Mode enabled (Laravel APP_DEBUG)
- Study: Laravel debug mode, stack traces, and the types of sensitive data shown in error pages (ENV variables, stack frames).  
- Look for: APP_DEBUG=true in environment or .env file and error handler behavior.  
- Verify conceptually: review .env and config files in the Docker container for debug settings.

### 17) Outdated frontend component (Markdown/editor)
- Study: how client-side libraries may expose vulnerabilities and how to identify outdated versions (console warnings, bundle metadata).  
- Look for: browser console warnings or known vulnerable versions listed in package lock files.  
- Verify conceptually: inspect frontend dependencies inside the container or Docker image layers.

### 18) Exposed vulnerable dependency (search/index service)
- Study: securing search services (Elasticsearch), how versions map to CVEs, and network exposure.  
- Look for: search service listening on a reachable host/port and version metadata.  
- Verify conceptually: inspect container networking and ES configuration for open ports and version info.

### 19) XXE — Out-of-Band XML External Entities
- Study: XML parser configuration, external entity resolution, and OOB detection channels (DNS/http callbacks).  
- Look for: endpoints that accept XML input (uploads/imports) and pass it to XML parsers without disabling external entity resolution.  
- Verify conceptually: review XML parsing code and parser options inside the application.

### 20) XSLT / XXE injection — document()/xsl:copy-of execution
- Study: XSLT processing risks, document() and xsl:copy-of, and how XSLT can fetch remote or local content.  
- Look for: XSLT processing endpoints that accept templates/XSLT from users or from imported files.  
- Verify conceptually: inspect the XSLT engine usage and whether access to external resources is restricted.

---

## Recommended Study Path

1. Start with web fundamentals: HTTP, headers, cookies, CORS, and authentication flows.  
2. Learn injection classes: SQLi → XSS → SSTI → XXE.  
3. Study access control: IDOR, role-based access, multi-tenant isolation.  
4. Practice SSRF and service discovery within containerized environments.  
5. Review secure configuration: APP_DEBUG, storage ACLs, dependency management.  
6. Learn how to analyze Docker images and containers to inspect application code and configurations safely.

---

## Best Practices for Lab Operators

- Run WAPTLab in an isolated network or VM. Do not expose it to the public Internet.  
- Use host-only networking or firewall rules to prevent accidental exposure.  
- Rotate any test credentials regularly and use ephemeral test data.  
- Maintain a separate builder or CI pipeline for recreating images and updating dependencies.

---

## License

MIT License — for educational and testing purposes only.
