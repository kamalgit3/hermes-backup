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

## NOT included (for security / regenerable)

- `.env` — real API keys (excluded intentionally; see `.env.example` pattern: `HERMES_CUSTOM_*_API_KEY`)
- `auth.json` — OAuth tokens / credential pools
- `bin/` (uv, tirith binaries), `cache/`, `logs/`, `image_cache/`, `audio_cache/`, `models_dev_cache.json` — re-downloadable/regenerable

## Restore

```bash
# copy files back into ~/.hermes/, then re-add keys:
#   .env  →  hermes config env-path
#   auth  →  hermes auth add <provider>
```
