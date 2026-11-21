---
name: devon-release
description: Prepare, verify, and hand off a release without confusing code completion with production readiness.
---

# Devon release

Use for build, ship, deploy, release readiness, production checks, and handoff.

## Release loop

1. Confirm the issue, acceptance criteria, deployment target, rollback path, and required approvals.
2. Check the working tree and reconcile concurrent remote work before pushing.
3. Run the smallest relevant lint, tests, build, and diff checks.
4. Deploy through the repository's documented path. Never assume a successful local build is live.
5. Verify the deployed commit, migrations, containers/processes, health endpoint, and the changed user path.
6. Record runtime, browser, device, and outcome evidence. Keep unverified items `Coded/Deploy-QA pending`.
7. Update the master plan, feature plan, next steps, architecture notes when needed, and chat log.

Generic environment deployment or QA skills may be used when available. If unavailable, perform the repository's documented checks and state what remains unverified.
