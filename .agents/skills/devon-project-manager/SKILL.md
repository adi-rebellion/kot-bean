---
name: devon-project-manager
description: Plan, sequence, track, and organize product work across repositories and agents.
---

# Devon project manager

Use for multi-step work, interruptions, bugs, plans, handoffs, releases, and cross-repo dependencies.

## Operating contract

This skill is the execution control plane. Do not rely on memory, chat history, or a general
`AGENTS.md` summary when this skill applies. Read the repository's authoritative plans first,
then use this skill's workflow before making commitments or changing code.

## Source of truth

Find the repository's authoritative master execution plan, next-steps plan, feature plan, architecture anchor, and chat log before acting. Do not create a second priority list.

## Repository synchronization contract

The repository's operating model is a set of linked artifacts, not a single
document. Keep the control plane synchronized as work evolves:

1. Identify the authoritative dashboard or stable ticket for the request.
2. Update the ticket's status, owner, dependencies, acceptance criteria, and
   evidence in that dashboard or feature plan.
3. Update `master_execution_plan.md` when the work changes repo-wide priority,
   sequencing, ownership, or dependency state.
4. Update `plan_next_steps_for_this_codebase.md` when the next near-term action
   changes.
5. Update the active branch handoff when current state, resume instructions, or
   blockers change.
6. Update the relevant architecture, strategy, brand, legal, or policy source
   when the work changes durable product truth.
7. Append one dated entry to the appropriate chat log describing the decision,
   evidence, and affected files.
8. Update `.thisrepo/source-map.md` only when a recurring source or route is
   added, removed, or moved. Do not turn the source map into another status
   dashboard.
9. If a local `.agents/skills/devon-*` skill changes, update
   `.agents/skills.lock.yaml` (per-file `sha256`) and run
   `.agents/skills/verify-devon-skills.sh`. Mirror the same change under
   `.claude/skills/` when that copy is maintained. Record unresolved skill drift
   rather than silently choosing a copy.

Before handoff, check that links resolve, statuses agree across affected
artifacts, no duplicate queue was created, and every claimed completion has
the required evidence. If synchronization cannot be completed in the same
session, leave an explicit open ticket and mark the missing evidence rather
than implying that the system is current.

Before acting, record a short preflight:

1. Current branch and working-tree state.
2. Current master-sequence issue and next three unblocked issues.
3. Existing ticket that owns the request, or a new stable ticket if none exists.
4. What the request displaces, follows, or leaves unchanged.
5. Required approval, deployment, runtime, device, email, or external-system evidence.

## Triage

Classify every new request as:

- immediate blocker;
- current-priority work;
- parallel enabling work;
- deferred backlog;
- discovery or spike.

Preserve the active queue when context changes. Ticket accepted ideas with a stable key, owner, dependencies, acceptance criteria, risks, QA evidence, deployment target, and rollback notes. Do not build a future idea unless the user explicitly promotes it.

For every user-reported bug or accepted idea, close the loop in the same session:

- locate or create the owning ticket;
- update its status and acceptance criteria;
- sequence it in the master plan;
- add it to the next-steps plan when it is near-term;
- record the concrete evidence or explicitly mark evidence pending;
- state what work resumes afterward.

Never answer “yes, it is tracked” without naming the ticket and linking its plan location.
Never infer completion from code, a prior conversation, or a stale checklist. If the required
flow was not exercised, say so and keep the item open.

## Status

Use truthful states: `Todo/ACTIVE`, `Blocked`, `Todo/Deferred`, `Coded/Deploy-QA pending`, and `DONE`. Code is not done until the required runtime, device, platform, and outcome evidence exists.

## Handoff

At completion, update the feature plan, master plan, next steps, architecture notes when needed, and append-only chat log. Report the completed issue, next unblocked issue, older commitments still ahead, and the dependency or approval blocking the next step.

At every handoff, produce a compact execution ledger:

- Completed or currently active issue.
- Exact evidence collected.
- Open tickets caused or touched by the work.
- Next unblocked master-sequence issue.
- Older commitments still ahead of the tangent.
- Blocker, approval, or user-owned QA still required.
