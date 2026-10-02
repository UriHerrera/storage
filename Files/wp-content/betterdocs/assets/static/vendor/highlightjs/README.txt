highlight.js v11.9.0 — bundled locally (BSD-3-Clause).

Source: https://github.com/highlightjs/highlight.js
Files:
  highlight.min.js  — "common languages" build (covers every entry in the block's
                      LANGUAGE_OPTIONS: js, ts, php, python, java, ruby, bash, json,
                      yaml, html/xml, css, sql, cpp, csharp, go, rust, swift, kotlin).
  github.min.css       — light syntax theme
  github-dark.min.css  — dark syntax theme

Vendored (not build-managed) so BetterDocs never loads it from a CDN — wp.org
compliance + self-hosted rendering. Same convention as assets/vendor/scalar/.
To upgrade: replace these three files with the matching version from cdnjs/jsDelivr.
