---
name: verify
description: How to verify anketo public-form frontend changes (JS/CSS) without a running PHP/MySQL stack, using a headless-Chrome harness.
---

# Verifying anketo changes

MySQL is usually down and `php spark serve` needs mysqli, so full end-to-end is often unavailable. For **public-form frontend changes** (anything under `public/assets/js/` or `public/assets/css/` used by `app/Views/public/`), verify with a static HTML harness in headless Chrome instead:

1. Write a harness HTML in the scratchpad that mirrors the real DOM from `app/Views/public/show.php` (`form[data-ak-paged-form]` > `section.ak-form-page[data-ak-form-page=N]` > `.ak-field` wrappers, plus the `.ak-page-actions` button bar with `data-ak-page-back/next/submit`) inside `.ak-form-card > .ak-form-body`. Copy field markup from `app/Views/public/fields/*.php`.
2. Load the **real repo assets** via absolute `file:///C:/Users/User/Desktop/05_Source_Code/anketo/public/assets/...` URLs (`theme.css`, `public-form.css`, the JS under test). Bootstrap is not needed; add `<style>.d-none{display:none!important}</style>` for button toggling fidelity.
3. Put a **synchronous** test script at end of body (after the JS under test): drive with `.click()` / `window.scrollTo()`, measure `window.scrollY`, `el.scrollWidth - el.clientWidth`, `getComputedStyle(...)`, and dump JSON into `<pre id="results">HARNESS_RESULTS ...</pre>`. No async — `--dump-dom` fires right after load.
4. Run:
   ```powershell
   & "C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --no-sandbox --force-device-scale-factor=1 --window-size=600,800 --dump-dom "file:///<scratchpad>/harness.html" | Select-String "HARNESS_RESULTS"
   ```
   `--screenshot="path.png"` instead of `--dump-dom` for a visual frame.
5. For an A/B sensitivity check, export pre-fix assets with `git show HEAD:public/assets/...` into the scratchpad and point a copy of the harness at them — the old assets should reproduce the bug.

Gotchas:
- Headless Chrome clamps the window to ~500px min width; to test mobile widths, constrain the harness wrapper div (e.g. `width:351px` = iPhone 375 minus container padding) instead of relying on `--window-size`.
- `window.innerWidth` reports the clamped window, not your wrapper — measure the element widths, not the viewport.
- Edge (`msedge.exe`) works the same way if Chrome is missing.
