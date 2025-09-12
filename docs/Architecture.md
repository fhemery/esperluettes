# Domain-Driven Design Architecture

This document outlines the architecture and folder structure for the application, following Domain-Driven Design (DDD) principles.

More details can be found inside the different modules README.
- [Admin](../app/Domains/Admin/README.md)
- [Auth](../app/Domains/Auth/README.md)
- [Dashboard](../app/Domains/Dashboard/README.md)
- [Home](../app/Domains/Home/README.md)
- [News](../app/Domains/News/README.md)
- [Profile](../app/Domains/Profile/README.md)
- [StaticPage](../app/Domains/StaticPage/README.md)
- [Shared](../app/Domains/Shared/README.md)
- [StoryRef](../app/Domains/StoryRef/README.md)

To understand code organization, check [Domain Structure](./Domain_Structure.md)

**Important:**:  The Domains must have one-way dependency (we cannot have Auth -> Profile -> Auth)

This has two consequences :
- To avoid messing up accidentally dependency, we have setup a tool called [Deptrac](https://github.com/deptrac/deptrac).

- When we need to send a command in the wrong direction, we use **Event-Driven Architecture**. Check below for more details. 

 ## Event-Driven Architecture
Event-Driven Architecture lets domains react to changes without tight coupling. Domains publish logical events; subscribers in other domains handle them via their own services. This keeps dependencies one-way while enabling rich cross-domain workflows.

For the full guide (contracts, EventBus, persistence model, summaries, translations, and an end-to-end example), see:

- [Events](../app/Domains/Events/README.md)

## Deptrac architectural rules

We follow a DDD layout under `app/Domains/` and enforce boundaries with Deptrac.

### Public API-Only Architecture

**Core Principle:** Domains can only access other domains through their **Public APIs**. All internal implementation (Models, Services, Controllers) remains private to each domain.

### What's Allowed Between Domains

**✅ Cross-domain access allowed:**
- `PublicApi/` directories - contracts and DTOs exposed by each domain
- `Shared/Contracts/` - interfaces used across multiple domains  
- `Shared/Dto/` - data transfer objects for cross-domain communication
- `Shared/Events/` - domain events for decoupled communication

**❌ Cross-domain access forbidden:**
- `Models/` - database models are private to each domain
- `Services/` - business logic services are private to each domain
- `Controllers/` - HTTP controllers are private to each domain
- Any other internal domain implementation


**Admin Exception:**
- **Admin** can access **everything** (all PublicApis AND private internals of all domains)
- This exception exists because we made a tradeoff between putting admin screens in their own domains vs keeping everything in Admin for clarity
- We should strive to limit direct access to private internals, but the cost of refactoring is currently higher than the benefit

### Test Dependencies

**Domain Feature Tests** have special privileges:
- Can access their **own domain internals**
- Can access **all other test modules** (for creating test data)
- Can access **Public APIs** from other domains (for integration testing)
- Can access **Shared test helpers**

### Example Structure

```
app/Domains/Story/
├── PublicApi/          # ✅ Accessible by other domains
│   ├── StoryPublicApi.php
│   └── Dto/
├── Models/             # ❌ Private to Story domain
├── Services/           # ❌ Private to Story domain  
├── Controllers/        # ❌ Private to Story domain
└── Tests/              # ✅ Can access other test modules
```

Run manually:

```
./vendor/bin/sail composer deptrac
# or if Sail is not used
composer deptrac
```
