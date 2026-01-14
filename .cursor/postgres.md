POSTGRES RULES

PostgreSQL is strict:
- No duplicate:
  - Index names
  - Column names
  - Constraint names

Always check:
- If column already indexed
- If unique() already creates index

Never:
- Add index blindly
- Assume MySQL behavior
