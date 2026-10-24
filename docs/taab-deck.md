# The TAAB deck - what it asserts, and who owns each fact

`docs/TAAB-Presentation.pptx` is the live masterclass deck: 14 slides plus speaker notes
AJ reads aloud. It is the one artefact in this repo that **copies** facts the app owns,
because PowerPoint cannot read `config/`. So it goes stale silently, and it has twice gone
into a session quoting a price and a hosting requirement that had not been true for months.

This file is the checklist that stops that. Work through it whenever you roll a new edition
(see *Running a masterclass edition* in [operations.md](operations.md)).

## Facts the deck copies, and where they really live

| Fact | Owner | Slides that carry it |
|---|---|---|
| Session date, time, platform | `config/taab.php` → `masterclass.date` / `.time` | 1 |
| Pay-in-full price | `config/accelerator.php` → `price_full` | 12, 13, 14 |
| Attendee rate + code | `config/accelerator.php` → `coupons.TAAB100` | 12, 13, 14 |
| Installment plan | `installment_each` x `installment_count` | 12, 13, 14 |
| Programme length | 8 weeks (CLAUDE.md §2) | 12, 13 |
| Module / workflow count | `config/curriculum.php` (9 core modules, 9 workflows) | 13 |
| Scorecard dimensions, thresholds, buckets | `resources/views/taab/scorecard.blade.php` | 9, 10 + notes 9, 10 |
| Hosting cost and what blocks it | the Setup questions in the scorecard | 6, 10 + notes 7, 9, 10 |
| Running cost beyond tuition (one domain) | the Setup questions in the scorecard + `components/requirements-costs.blade.php` | notes 7 |
| Certificate of completion | `App\Support\Certificate` | 12, 13 + notes 13 |

**TAAB100 applies to the pay-in-full plan only** (`'plans' => ['full']`). Slides 13 and 14
deliberately quote the installment separately for that reason - do not merge them into one
"from ₦x" line, which would promise the attendee rate on a plan that cannot take it.

## Honesty rules this deck has broken before

- **The hosting blocker is not an international card.** Google Cloud's always-free tier now
  wants a **one-time ~$30 (≈₦45k) prepayment**, and **a Naira card works**. The deck and all
  three affected speaker notes said a real international card was required and a virtual one
  would not pass. That was the single most consequential wrong fact in here: it is the one
  thing the scorecard can hard-cap a result on, and AJ was reading it to the room.
- **No "pre-cohort prep track".** Slide 12's Path 02 used to promise one. It does not exist.
  Path 02 now offers the three tools and a retake, which do.
- **No early-bird.** `earlybird_seats` is `0`, so slides must not run an early-bird deadline.
  Note you turn early-bird off with `earlybird_seats`, never by clearing `earlybird_ends_at` -
  an empty cutoff means "no cutoff" and switches it back **on**.
- **Repetigo is not this brand.** Public identity is **AJBuilds AI / ajbuildai.com**; the legal
  entity is Deepr Web Services, and invoices come from Deepr Marketing. None of those are
  interchangeable on a slide.
- **Project figures on slide 5 are labelled "example projects".** Keep that label unless a
  named client and real proof replaces them.

## Editing it

There is no renderer in this environment (no LibreOffice, no `pdftoppm`), so **visual QA
cannot be done here** - text fit has to be reasoned about instead. Several text boxes set no
autofit (`bodyPr` has no `normAutofit`), which means they **clip** rather than shrink:

| Shape | Box | Size | Chars that fit |
|---|---|---|---|
| Slide 1 subtitle | 7.00in x 0.36in | 11pt | ~105, one line |
| Slide 10 item bodies | 4.20in x 0.78in | 11pt | ~175, three lines |
| Slide 6 stack items | 1.65in x 0.55in | 10.5pt | ~23, one line |
| Slide 12 path bodies | 2.65in x 1.72in | 11pt | ~165 |

Slides 13 and 14 use `spAutoFit`, so those grow instead of clipping - still keep the big
price line near 55 characters or it wraps to two lines.

Workflow, from the repo root:

```bash
python -c "import sys,zipfile; zipfile.ZipFile(sys.argv[1]).extractall('unpacked')" docs/TAAB-Presentation.pptx
# edit ppt/slides/slideN.xml and ppt/notesSlides/notesSlideN.xml by string replacement
# then repack with [Content_Types].xml first, and validate
```

Replace whole `<a:t>` runs matched on an ASCII prefix rather than retyping a run containing
`₦`, `·`, `×` or curly apostrophes - transcribing those by hand is how a replacement silently
matches nothing. Assert every replacement matched exactly once.

**Dashes are hyphens.** AJ asked for hyphens only in written output; the deck's text was
normalised in the Oct 2026 pass (60 em/en dashes replaced).

Validate with the `pptx` skill's `scripts/office/validate.py`, passing `--original` so the
template's own schema quirks are not read as new breakage, and read the content back with
`scripts/slide_text.py` before shipping.

## History

- **Jun 2026** - first refinement pass against a review doc (now superseded; see
  `taab-presentation-review.md`). Fixed "1-Day Virtual Event" to a 2-hour Google Meet session,
  killed the monthly-tool-cost narrative, merged the readiness slides.
- **24 Oct 2026** - overhaul for the Sat 31 Oct edition: date, brand, 8 weeks, ₦120,000 with
  TAAB100 at ₦100,000, early-bird removed, module count corrected to 9, certificate added,
  the international-card claim corrected on three notes slides, and speaker notes written for
  slide 13, which had none at all.
- **24 Oct 2026, later the same day** - the running-cost claim corrected on notes 7. It said
  "the only recurring cost is one cheap domain - about ten dollars a year", which is a `.com`
  price quoted as the floor; a `.site` or `.online` starts around $1 for the first year. The
  same wrong floor was live on five surfaces at once - `/accelerator`, the shared requirements
  table, the tool-stack guide (twice) and, worst of all, inside the **checkout no-surprises
  acknowledgement the buyer ticks** - and the scorecard additionally billed students for voice
  credits that Vapi gives away as a free $10 credit. Quote the first year, not a flat annual
  price - these TLDs renew dearer. Each page carries its own copy of the sentence, which is why
  they drifted together and why `ScorecardPageTest` now checks all four pages in one test.
  **Still unchanged:** the paid self-hosting guide says `$6-11` for a domain, because it walks
  the reader through buying a `.com` on Hostinger step by step. Changing the number there means
  changing the walkthrough.
