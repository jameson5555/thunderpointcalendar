# Thunderpoint accessibility standard

Thunderpoint targets WCAG 2.2 Level AA for every public and authenticated workflow. Automated checks are required on each pull request, but passing automation alone is not a conformance claim. A public claim may be made only after the manual and assistive-technology checks below pass.

## Approved visual system

The area color is an identity cue, so legend swatches, calendar bars, and text-bearing area chips use the same vibrant background. Their foreground changes between warm near-black and cream to keep the original fill while maintaining readable labels.

| Area | Background everywhere | Label text |
| --- | --- | --- |
| Boathouse | `#ED7009` | `#17120F` |
| Jack's | `#1A8C91` | `#17120F` |
| Joyce's | `#E7A30F` | `#17120F` |
| Jann's | `#6F7429` | `#FFFDF5` |

Use the semantic CSS tokens `--tp-error`, `--tp-status`, `--tp-link`, `--tp-text-accent`, and `--tp-focus` for their named purposes. Do not substitute decorative orange, teal, or yellow for small text. Normal text requires 4.5:1 contrast; large text and meaningful component boundaries require 3:1.

## Component requirements

- Every route has a unique document title, one H1, a “Skip to main content” link, and a stable `main#main-content` landmark.
- Controls have visible labels where practical and complete accessible names. Decorative swatches and icons are hidden from assistive technology.
- Validation errors use stable IDs, `aria-invalid`, `aria-describedby`, a focusable error summary, and field-level guidance. Errors use `role="alert"`; successful asynchronous notices use `role="status"`. Do not nest or duplicate live regions.
- Menus expose `aria-expanded` and `aria-controls`, close with Escape, and restore focus to their trigger.
- Modal dialogs have a programmatic name and modal state, contain focus, close with Escape, make background content inert, and restore focus. Initial focus belongs on a useful heading or the first field requiring action.
- Stay dates use separately labeled Arrival date and Departure date fields. Each accepts `MM/DD/YYYY` and has its own button for the shared Vanilla Calendar Pro popup. The popup follows grid keyboard behavior, disables unavailable dates, rejects ranges crossing them, announces the selection summary, and returns focus on close. Preserve the hidden `start_date` and `end_date` ISO fields used by the backend.
- Interactive controls should retain a 44 by 44 CSS-pixel target where practical. Calendar booking bars may be 24 pixels high when spacing prevents adjacent-target interference.
- New animation must honor `prefers-reduced-motion`. New custom colors and boundaries must remain discernible in forced-colors mode.

## Automated checks

Run:

```sh
npm run test:a11y
```

The Playwright suite provisions an isolated SQLite database and checks public pages, admin and standard-user views, navigation, dialogs, the date picker, viewport overflow, headings, titles, labels, focus restoration, and axe-core WCAG rules. CI installs Chromium and runs this command before a successful CI result can trigger staging deployment.

When adding a route or interactive state, add it to `tests/a11y/accessibility.spec.js`. Treat automated exceptions as temporary defects: document the affected success criterion, scope, owner, and removal date here before suppressing a rule.

## Manual release checklist

Test the complete public, member, Poobah, and admin workflows:

1. Keyboard only: visible focus, logical order, no traps, menus/tabs/dialogs/date selection, Escape, and focus restoration.
2. Browser zoom at 200% and 400%, including a 320 CSS-pixel viewport: reflow, readable content, and no two-dimensional scrolling except essential grids.
3. WCAG text-spacing overrides: 1.5 line height, 2× paragraph spacing, 0.12em letter spacing, and 0.16em word spacing.
4. Windows forced-colors/high-contrast mode and reduced-motion mode.
5. VoiceOver with current Safari on macOS/iOS and NVDA with current Chrome on Windows: landmarks, headings, names, states, errors, status messages, tables/grids, and dialogs.
6. Contrast for any new foreground/background pairing and non-text boundaries.

Repeat this checklist after major UI changes and at least quarterly. Record tester, browser/AT versions, findings, and fixes in the audit history.

## Known exceptions

No intentional WCAG 2.2 AA exceptions are approved. Third-party calendar markup must be rechecked whenever `vanilla-calendar-pro` changes; the dependency is pinned to `3.3.1` until that review is complete.

## Audit history

| Date | Scope | Result |
| --- | --- | --- |
| 2026-09-03 | Public, dashboard, admin, profile, navigation, dialogs, form errors, color system, and date picker | Remediation implemented; automated regression coverage added. Manual VoiceOver, NVDA, zoom, spacing, forced-colors, and reduced-motion sign-off remains required before a conformance claim. |
