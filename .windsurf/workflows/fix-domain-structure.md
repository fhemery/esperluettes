---
description: 
auto_execution_mode: 1
---

1. Read docs/Domain_Structure.md to confirm target Public/Private layout and naming.

2. Inventory the domain: Controllers, Models, Services, Observers, Requests, routes, Blade views, translations, Public API, Events.

3. Create target Public and private folders

4. Move internals to Private/* and update namespaces to App\Domains\<Domain>\Private\….

5. Move Public API and cross-domain Events to Public/* with namespaces App\Domains\<Domain>\Public\….Events are always public.PublicApi classes should be moved to Public/Api. No additional interface shoud be created

6. Move Blade to Private/Resources/views/ using pages/ and components/.

7. Move PHP translations to Private/Resources/lang/<locale>/ and plan a domain key (e.g., news).

8. Transfer the Service Provider to Public/Providers: load translations, views, migrations, Private/routes.php; register observers and public events.

9. Adjust provider registration provider in bootstrap/providers.php

10. Remove any includes from routes/web.php for this domain (routes now load via provider).

11. Update all imports across app/tests: use Private\… for internals, Public\Events\… for events, Public\Api\… for public APIs.

12. Update controller view calls to '<domain-key>::pages.<view>'.

13. Adjust deptrac.yaml paths so <Domain>Public = app/Domains/<Domain>/Public/, <Domain>Private = app/Domains/<Domain>/Private/ (keep ruleset).

14. Clear caches: artisan optimize:clear.

15. Run domain + admin tests, then full suite; fix namespace issues.

16. Run deptrac; resolve any boundary violations.

17. Remove any empty folder following the migration