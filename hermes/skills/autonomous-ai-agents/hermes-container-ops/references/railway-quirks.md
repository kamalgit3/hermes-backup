# Railway-Hosted Hermes — observed quirks (2026-08 incident)

Real incident log: update + auth recovery on a Railway-deployed Hermes container, with two sibling containers on the same host.

## Environment observed
- Debian trixie cloud container, root shell; hostname changes on every redeploy (7d184e4e7126 → 43f91849186f)
- `/data` = 434M bind volume (`/dev/zd17184`), `/` = overlay 1.9T
- `HOME=/data` → all per-user caches (npm, node-gyp, uv, pip) land on the small volume
- Hermes at /opt/hermes-agent, venv at /opt/venv (Python 3.11.15), gateway runs as PID 2 under tini → `/app/scripts/entrypoint.sh`

## Update failure chain (exact symptoms → verified fixes)
1. **EBADENGINE**: `notsup Required: {"node":">=22.22.0","npm":"<11.10.0 || >=11.17.0"} Actual: {"npm":"9.2.0"}` — node 20.19.2 was the real blocker (npm 9.2.0 IS in range). Also: "Hermes could not provision its own Node.js runtime" warning. Fixed via NodeSource `setup_22.x` → node 22.23.2 + npm 10.9.8. Note: Debian's npm uninstall pulled 121 `node-*` lib packages — harmless, Hermes doesn't need them.
2. **ENOSPC** during `npm install` (209 pkgs): `/data` at 9M free. `npm cache clean --force` did NOT help — `/data/.npm` was only 604K. Real consumer: `/data/.cache` = 255M (node-gyp Node headers). Fixed: `npm config set cache /opt/.npm-cache` + `mkdir -p /opt/.cache && ln -s /opt/.cache /data/.cache` → freed 264M.
3. **`gyp ERR! not found: make`** on node-pty 1.1.0 → `apt-get install -y build-essential`.
4. **ENOTEMPTY** `rename 'node_modules/lucide-react' -> 'node_modules/.lucide-react-qYEoLgI8'` from earlier partial install → `rm -rf node_modules && npm install` → `added 1181 packages in 41s`, clean.
5. After that, `hermes update` → "Already up to date!" with no Node errors; web UI built. `hermes --version` → v0.20.0.

## Auth 401 saga
- `hermes model` prints "Could not fetch models from endpoint" but still sets the model (railway_combo).
- `hermes config get model` → `api_key: ${HERMES_CUSTOM_9ROUTER_PRODUCTION_0A57_UP_RAILWAY_APP_API_KEY}` — placeholder env auto-derived from base_url; the env var was MISSING in the gateway process.
- `hermes auth list` rows: `OPENROUTER_API_KEY api_key env:OPENROUTER_API_KEY` → value comes from process env, not stored; missing env → unauthenticated requests.
- `hermes auth status` alone errors: `the following arguments are required: provider` — use `hermes auth list`.
- Manual `echo 'KEY=...' >> /data/.hermes/.env` + `hermes gateway restart` worked until the next container start; then hostname changed (new container) and `grep -c 'HERMES_CUSTOM' /data/.hermes/.env` → `0`.
- Log line proving the cause: `[bootstrap] Writing runtime env to /data/.hermes/.env` right after `Mounting volume on: /var/lib/containers/railwayapp/bind-mounts/...`.
- Key validated independently: `curl -s <base_url>/v1/models -H "Authorization: Bearer <key>"` → returned model list including `railway_combo`.
- Durable fixes (proposed; user pivoted before final verification): (a) Railway Variables → bootstrap copies into .env each start; (b) `hermes config set model.api_key <key>` — config.yaml is never touched by bootstrap.

## Auxiliary/auth noise in logs (recognize & ignore)
- `WARNING tools.registry: check_fn ... returned False` — normal when optional tools lack deps.
- `kanban.db ... SQLite WAL-reset corruption bug` warning — informational; Hermes auto-falls back to journal_mode=DELETE.
- `Auxiliary client: marking <provider> unhealthy for 600s (payment / credit error)` — follows any 401; auto-clears after the timeout.

## Working style of the user in this incident
- Persian-speaking; replies expected in Persian; wants numbered copy-paste command blocks and to run them himself ("دست به چیزی نزن تا بگم بهت").
- Runs the bot with a custom provider: base_url `https://9router-production-0a57.up.railway.app/v1`, model `railway_combo` (9router/Nvidia combo router).
