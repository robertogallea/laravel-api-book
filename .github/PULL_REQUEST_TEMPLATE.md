## What and why

<!-- What does this change do, and why? Link the issue/discussion if there is one. -->

## Automated checks

The `Quality` workflow runs `composer quality` (style, tests, OpenAPI documentation generation)
on this pull request automatically. Do not re-check any of that by hand here: if it is red, fix
it before requesting a review.

## Review checklist

These are the things CI cannot judge for you. Answer honestly, not just by checking a box.

- [ ] Input is validated by a dedicated Form Request, not ad-hoc rules in the controller, and
      output goes through an API Resource, not a raw Eloquent model.
- [ ] Any new error case has an `ErrorCode` in the shared catalog and returns Problem Details,
      not a one-off shape invented for this endpoint.
- [ ] For a new or changed endpoint: who can call it unauthenticated, who can call it
      authenticated (and with which guard), and on exactly which resources? A role is not
      enough on its own; ownership of the specific resource must be checked too.
- [ ] The new or changed tests actually cover this change: the right feature/unit level, a
      denied-authorization case (not just the happy path), and any external dependency faked,
      not called for real.

## Definition of Done

- [ ] The `Quality` CI check is green on the latest commit of this pull request.
- [ ] At least one other team member has reviewed and approved this pull request.
- [ ] Every item above has an honest answer, not a box checked to move on.
