# n8n workflow exports

| File | Tracked | What it is |
|---|---|---|
| `accelerator-installments.json` | **yes** | Installment + balance emails. Contract: [../n8n-enrollment-flow.md](../n8n-enrollment-flow.md) |
| `aj-buildai-waitlist-masterclass.json` | no | The live TAAB funnel workflow. Contract: [../n8n-masterclass-flow.md](../n8n-masterclass-flow.md) |
| `debug-clinic.json` | no | Scratch/diagnostic workflow |

## Why most exports aren't committed

A full n8n export inlines **live credential material** — Twilio account SIDs, basic-auth
references, whatever an HTTP node happens to carry. GitHub's push protection rejects those,
and it's right to. So exports stay local by default and this directory is staged file by
file, never with `git add -A`.

`accelerator-installments.json` is the exception: it was authored here rather than exported
from a live instance, and it carries no secret material. The only identifier in it is an
opaque n8n credential *reference* (`7RGhNLHFADXdsIsp`), which is useless off the instance.

**Before committing any export, scan it.** If it contains a token, key, account SID or
password, leave it untracked and document the workflow in markdown instead.

## The email bodies are duplicated on purpose

The three installment email templates live in both
[../emails/](../emails/) and inside `accelerator-installments.json`. They were generated
together so they start identical, but nothing keeps them in sync afterwards — same rule as
the written guides: **it's a pair, edit both halves.** The markdown contract is the place to
check when they disagree.

## Round-tripping

Re-exporting from n8n after a UI edit will reformat the whole file and may reintroduce
credential material. Diff it before staging, and drop anything the sticky notes or this
README say shouldn't be here.
