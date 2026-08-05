---
name: hermes-container-maintenance
description: "Use when Hermes update fails or /data fills on Railway."
version: 1.0.0
author: Hermes Agent
license: MIT
platforms: [linux]
metadata:
  hermes:
    tags: [hermes, railway, docker, npm, disk, troubleshooting, gateway]
---

# Hermes Container Maintenance (Railway / small-volume)

Covers the recurring failure chain on Railway containers running Hermes: `hermes update` engine errors, npm install failures, `/data` disk exhaustion, `.env` being wiped on redeploy, and gateway 401s after update-triggered restarts. Read `references/update-failure-modes.md` for exact error signatures and the full working fix sequence.

## Golden rules for this user
- User runs all commands himself on his own containers — deliver numbered copy-paste blocks; never execute on his machines.
- Respond in Persian (Farsi). Verify before claiming — user's governing rule: never answer from guesswork.
- Only `/data` is "ours" to manage; NEVER touch/clean the large overlay `/` mount (user's non-negotiable rule).
- Confirm before any edit; unsolicited `.env`/config edits are forbidden.

## Update engine requirement (the misleading npm error)
`hermes update` fails with `EBADENGINE ... Required: {"node":">=22.22.0","npm":"<11.10.0 || >=11.17.0"}` and prints "npm 9.2.0 does not satisfy..." even though npm 9.2.0 IS inside the allowed range. The real blocker is almost always **node too old** (Debian ships node 20 + npm 9).

Fix on Debian/Ubuntu:
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs
node --version && npm --version   # expect v22.22.0+ and npm 10.x
```
- Install **22.x, NOT 24.x**: Node 24 bundles npm 11 which can land in the forbidden 11.10–11.16 band.
- NodeSource install replaces Debian's npm and removes hundreds of Debian `node-*` packages — normal, ignore.

## Disk exhaustion on tiny /data
HOME=/data is a ~434MB volume; npm installs and node-gyp fill it fast (ENOSPC). The overlay `/` mount is huge — move caches there:
```bash
npm config set cache /opt/.npm-cache
mkdir -p /opt/.cache && ln -s /opt/.cache /data/.cache   # node-gyp cache lives under ~/.cache
```
When /data >80%: safe cleanup = `/data/.npm/_cacache` (tens of MB), `/data/.cache/uv`, `/data/.cache/pip`, `/data/.hermes/cache/*`. Keep `/data/.hermes/bin` (uv/tirith binaries). `npm cache clean --force` does NOT free the node-gyp cache — that's the big one (~255M).

## npm install failures after a broken update
- `ENOTEMPTY ... rename 'node_modules/x' -> 'node_modules/.x-<rand>'` → previous install died mid-flight; `rm -rf node_modules && npm install` in repo root (`/opt/hermes-agent`).
- `node-gyp ... not found: make` (node-pty native build) → `apt-get install -y build-essential`, retry.
- The `unicode-animations` ASCII banner + hardlink warning during install are normal npm output, not errors.

## Railway bootstrap wipes .env on every start
Container start logs `[bootstrap] Writing runtime env to /data/.hermes/.env` — manual `.env` edits do NOT survive a redeploy (hostname changes each deploy = new container). Persistent variables must be set in **Railway dashboard → Service → Variables**, then Redeploy.

## Custom provider 401 after gateway restart
Symptom: `401 API key required for remote API access` / `Missing Authentication header` right after an update-triggered gateway restart, though the same provider worked before.
- `hermes config get model` shows `api_key: ${SOME_DERIVED_ENV_NAME}` — Hermes auto-derived the env name from the base_url (e.g. `HERMES_CUSTOM_9ROUTER_..._API_KEY`).
- The gateway process must have that env var; verify with `test -n "$VAR" && echo set || echo MISSING`.
- Durable fix without the dashboard: `hermes config set model.api_key '<literal key>'` — config.yaml is NOT rewritten by bootstrap, so it survives restarts. (Give the command; the user runs it.)
- Creds: `hermes auth list` shows all; `hermes auth status` requires a provider arg.
- Validate a key directly: `curl -s <base_url>/v1/models -H "Authorization: Bearer <key>"` — a JSON model list means the key is valid; `401` means wrong/expired.

## Tooling pitfall
Files containing `§` separators (memory files) get flagged "Binary file" by read_file — display them with `python3 -c "print(open('<path>', encoding='utf-8').read())"`.
