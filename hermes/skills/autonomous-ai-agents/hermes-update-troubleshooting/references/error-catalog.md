# hermes update — Node.js failure repair: full sequence (verified 2026-08)

Ordered repair that took a Debian 13 (trixie) container from "Update partially complete"
to a fully clean `hermes update`, in one sitting. Each step = one distinct failure mode.

## 0. Environment facts (this container)
- Hermes at `/opt/hermes-agent`, venv at `/opt/venv`, `HOME=/data`
- `/data` is a tiny dedicated partition (434M) while `/` (overlay) has hundreds of GB free
- Root shell prompt: `root@7d184e4e7126:/app#` — user runs everything himself

## 1. EBADENGINE — node too old
Every npm op failed with:
```
npm ERR! notsup Required: {"node":">=22.22.0","npm":"<11.10.0 || >=11.17.0"}
npm ERR! notsup Actual:   {"npm":"9.2.0"}
✗ Managed Node.js provisioning failed
```
npm 9.2.0 satisfies `<11.10.0` — the message is misleading; the blocker is node 20.x.
Fix:
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs
node --version && npm --version   # → v22.23.2 / 10.9.8  ✅
```
Debian removes distro `npm` + ~121 `node-*` packages during this — expected, harmless.

## 2. ENOSPC — small /data partition
npm started installing for real, then died mid-write with `errno -28 ENOSPC`, plus a burst of
`npm warn tar zlib: incorrect data check / tarball ... corrupted` (disk-full side effect).
`npm cache clean --force` freed NOTHING (`/data/.npm` was 604K). The hog was the node-gyp
header cache: `/data/.cache` = 255M (node-gyp uses `$HOME/.cache`, HOME=/data).
Fix (permanent):
```bash
du -sh /data/* /data/.[!.]* 2>/dev/null | sort -rh | head -10   # confirm the hog
rm -rf /data/.cache
mkdir -p /opt/.cache && ln -s /opt/.cache /data/.cache   # node-gyp writes through the link
npm config set cache /opt/.npm-cache                       # npm cache off /data for good
rm -rf /data/.npm/_logs
df -h /data    # 98% → 38% used
```

## 3. ENOTEMPTY — dirty node_modules from the interrupted install
```
npm error ENOTEMPTY: directory not empty, rename 'node_modules/lucide-react' -> 'node_modules/.lucide-react-qYEoLgI8'
```
Fix: `cd /opt/hermes-agent && rm -rf node_modules && npm install` (node_modules is pure deps).

## 4. node-gyp `not found: make`
```
gyp ERR! not found: make
gyp ERR! command "/usr/bin/node" "/opt/hermes-agent/node_modules/.bin/node-gyp" "rebuild"
```
node-pty (native addon) needs a C toolchain. Fix: `apt-get install -y build-essential`,
then re-run `npm install`. The long frozen spinner is the native build — normal.

## 5. Success signals
```
added 1181 packages, changed 5 packages, and audited 1194 packages in 41s
hermes update → ✓ Already up to date!     (no "Web UI npm install failed" line)
hermes --version → Hermes Agent v0.20.0
```
After a manual `npm install`, `hermes update` saying "Already up to date!" IS the success
outcome — not a no-op.

## 6. Post-update gateway silence — 401 (VERIFIED diagnosis, same session)
Every provider 401'd right after the update's gateway restart, while the same endpoints had
worked minutes earlier. Verified chain:
- `hermes auth list` → credentials exist but most are `env:VAR` POINTERS (e.g.
  `OPENROUTER_API_KEY api_key env:OPENROUTER_API_KEY`) — the value must be in the gateway process env.
- `hermes config get model` → `api_key: ${HERMES_CUSTOM_9ROUTER_PRODUCTION_0A57_UP_RAILWAY_APP_API_KEY}`
  — Hermes auto-derives an env-var name from the custom base_url; the request goes out keyless
  when that var is unset.
- `test -n "$VAR" && echo set || echo MISSING` → MISSING confirmed.
- Log line `[bootstrap] Writing runtime env to /data/.hermes/.env` → Railway's bootstrap REWRITES
  .env on every container start, so hand-appended keys do NOT survive a redeploy.
Fix (durable): add the env var in Railway dashboard → service → Variables → Redeploy; bootstrap
writes it into .env automatically. Then `hermes gateway restart` and test with a chat message.
