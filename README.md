# Hermes Backup

Backup of Hermes agent state from container `4dc68ea2b1fb` (created 2026-08-05).

## Contents

| Path | Description |
|---|---|
| `config.yaml` | Hermes main configuration (no inline secrets — api_key is an `${ENV}` reference) |
| `SOUL.md` | Agent identity/persona |
| `MEMORY.md`, `USER.md` | Persistent memory files |
| `skills/` | Installed skills |
| `cron/` | Scheduled jobs |
| `sessions/` | Session transcripts |
| `state.db`, `kanban.db`, `executions.db` | SQLite state databases |
| `channel_directory.json`, `gateway_state.json` | Gateway state |
| `hooks/`, `pairing/`, `platforms/` | Extensions config |
| `entrypoint.sh` | Container entrypoint script |

## Sensitive files (included — full backup)

- `.env` — real API keys (this is a PRIVATE repo; keys are included for full restore)
- `auth.json` — OAuth tokens / credential pools

## NOT included (regenerable only)

- `bin/` (uv, tirith binaries), `cache/`, `logs/` — re-downloadable/regenerable

## Restore

```bash
# copy files back into ~/.hermes/, then re-add keys:
#   .env  →  hermes config env-path
#   auth  →  hermes auth add <provider>
```
