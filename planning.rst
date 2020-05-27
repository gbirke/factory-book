Planned evolution of code and architecture

1. single all-in-one files
2. Fix SRP violation by splitting Presentation, Business Logic (call it
   "Route") and Persistence (but still keeping files). Mention that we
   don't have acceptance/E2E tests yet and that normally you should do
   that.
3. Fix DRY violation (object creation) by creating a factory (still
   keeping files)
4. Fix DRY violation (request cycle) by creating a front controller
5. Thinking about testing - reveals that our code still violates SRP -
   Mixes web framework (infrastructure) and business logic.
6. Clean architecture and CQRS - Fix Dependency Inversion





