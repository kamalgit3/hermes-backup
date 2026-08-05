Multiple Railway containers — logs pasted from root@<hostname> may be a DIFFERENT machine; verify with `hostname`, guide via commands the user runs himself.
§
Node upgraded to 22.23.2/10.9.8 (NodeSource) to satisfy hermes update (node >=22.22.0, npm <11.10.0 || >=11.17.0).
§
HOME=/data = tiny ~434MB volume that fills fast — redirect caches to /opt: `npm config set cache /opt/.npm-cache`; node-gyp: `ln -s /opt/.cache /data/.cache`.
§
Railway bootstrap REWRITES /data/.hermes/.env on every container start, wiping manual edits — persistent vars must be set in Railway dashboard Variables, then redeploy.
§
User's governing response rules: (1) NEVER answer from guesswork/speculation — only respond with full certainty. (2) If data insufficient or uncertainty exists, state clearly you lack enough info and refuse probable/possible answers. (3) Base answers only on fully verified correctness. Prefers concrete working scripts/code over prose (said 'از اینایی که گفتی هیچی نفهمیدم'); deliver runnable files directly. Responses short, actionable, in Persian.
§
User strongly dislikes unsolicited edits/actions. Always confirm target before editing. Never assume .env change alone switches Hermes provider; provider/model config may need explicit update too.
§
SmsFinance: Android/Kotlin bank-SMS parser. Full project memory in PROJECT.md in repo kamal261/Hesabdary6. Build via GH Actions (kamal261 token); filter rules & padding-import bug documented there. See PROJECT.md before working on it.
§
User said 'دست به هیچی نزن' — do not edit files or make changes without explicit confirmation. Stop immediately if user says this.
§
TWO GitHub accounts, never mix: kamal266-1 = Hermes backup only (token in backup-hermes.sh); kamal261 = APK builds on Hesabdary6. Backup never touches kamal261; builds never touch kamal266-1.
§
API status (user re-sends keys; don't persist values): Gemini works — gemini-3.6-flash strong; NVIDIA works — deepseek-v4-pro; Grok exhausted; OpenRouter models-list-only (chat 401). railway_combo routes to deepseek-v4-flash. User wants strongest model for coding.
§
RULE (Kamal): after every long session (>15 min active work), check Railway disk: df -h /data /. If /data >80% full (critical): do safe cleanup (pip/npm cache, old .hermes/cache) yourself and report. If <80% (not critical): only report, wait for user's decision before cleaning.
