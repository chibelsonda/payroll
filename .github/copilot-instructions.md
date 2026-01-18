# GitHub Copilot Project Instructions

PRIMARY OBJECTIVE:
- Minimal changes
- Smallest diff possible
- Never rewrite files
- Never refactor unless asked

THINKING:
- Understand existing code first
- Fix ONLY the specific bug

NEVER:
- Reformat code
- Rename variables
- Reorder imports
- Change code style
- Touch unrelated code

FILE SCOPE:
- Only edit the file mentioned or the file with the bug
- If more than 2 files are needed → STOP AND ASK

MIGRATIONS:
- Do not edit old migrations
- No duplicate columns or indexes

DATABASE (Postgres):
- morphs(), unique(), foreignId()->constrained() already create indexes
- Never create duplicate indexes

SPATIE:
- Teams (company_id) is always enabled
- Never assignRole without company context

VUE:
- Do not change UI or layout
- Fix only logic bugs

TESTS:
- Never delete tests

SECURITY:
- Never bypass auth or permissions

PAYROLL:
- Never change payroll formulas or logic unless explicitly instructed

HARD STOPS:
If change affects:
- Schema
- Auth
- Roles
- Company model
- Payroll logic
→ STOP AND ASK FIRST

OUTPUT:
- Prefer small patches
- Avoid full file rewrites
