---
name: hermes-container-ops
description: "Hermes container ops: updates, disk-full, 401s."
version: 1.0.0
license: MIT
platforms: [linux, macos, windows]
metadata:
  hermes:
    tags: [hermes, container, railway, devops, update, troubleshooting, selfhosted]
---

# Hermes Container Ops

Maintenance and troubleshooting for self-hosted Hermes Agent containers (Railway, Docker, VPS). Covers update failures, disk pressure, and gateway auth loss after restarts. Session-specific transcripts in `references/railway-quirks.md`.

## When to use
- `hermes update` fails at the npm/node step (EBADENGINE, ENOSPC, ENOTEMPTY, node-pty build errors)
- Disk-full on a small persistent volume (`/data` on Railway bind mounts) while `/` looks fine
- Gateway answers everything with HTTP 401 after a restart/redeploy ("API key required for remote API access")
- Safe disk cleanup of a Hermes container

## 0. Recon first — always
```bash
df -h / /data          # distinguish root overlay from small bind-mounted volume
node --version && npm --version
hermes --version
hermes config get model   # watch for ${ENV_VAR} placeholders in api_key
```
On Railway only `/data` persists; `/` is ephemeral. Hermes data lives in `/data/.hermes`. Check `df` per-mount before blaming npm for ENOSPC — the failing mount may be a small bind volume.

## 1. npm EBADENGINE during update
Symptom: `npm ERR! notsup Required: {"node":">=22.22.0","npm":"<11.10.0 || >=11.17.0"} Actual: {"npm":"9.2.0"}`.

**Pitfall: the message blames npm, but if the installed npm IS within the allowed range, the real blocker is an old node.** Check `node --version` before touching npm.

Fix — upgrade node, not npm:
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs
```
Prefer **Node 22.x** (bundles npm 10.x, satisfies `<11.10.0`). Node 24.x bundles npm 11.x which can land in the forbidden 11.10–11.16 gap. Verify: `node --version && npm --version`.

## 2. node-pty native build fails (`gyp ERR! not found: make`)
`node-pty` compiles from source during `npm install`; needs build tools:
```bash
apt-get install -y build-essential
cd /opt/hermes-agent && rm -rf node_modules && npm install
```

## 3. ENOTEMPTY after a partial npm install
A failed earlier install leaves `node_modules` with half-moved dirs (`npm error ENOTEMPTY: rename 'node_modules/X' -> 'node_modules/.X-XXXX'`). Fix: `rm -rf node_modules && npm install` (occasionally needs two clean runs). node_modules is pure dependency — safe to delete; the code lives in the repo root, not in node_modules.

## 4. ENOSPC — small `/data` volume (Railway)
`/data` is typically ~400MB while `/` has TBs. `npm cache clean --force` often does NOT help — the real consumer is usually `/data/.cache` (node-gyp downloads Node headers to compile native modules, ~250MB), because `HOME=/data` routes all user caches (npm, node-gyp, uv, pip) to the small volume.

Permanent fix — relocate caches to the big volume:
```bash
npm config set cache /opt/.npm-cache
mkdir -p /opt/.cache && ln -s /opt/.cache /data/.cache   # node-gyp uses $HOME/.cache; symlink catches it
```
Verify `df -h /data`, then `rm -rf node_modules && npm install`.

## 5. Gateway HTTP 401 after restart
Symptom: everything worked, then after gateway restart/redeploy every call fails with 401 `API key required for remote API access` or `Missing Authentication header`. The provider config references an env var that isn't set in the gateway process.

Diagnosis:
```bash
hermes auth list                    # NOTE: `hermes auth status` REQUIRES a provider arg; list shows all
hermes config get model             # api_key: ${SOME_ENV_VAR} = placeholder; the env var is the problem
test -n "$SOME_ENV_VAR" && echo set || echo MISSING
curl -s <base_url>/v1/models -H "Authorization: Bearer <real-key>"   # proves key validity; JSON list = good
```

Key facts:
- Credential rows showing `env:VAR` (e.g. `OPENROUTER_API_KEY api_key env:OPENROUTER_API_KEY`) mean the value comes from the **process environment**, not stored auth. Missing env → requests go out unauthenticated.
- On Railway, bootstrap **rewrites `/data/.hermes/.env` on every container start** — manual `.env` edits do NOT survive restarts. Two durable fixes:
  - (a) set the variable in **Railway Variables** (bootstrap copies them into `.env`), then Redeploy; or
  - (b) set the key **directly in config.yaml** — bootstrap never touches it:
    ```bash
    hermes config set model.api_key '<real-key>'
    hermes gateway restart
    ```

Security: if a key was pasted into chat/terminal/shell history, recommend rotating it after the fix.

## 6. Safe disk cleanup on `/data`
`du -sh /data/* /data/.[!.]* | sort -rh` first. Safe to delete: `/data/.npm/_cacache` and `_prebuilds`, `/data/.cache/uv`, `/data/.cache/pip`, `/data/.hermes/cache/*` (uploaded documents/media copies), logs older than a few days (`find /data/.hermes/logs -type f -mtime +2 -delete`).
Do NOT delete: `/data/.hermes/bin` (uv, tirith binaries Hermes needs), `skills/`, `memories/`, `state.db`, `config.yaml`, `.env`, `sessions/`, `cron/`.

## Pitfalls
- Diagnosing "disk full" from the npm error alone — always `df -h` per-mount first.
- `hermes auth status` without a provider arg errors; use `hermes auth list`.
- After ANY `hermes update`, expect a gateway restart; verify the bot still answers (401 is the classic post-restart failure).
- On Railway, never hand-edit `.env` for persistence — bootstrap overwrites it every start.
- The user may run Hermes on several machines; confirm which one you're dealing with before recommending machine-specific paths.
