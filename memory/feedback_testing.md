---
name: feedback-testing
description: Testing philosophy — only test endpoints/controllers, not models directly
metadata:
  type: feedback
---

Do not write model-level unit tests. Only write feature tests that exercise HTTP endpoints or controllers.

**Why:** Model tests are not considered good practice in this project; they test implementation details rather than behavior. Integration tests via HTTP endpoints provide more meaningful coverage.

**How to apply:** For any new domain, skip model tests entirely. Write tests in `Tests/Feature/` that go through routes/controllers. Start tests only from Phase 6 onward when endpoints exist.
