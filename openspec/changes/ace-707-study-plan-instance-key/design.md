## Context

`Resources/Private/TypeScript/frontend/academic-study-plan.ts`:

```ts
const instances = new Map<string, StudyPlan>();

const init = (): void => {
    document.querySelectorAll<HTMLElement>('.academic-study-plan').forEach((container): void => {
        const identifier = container.dataset.studyPlan ?? '';
        if (!instances.has(identifier)) {
            instances.set(identifier, new StudyPlan(container));
        }
    });
};
```

`init()` runs once per page in a browser, so the collision is between two
containers of the same page rather than between two runs.

## Goals / Non-Goals

**Goals:**

- Every plan of a page is started, once.

**Non-Goals:**

- Changing what a started plan does.
- Releasing an instance whose container was removed from the document.

## Decisions

### Key by the element

The map becomes `Map<HTMLElement, StudyPlan>`. The element is what the entry is
about, it is unique by construction, and it needs no attribute to exist. The
value of `data-study-plan` keeps its only other job — it is the uid a template
renders — and nothing reads it any more.

Rejected: requiring the attribute and making the value unique. It would make a
template override that drops it silently inert, which is the defect in another
spelling, and the module has no way to report it to an integrator.

### The map is not pruned, and says so

An instance whose container is removed from the document stays in the map and
stays subscribed to the resize handler. That was true before as well; it is
noted in the code rather than changed, because a container is removed by a
script this module knows nothing about and there is no event that would tell it.

## Risks / Trade-offs

- [Two plans of one page now both run] → That is the fix. Each instance only
  ever queries inside its own container, so the second plan cannot reach the
  first one's elements.

## Migration Plan

Nothing is required on update.

## Open Questions

None.
