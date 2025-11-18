# Custom Admin System Migration Plan

## Context & Problem Statement

Our application uses Filament for admin panels, but we face limitations:
- **Asset bundle conflicts**: Cannot use our custom Vite bundles (Alpine.js, Tailwind) inside Filament pages
- **Cross-domain features**: Complex admin features spanning multiple domains don't fit Filament's resource paradigm
- **Testing constraints**: Filament pages are harder to test with standard Laravel feature tests
- **Component reuse**: Cannot leverage our existing domain components inside Filament

## Strategic Decision: Hybrid Admin System

Build a custom admin system that:
1. Uses our standard asset bundles (no conflicts)
2. Allows domain-specific admin pages
3. Supports gradual migration from Filament
4. Can coexist with Filament resources during transition
5. Eventually may replace Filament entirely (decision deferred)

---

## Architecture Overview

### Core Components

#### 1. Admin Layout (`app/Domains/Shared/Resources/views/layouts/admin.blade.php`)
A dedicated layout for admin pages that:
- Loads our standard Vite bundles
- Renders a sidebar navigation menu
- Provides admin-specific header/footer
- Applies admin-only styling/utilities
- Handles authentication & authorization display

**Key Responsibilities:**
- Asset loading (CSS/JS via Vite)
- Navigation menu rendering (from registry)
- Flash messages for admin actions
- User info display (admin name, logout)
- Breadcrumb support for deep pages

**Layout Structure:**
- Header: Admin branding, user menu, notifications
- Sidebar: Dynamic navigation from registry
- Main content area: Page-specific content
- Footer: Optional admin utilities

---

#### 2. Admin Navigation Registry

**Purpose:** Central registry where each domain declares its admin pages, similar to how Filament discovers resources.

**Location:** `app/Domains/Admin/Private/Support/AdminNavigationRegistry.php` (singleton service)

**Responsibilities:**
- Collect admin page definitions from all domains
- Group pages by navigation category
- Check permissions before displaying menu items
- Support both custom pages AND Filament resources
- Allow ordering/sorting of menu items

**Registration Flow:**
1. Each domain's service provider registers its admin pages during `boot()`
2. Registry stores page metadata (route, label, icon, permissions, group, order)
3. Admin layout queries registry to build sidebar navigation
4. Registry checks current user permissions before including items

**Data Structure (Conceptual):**
Each registered page contains:
- `label`: Display name (translatable)
- `route`: Route name or URL
- `icon`: Icon identifier (Material Symbols, Heroicons)
- `permissions`: Required roles/permissions (array)
- `group`: Navigation group (e.g., "Moderation", "Content", "System")
- `order`: Sort order within group
- `type`: 'custom' or 'filament' (affects styling/behavior)
- `badge`: Optional badge callback for notifications/counts

**Groups:**
- System (users, roles, settings)
- Content (stories, chapters, categories)
- Moderation (reports, user management)
- Events (domain events, notifications)
- Technical (logs, feature toggles, maintenance)

---

#### 3. Domain Admin Pages

**Pattern:** Each domain manages its own admin pages in its Private namespace.

**Structure:**
```
app/Domains/{Domain}/Private/
  Controllers/
    Admin/                     # Admin-only controllers
      {Feature}Controller.php
  Resources/
    views/
      pages/
        admin/                 # Admin-only views
          {feature}.blade.php
  routes.admin.php            # Optional: separate admin routes file
```

**Controller Responsibilities:**
- Handle admin-specific business logic
- Use domain services (follow existing architecture)
- Return views using admin layout
- Apply authorization via middleware or policies

**View Pattern:**
Views extend the admin layout and provide page content:
- Use `x-shared::admin-layout` component
- Specify page title, breadcrumbs
- Use existing domain components (no restrictions!)
- Access full Vite asset bundles

---

## Migration Path

### Phase 1: Foundation (Week 1-2)

**Goal:** Build infrastructure without touching existing Filament pages.

**Tasks:**
1. Create admin layout blade template
2. Implement AdminNavigationRegistry service
3. Register registry as singleton in Admin domain
4. Create example admin page in one domain (Moderation user management)
5. Register example page in registry
6. Test navigation rendering & permission checking

**Deliverables:**
- Working admin layout with sidebar
- Registry accepting page registrations
- One fully functional custom admin page
- Documentation on how domains register pages

**Success Criteria:**
- Custom page displays correctly with all assets
- Filament pages still work unchanged
- Navigation shows both custom and Filament links
- Permission checking prevents unauthorized access

---

### Phase 2: Gradual Migration (Month 1-3)

**Goal:** Migrate complex/cross-domain features to custom admin system.

**Priority Order (Highest to Lowest):**
1. **Cross-domain features first**
   - User management (Auth + Profile + Moderation data)
   - System maintenance pages
   - Domain event exploration
   
2. **Pages requiring custom components**
   - Rich dashboards with charts
   - Bulk operations with custom UI
   - Features using our Alpine.js components

3. **Hard-to-test Filament pages**
   - Pages with complex interactions
   - Pages requiring Pest/PHPUnit tests

**Migration Process (Per Feature):**
1. Identify Filament resource/page to migrate
2. Create corresponding controller in domain's Admin namespace
3. Create view extending admin layout
4. Implement business logic using existing domain services
5. Write feature tests (now easy with standard Laravel testing)
6. Register page in navigation registry
7. Remove Filament resource/page
8. Update any links pointing to old Filament route

**What Stays in Filament (For Now):**
- Simple CRUD resources (ActivationCodes, Roles)
- Resources without complex UI needs
- Low-maintenance admin tools
- Resources that "just work"

**Coexistence Strategy:**
- Registry shows both Filament and custom pages in same menu
- Visual distinction (icon prefix, subtle styling difference)
- Clicking Filament items goes to `/admin/*` (Filament domain)
- Clicking custom items goes to domain routes (our layout)
- Both accessible from same navigation sidebar

---

### Phase 3: Domain-by-Domain Migration (Month 3-6)

**Goal:** Migrate remaining features domain by domain.

**Approach:**
For each domain (Auth, Story, Calendar, etc.):
1. Audit all Filament resources in `app/Domains/Admin/Filament/Resources/{Domain}/`
2. Prioritize by complexity & maintenance burden
3. Migrate resources to domain's own admin namespace
4. Update domain service provider to register pages
5. Remove Filament resource files
6. Update documentation

**Domain Service Provider Pattern:**
Each domain's service provider registers its admin pages:
- Happens in `boot()` method
- Calls `AdminNavigationRegistry::register()` with page array
- Only registers pages user has permission to see
- Declares navigation group, order, icon

**Example Registration (Conceptual):**
When Auth domain boots, it registers:
- User management → custom page
- Roles → custom page
- Activation codes → custom page

When Moderation domain boots, it registers:
- Reports → custom page
- User moderation → custom page
- Moderation reasons → custom page

**Benefits:**
- Each domain owns its admin UI
- Domain boundaries respected
- Testing easier (domain-level tests)
- No cross-domain pollution

---

### Phase 4: Filament Removal (Optional, Month 6+)

**Decision Point:** Evaluate if Filament still provides value.

**Keep Filament If:**
- 20%+ of admin pages still use it effectively
- Team prefers Filament for certain CRUD operations
- Migration cost exceeds maintenance cost
- Filament features (form builder, etc.) are valuable

**Remove Filament If:**
- Less than 10% of admin pages use it
- Maintenance burden of dual system is high
- All remaining pages are simple CRUD (easy to migrate)
- Team confident in custom admin system

**Removal Process:**
1. Migrate final Filament resources to custom pages
2. Remove Filament packages from composer.json
3. Remove AdminServiceProvider (Filament panel definition)
4. Clean up any Filament-specific middleware
5. Remove navigation registry's Filament support code
6. Update deployment/build scripts if needed

---

## Technical Design Details

### Admin Navigation Registry Implementation

**Service Registration:**
- Registered as singleton in Admin domain's service provider
- Available via dependency injection or facade
- Stores navigation items in memory (built on each request)

**Registration Method Signature (Conceptual):**
- `register(string $route, string $label, string $group, array $permissions, ?string $icon, int $order, string $type)`
- Route can be route name or absolute URL
- Permissions array checked against current user
- Type distinguishes custom vs Filament pages

**Navigation Building:**
- Called during admin layout rendering
- Filters items based on current user permissions
- Groups items by navigation group
- Sorts by order within each group
- Returns structured array for blade rendering

**Permission Checking:**
- Uses existing CheckRole middleware logic
- Supports multiple roles (AND/OR logic)
- Hides menu items user cannot access
- Optional: shows locked icon for aspirational items

---

### Admin Layout Components

**Sidebar Navigation Component:**
- Receives navigation items from registry
- Renders collapsible groups
- Highlights current active page
- Shows icons + labels
- Supports badge/notification counts
- Mobile-responsive (hamburger menu)

**Permission Helpers:**
- Blade directives for role checking
- Reuse existing Auth domain permission logic
- No new authorization system needed

**Asset Loading:**
- Use existing Vite setup
- Admin-specific CSS can extend base styles
- Admin-specific JS modules can be added to bundle
- No conflicts since we control the pipeline

**Flash Messages:**
- Reuse existing flash message system
- Admin-specific styling if desired
- Positioned consistently across admin pages

---

### CRUD Migration Pattern

**Current Filament Resource Structure:**
```
app/Domains/Admin/Filament/Resources/{Domain}/
  {Model}Resource.php
  {Model}Resource/Pages/
    List{Model}.php
    Create{Model}.php
    Edit{Model}.php
```

**Target Custom Structure:**
```
app/Domains/{Domain}/Private/
  Controllers/Admin/
    {Model}Controller.php     # Resourceful controller
  Resources/views/pages/admin/
    {model}/
      index.blade.php          # List
      create.blade.php         # Create form
      edit.blade.php           # Edit form
      show.blade.php           # Optional detail view
```

**Migration Steps Per CRUD:**
1. Create resourceful controller using Artisan
2. Implement index, create, store, edit, update, destroy methods
3. Reuse existing domain services (no new business logic)
4. Create blade views extending admin layout
5. Use existing domain components for forms/tables
6. Add validation via Form Request classes
7. Write feature tests for all CRUD operations
8. Register in navigation registry (often just index page)
9. Remove Filament resource

**Form Handling:**
- Use standard Laravel validation
- Form Request classes in domain's Requests/ folder
- Blade forms with Tailwind styling (consistent with app)
- Can use our custom form components

**Table Rendering:**
- Livewire components if interactivity needed
- Alpine.js for client-side filtering/sorting
- Server-side pagination via Laravel paginator
- Export existing domain table components if available

---

### Routing Organization

**Option A: Single routes.php with groups**
Each domain's existing routes.php adds admin routes in a group:
- Prefix: `/admin/{domain}/` or `/{domain}/admin/`
- Middleware: auth, role-checking
- Name: `{domain}.admin.*`

**Option B: Separate routes.admin.php**
Each domain has optional routes.admin.php file:
- Loaded by domain service provider
- Clearer separation of public vs admin routes
- AdminNavigationRegistry can auto-discover from route names

**Recommendation:** Option A initially (simpler), migrate to Option B if admin routes become numerous.

**Route Naming Convention:**
- `{domain}.admin.{resource}.{action}`
- Example: `moderation.admin.users.index`
- Consistent with existing domain patterns

---

### Cross-Domain Admin Pages

**Challenge:** Some admin pages need data from multiple domains (e.g., user management: Auth + Profile + Moderation).

**Solution Strategies:**

**1. Primary Domain Ownership**
- Page lives in the domain most central to its purpose
- Example: User management → Moderation domain
- Uses other domains' public APIs to fetch data
- Respects domain boundaries

**2. Dedicated Admin Domain (Alternative)**
- Create `app/Domains/Admin/Private/Pages/CrossDomain/`
- Admin domain depends on other domains' public APIs
- Only for truly cross-domain features
- Most pages should live in their primary domain

**3. API Composition Pattern**
- Controller calls multiple domain APIs
- Composes data in controller
- Passes unified data to view
- View doesn't know about multiple domains

**Recommendation:** Strategy 1 (primary domain ownership) for most cases. Strategy 2 (Admin domain) only if you have many cross-domain pages.

---

### Testing Strategy

**Advantages of Custom Admin Pages:**
- Standard Laravel HTTP tests (get, post, put, delete)
- Can test authorization easily
- Can test form validation
- Can test business logic integration
- No Filament test complexity

**Test Structure:**
```
app/Domains/{Domain}/Tests/Feature/Admin/
  {Model}ControllerTest.php
```

**Test Coverage:**
- Index page renders with data
- Create form displays
- Store validates and creates record
- Edit form loads existing data
- Update validates and updates record
- Destroy removes record
- Authorization denies non-admin users
- Permission checking per role

**Test Helpers:**
- Reuse existing test helpers (admin(), moderator() functions)
- Mock external APIs if needed
- Use RefreshDatabase trait
- Factories for test data

---

## Design Decisions & Tradeoffs

### Decision 1: Coexistence vs Clean Break

**Chosen:** Coexistence (hybrid system)

**Rationale:**
- Lower risk (incremental migration)
- Can evaluate ROI per feature migration
- No "big bang" deployment
- Team can learn custom system gradually

**Tradeoff:**
- Maintenance of two admin systems temporarily
- Navigation complexity (two types of pages)
- Potential confusion for admins

### Decision 2: Centralized vs Distributed Admin Pages

**Chosen:** Distributed (domain-owned admin pages)

**Rationale:**
- Respects domain boundaries
- Each domain owns its admin UI
- Easier to test in domain context
- Aligns with our architecture philosophy

**Tradeoff:**
- Admin pages scattered across domains
- Need registry for navigation discovery
- More complex organization

### Decision 3: Custom vs Filament Components

**Chosen:** Custom components (our existing component library)

**Rationale:**
- No asset bundle conflicts
- Can reuse frontend components
- Full control over UX
- Better testability

**Tradeoff:**
- More work to build forms/tables
- No Filament form builder magic
- Need to maintain our own components

---

## Migration Checklist

### Before Starting
- [ ] Document all existing Filament resources
- [ ] Audit which resources are simple vs complex
- [ ] Identify cross-domain admin features
- [ ] Review team capacity & timeline

### Phase 1 Tasks
- [ ] Create admin layout blade template
- [ ] Build AdminNavigationRegistry service
- [ ] Register registry in service container
- [ ] Design navigation data structure
- [ ] Implement permission filtering logic
- [ ] Create first custom admin page (Moderation)
- [ ] Test custom page with full asset bundles
- [ ] Register custom page in navigation
- [ ] Add Filament pages to navigation registry
- [ ] Verify both page types accessible from menu

### Phase 2 Tasks (Per Feature Migration)
- [ ] Create controller in domain's Admin namespace
- [ ] Implement CRUD methods using domain services
- [ ] Create blade views extending admin layout
- [ ] Write feature tests for all actions
- [ ] Register in navigation registry
- [ ] Remove Filament resource
- [ ] Update any links/references
- [ ] Deploy and verify in production

### Phase 3 Tasks (Per Domain)
- [ ] Audit domain's Filament resources
- [ ] Prioritize migration order
- [ ] Migrate resources following Phase 2 pattern
- [ ] Update domain service provider registrations
- [ ] Clean up Filament resource files
- [ ] Update domain documentation

### Phase 4 Tasks (If Removing Filament)
- [ ] Migrate final Filament resources
- [ ] Remove Filament from composer.json
- [ ] Remove AdminServiceProvider
- [ ] Clean up Filament middleware
- [ ] Remove navigation registry Filament support
- [ ] Update deployment scripts
- [ ] Update documentation

---

## Success Metrics

### Technical Metrics
- **Asset bundle conflicts**: Zero (eliminated completely)
- **Test coverage**: 80%+ for custom admin pages
- **Page load time**: Equal or faster than Filament
- **Bug rate**: Lower than Filament pages

### Development Metrics
- **Time to add new admin page**: < 2 hours
- **Migration time per simple CRUD**: 2-4 hours
- **Migration time per complex feature**: 1-2 days
- **Developer satisfaction**: Team prefers custom system

### Business Metrics
- **Admin user satisfaction**: Equal or better UX
- **Feature velocity**: Can build complex admin features faster
- **Maintenance burden**: Lower overall (once migration complete)

---

## Open Questions & Decisions Needed

### 1. Navigation Registry API
- Fluent API vs array-based registration?
- Support for nested menu items (submenus)?
- Dynamic badge counts (live updates)?

### 2. Admin Layout Variations
- Single layout for all admin pages?
- Or multiple layouts (dashboard vs form vs table)?
- How to handle page-specific sidebar items?

### 3. Filament Integration Depth
- Link to Filament pages only?
- Or try to embed Filament navigation in our sidebar?
- Visual distinction between page types?

### 4. Permission System
- Reuse existing role checking?
- Or implement more granular permissions?
- Gate-based vs role-based vs policy-based?

### 5. Mobile Responsiveness
- Full mobile admin support?
- Or desktop-only with mobile message?
- Responsive sidebar behavior?

### 6. Deployment Strategy
- Feature flag for custom admin pages?
- Gradual rollout per user group?
- Or all-at-once per feature?

---

## Resources & References

### Internal Documentation
- `docs/Domain_Structure.md` - Domain architecture patterns
- `docs/Architecture.md` - Overall system architecture
- `.windsurf/rules/rules.md` - Coding standards & patterns

### External References
- Laravel HTTP Tests documentation
- Tailwind CSS admin dashboard patterns
- Alpine.js component patterns
- Laravel authorization (gates, policies)

### Similar Systems
- Laravel Nova (admin panel, but licensed)
- Backpack (admin panel, but opinionated)
- Custom Laravel admin systems (various OSS examples)

---

## Timeline Estimate

### Conservative (Safe)
- **Phase 1**: 2 weeks
- **Phase 2**: 3 months (migrate 5-10 features)
- **Phase 3**: 3 months (migrate remaining features)
- **Phase 4**: 1 month (Filament removal if chosen)
- **Total**: 7-8 months

### Aggressive (Risky)
- **Phase 1**: 1 week
- **Phase 2**: 6 weeks (focused migration)
- **Phase 3**: 6 weeks (parallel domain work)
- **Phase 4**: 2 weeks
- **Total**: 3-4 months

### Recommendation
Start conservative, accelerate once pattern is proven. Better to do it right than to rush and create technical debt.

---

## Conclusion

This custom admin system provides:
- ✅ Freedom from Filament asset constraints
- ✅ Full component reuse across app and admin
- ✅ Better testability
- ✅ Domain-oriented organization
- ✅ Gradual migration path (low risk)
- ✅ Flexibility for complex features

The hybrid approach allows evaluation of each feature's migration ROI while maintaining functional admin panel throughout transition.

**Next Step:** Review this document, discuss open questions, then implement Phase 1 foundation.
