---
name: hermes-update-troubleshooting
description: "Use when 'hermes update' fails: EBADENGINE, ENOSPC, partial update."
version: 1.0.0
author: Hermes Agent
license: MIT
platforms: [linux, macos]
metadata:
  hermes:
    tags: [hermes, update, troubleshooting, node, npm, engine]
    related_skills: [hermes-agent]
---

# Hermes Update Troubleshooting

Companion to the bundled `hermes-agent` skill (which is protected and cannot be patched).
Use when `hermes update` fails on the Node.js step or the update ends "partially complete".
Full ordered repair sequence from a real container repair: `references/error-catalog.md`.

## First rule: which machine are you on?
Users paste update logs from a REMOTE shell (`root@<hostname>:/app# ...`). That hostname is
often a DIFFERENT container/account than your own environment. Before assuming anything about
node/npm/disk state on "their" machine:
1. Run `hostname` in YOUR environment and compare it to the hostname in their prompt.
2. If they differ, do NOT present your local `node --version` / `df -h` as their state.
3. Hand them commands to run in THEIR shell and ask for output back.

Pitfall: this bit in a session where my own container still had node 20.x while the user was
upgrading a separate container — local checks looked authoritative but were irrelevant. The
user corrected twice: "you're not on this structure", "this is another account".

## When to use
- `hermes update` exits with `npm ERR! code EBADENGINE` / `notsup Not compatible with your version of node/npm`
- Log ends with `⚠ Update partially complete — Node.js dependencies for repo root did not refresh`
- `npm ERR! errno -28 ENOSPC: no space left on device` during the npm step
- `hermes update` says `✓ Already up to date!` but the Node/UI build never completed
- `hermes web` / dashboard missing after update
- `npm ERR! ENOTEMPTY: directory not empty, rename ...` — dirty node_modules from an interrupted install
- `gyp ERR! not found: make` — missing C toolchain for native addons (node-pty)

## Root cause — the trap
The error text blames npm, e.g.:
```
npm ERR! notsup Actual:   {"npm":"9.2.0"}
✗ npm 9.2.0 does not satisfy the range this project requires: <11.10.0 || >=11.17.0
```
But npm 9.2.0 **is inside** the allowed range (`<11.10.0`). The real failure is usually
the `node` engine check — `"node":">=22.22.0"` vs an old system node (e.g. Debian's 20.x).
**Always check both versions before touching anything:**
```bash
node --version && npm --version
```
If node < 22.22.0 → node is the problem, not npm. The "upgrade your npm" hint in the
error is a red herring in that case.

## Fix — Debian/Ubuntu (incl. Docker containers)
Upgrade node to 22.x via NodeSource (ships its own npm 10.x):
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs
node --version && npm --version   # expect v22.23.2+ and npm 10.x
hermes update
```
Verified working combo: node 22.23.2 + npm 10.9.8.
The apt upgrade removes Debian's npm 9.2.0 and ~120 `node-*` helper packages — expected, harmless.

**Why Node 22, not 24:** Node 24 bundles npm 11.x, which can land in the forbidden window
11.10.0–11.16.x and re-trigger the exact same error.

## Symptom: ENOSPC "no space left on device" during npm install
`npm error errno -28 ENOSPC`. Diagnose with `df -h` — typically a small dedicated partition
(e.g. `/data` at 434M, 83% full) is the bottleneck while `/` (overlay) has hundreds of GB free.
npm cache lives under the Hermes home by default (`/data/.npm`).

Fix:
```bash
df -h                                  # find which partition is actually full
du -sh /data/* /data/.[!.]* 2>/dev/null | sort -rh | head -10   # find the real hog
npm cache clean --force                 # often frees NOTHING — see notes below
rm -rf /data/.cache                     # node-gyp header cache — the usual 200+MB hog
mkdir -p /opt/.cache && ln -s /opt/.cache /data/.cache   # keep it off /data permanently
npm config set cache /opt/.npm-cache   # move the npm cache to the big partition (persistent)
hermes update
```

Notes:
- `npm warn tar zlib: incorrect data check / tarball seems corrupted` during ENOSPC is a
  SYMPTOM of the full disk (corrupt cache writes), not a separate problem.
- `/data/.npm` may itself be tiny (KBs) — so `npm cache clean --force` frees nothing.
- The real hog on a small `/data` is usually the node-gyp download cache at `$HOME/.cache`
  (e.g. `/data/.cache`, ~255MB of Node headers, because HOME=/data). Symlinking it to the
  big partition makes the fix permanent — node-gyp writes through the link.
- `/data/.hermes` can also be a large consumer but is needed — leave it alone.

## Symptom: ENOTEMPTY — dirty node_modules from an interrupted install
After a partial/failed install, `npm install` dies with:
```
npm error ENOTEMPTY: directory not empty, rename 'node_modules/lucide-react' -> 'node_modules/.lucide-react-qYEoLgI8'
```
node_modules is in a half-moved state. Fix:
```bash
cd /opt/hermes-agent && rm -rf node_modules && npm install
```
(node_modules is pure dependency data — safe to delete; it reinstalls.)

## Symptom: `gyp ERR! not found: make` — missing C build toolchain
Native addons (node-pty) compile from source; Debian containers lack `make`/`g++`:
```bash
apt-get install -y build-essential
cd /opt/hermes-agent && npm install
```
A long frozen spinner during the native build is normal — it can take minutes.

## Symptom: "Already up to date!" after a failed npm install
After a partial update (e.g. ENOSPC), re-running `hermes update` may fetch 0 new commits and
SKIP the npm install — leaving `node_modules` in a mixed state while claiming "up to date!".
Code and Python deps are fine (verify with `hermes --version`); only the web UI/TUI Node deps
are stale.

Fix — run the workspace install directly (it re-runs even with no code changes):
```bash
cd /opt/hermes-agent && npm install
```
Then confirm with `hermes dashboard --status` (or re-run `hermes update`; with npm deps already
installed it completes the UI build).

## Symptom: bot silent with HTTP 401 after update
Every provider rejected with 401 right after the update's gateway restart — `Missing Authentication header` (openrouter) / `API key required for remote API access` (custom). The key is missing from the NEW process env, not invalid.
- `hermes auth list` entries like `#1 OPENROUTER_API_KEY api_key env:OPENROUTER_API_KEY` are POINTERS — the value must exist in the gateway process env.
- Custom providers configured via `hermes model` store the key as an env-var reference in config.yaml: `hermes config get model` → `api_key: ${HERMES_CUSTOM_<SLUGIFIED_BASE_URL>_API_KEY}` (name auto-derived from the base_url). Unset var → keyless request → 401.
- Check without printing the secret: `test -n "$VAR" && echo set || echo MISSING`
- **Railway trap:** the container bootstrap REWRITES `/data/.hermes/.env` on every start (`[bootstrap] Writing runtime env to /data/.hermes/.env`). Keys appended to .env by hand are wiped on the next redeploy. Durable fix: add the variable in Railway dashboard → service → Variables → Redeploy; bootstrap writes it into .env automatically.
- Finish: `hermes gateway restart`, then test with a chat message.

## Verification
- `hermes --version` (alias `hermes -V`) — version, install dir, Python version
- `hermes status` — component status incl. gateway
- `hermes dashboard --status` — whether the web UI is available
- `npm install` ending with `added N packages` and a subsequent `hermes update` printing
  `✓ Already up to date!` with NO "Web UI npm install failed" line = fully repaired

## Reading a healthy update log
- `Pre-update snapshot: <ts>-pre-update` → rollback point, safe to ignore on success
- `Currently on detached HEAD — switching to main` → normal git behavior
- Managed uv install + Python dep refresh → `warning: Failed to hardlink files` is benign (cross-filesystem)
- Node deps refresh → **the step that fails** on engine mismatch
- `Building web UI...` → "⚠ Web UI npm install failed" appears when node deps failed
- `draining gateway PID N` → gateway restart at end (PID 2 in containers). Chat coming back alive = success.

## Pitfalls
- Don't blindly follow the suggested `npm install -g npm@...` — if npm is already in-range, it fixes nothing; upgrade node.
- A partial update (code + Python OK, node deps stale) is repaired by fixing node and re-running `hermes update` — but if it then says "Already up to date!" without re-running npm, use the direct `cd /opt/hermes-agent && npm install` fix above. No rollback needed.
- Container context: user is root, Hermes data lives under `/data/.hermes`.
- After the gateway restart that ends `hermes update`, the bot can go silent with HTTP 401s — see "Symptom: bot silent with HTTP 401 after update" above. On Railway, never fix it by appending keys to `/data/.hermes/.env` — the bootstrap wipes that file on restart; set the vars in Railway dashboard Variables instead.

## Working with this user
Pooyamachine prefers to run system-modifying commands himself: provide exact commands to
paste and wait for his explicit go-ahead before touching anything. Reply in Persian.
