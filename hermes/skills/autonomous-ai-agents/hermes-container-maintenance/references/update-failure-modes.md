# Hermes update failure modes (Railway containers)

Exact error signatures observed, for pattern-matching new reports. Fixes in SKILL.md; this file is the diagnostic cheat-sheet.

## 1. EBADENGINE (node/npm engine mismatch)
```
npm ERR! code EBADENGINE
npm ERR! notsup Required: {"node":">=22.22.0","npm":"<11.10.0 || >=11.17.0"}
npm ERR! notsup Actual:   {"npm":"9.2.0"}
✗ npm 9.2.0 does not satisfy the range this project requires: <11.10.0 || >=11.17.0
```
Misleading: npm 9.2.0 satisfies `<11.10.0`. Root cause is node 20.x (`node --version`). Also note: `hermes update` auto-switches a detached HEAD to `main` before pulling — the `⚠ Currently on detached HEAD` line is benign.

## 2. ENOSPC during npm install (tiny /data)
```
npm error code ENOSPC
npm error nospc ENOSPC: no space left on device, write
```
Often preceded by a burst of `npm warn tar zlib: incorrect data check ... seems to be corrupted. Refreshing cache.` — corrupted-tarball warnings are a SYMPTOM of a full disk, not a separate problem. `df -h` shows /data ~434M at 98%.

Biggest consumers seen (du -sh /data/*):
- `/data/.cache` — node-gyp headers for the node version, ~255M (biggest!)
- `/data/.npm/_cacache` — ~39M
- `/data/.hermes` — ~155M incl. `/data/.hermes/bin` (uv 56M + tirith 23M — keep these)

`npm cache clean --force` does NOT free the node-gyp cache. Redirect it: `ln -s /opt/.cache /data/.cache`.

## 3. ENOTEMPTY (dirty node_modules from interrupted install)
```
npm error code ENOTEMPTY
npm error path /opt/hermes-agent/node_modules/lucide-react
npm error dest /opt/hermes-agent/node_modules/.lucide-react-qYEoLgI8
```
Fix: `cd /opt/hermes-agent && rm -rf node_modules && npm install`. May recur once per stale dir; a clean rm -rf resolves it.

## 4. node-gyp "not found: make" (native module build)
```
gyp ERR! stack Error: not found: make
```
While building `node-pty` (or any native addon). Fix: `apt-get install -y build-essential`. Python is usually already present (Hermes venv at /opt/venv).

## 5. Gateway 401 after update restart
Timeline pattern: provider works (only upstream 502 rate-limits) → `hermes update` drains/restarts gateway → every call now `401 API key required for remote API access` (custom) or `Missing Authentication header` (openrouter).
- `hermes config get model` reveals `api_key: ${ENV_DERIVED_FROM_BASE_URL}` (e.g. `HERMES_CUSTOM_9ROUTER_PRODUCTION_0A57_UP_RAILWAY_APP_API_KEY`).
- `hermes auth list` may show the provider as `custom:<base_url>` with a `model_config` credential — meaning the key lives in config, not the auth store.
- Manual `.env` appends get wiped on next deploy: log line `[bootstrap] Writing runtime env to /data/.hermes/.env` + hostname changes (new container).
- Durable fixes: (a) Railway dashboard Variables → Redeploy; (b) `hermes config set model.api_key '<literal key>'` (config.yaml survives bootstrap).
- Key validation: `curl -s <base_url>/v1/models -H "Authorization: Bearer <key>"` → JSON model list = valid; `{"error":"API key required..."}` = wrong/expired key or missing header.

## 6. Working end-to-end sequence
1. `curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs` → node 22.23.2 / npm 10.9.8
2. `apt-get install -y build-essential`
3. `npm config set cache /opt/.npm-cache` and `mkdir -p /opt/.cache && ln -s /opt/.cache /data/.cache`
4. `cd /opt/hermes-agent && rm -rf node_modules && npm install` → ~1181 packages, ~41s
5. `hermes update` → "Already up to date!" with no "Web UI npm install failed" warning
6. Resolve provider key (Railway Variables or `hermes config set model.api_key`) → `hermes gateway restart`
7. Sanity: `hermes --version`, then send a test message in chat.

## Other observed log noise (benign)
- `SQLite 3.46.1 is vulnerable to the WAL-reset corruption bug` — informational; Hermes falls back to journal_mode=DELETE.
- `check_fn ... returned False; dependent tools will be unavailable` — per-turn tool gating, not an error.
- `Auxiliary: marking openrouter unhealthy for 600s` — transient provider circuit-breaker.
