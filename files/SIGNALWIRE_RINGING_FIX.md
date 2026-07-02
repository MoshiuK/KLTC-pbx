# SignalWire "Phones Don't Always Ring" — Fix & Runbook

This documents the fixes pushed to `Knox-Media-Group/white-label-pbx`
(branch `claude/pbx-signalwire-integration-8hiql0`) for pbx.kltconnect.com,
plus the SignalWire dashboard and phone settings you must verify.

## What was broken in the code (now fixed)

1. **Phones were being provisioned to register with Telnyx, not SignalWire.**
   The auto-provisioning config sent the Fanvil V61G to `sip.telnyx.com` with
   Telnyx STUN. A phone registered to the wrong registrar never rings.
   It now registers phones to your SignalWire SIP domain.

2. **SIP registration expiry was 3600s (1 hour).** Home/office routers drop
   idle UDP NAT bindings after ~1–5 minutes. After the binding dropped,
   SignalWire could not reach the phone until it re-registered — so phones
   rang "sometimes" (right after boot/re-register) and went silent later.
   Now: re-register every **120s** + 30s keepalives. This is the classic
   cause of intermittent ringing.

3. **Silent port drift.** The app was allowed to start on a different port
   if 3003 was busy (e.g. a stale PM2 process). Nginx kept proxying
   pbx.kltconnect.com → 127.0.0.1:3003 to a dead/stale process, so every
   SignalWire webhook failed and no phone rang until a manual restart.
   In production the app now refuses to start unless it gets its configured
   port, so PM2 restarts it cleanly instead.

4. **Sequential ring groups pointed at a webhook that didn't exist**
   (`/next-in-sequence` → 404 → SignalWire dropped the call after the first
   phone). The webhook is now implemented and rings each member in turn.

5. **Endpoints stuck in `provisioning` status were silently excluded from
   ring groups.** Endpoints created in the UI start as `provisioning`, so
   ring-group members never rang. Now an endpoint rings unless explicitly
   disabled/suspended.

6. **No no-answer fallback.** If a phone didn't pick up, the caller got dead
   air then a hangup. Unanswered calls now go to voicemail.

7. **Phone number lookup was exact-match only.** SignalWire sends
   `+15551234567`; if the number was stored as `15551234567` or formatted,
   the call got "This number is not configured." Lookup now matches on
   digits regardless of formatting.

8. **Purchased numbers never had their voice webhook set**, so SignalWire
   had nowhere to send inbound calls. Purchasing now auto-points the number
   at the PBX, and there's an admin `syncNumberWebhooks` action to fix all
   existing numbers at once.

## Deploy the fix on the server

```bash
cd /opt/white-label-pbx
git fetch origin
git checkout claude/pbx-signalwire-integration-8hiql0
git pull
pnpm install
pnpm run build
pm2 restart white-label-pbx
pm2 logs white-label-pbx --lines 50   # confirm it bound to port 3003
```

Add these to `/opt/white-label-pbx/.env` (then `pm2 restart white-label-pbx`):

```env
PORT=3003
PUBLIC_URL=https://pbx.kltconnect.com
# Copy the exact value from SignalWire dashboard -> SIP -> Domains
SIGNALWIRE_SIP_DOMAIN=knoxlandin-526db06c4f67.sip.signalwire.com
```

## SignalWire dashboard checklist

For **every** phone number (Phone Numbers → Edit):

- **Handle calls using:** LaML Webhooks
- **When a call comes in:** `https://pbx.kltconnect.com/api/webhooks/voice` (POST)
- **Status callback:** `https://pbx.kltconnect.com/api/webhooks/status` (POST)

For every SIP endpoint (SIP → Endpoints) the phones register as
(e.g. `knox_101`, `knox_102`, `knox_103`):

- Encryption: **Optional** (not Required — Fanvil default config can't do SRTP)
- Codecs: make sure **PCMU/PCMA** are enabled

## Fanvil V61G settings (if configured manually)

Line → SIP:

| Setting | Value |
|---|---|
| Server/Proxy address | your SignalWire SIP domain (`...sip.signalwire.com`) |
| Port | 5060, transport UDP |
| Registration expiry | **120** seconds |
| Keep-alive | enabled, 30s |
| STUN | **disabled** |

## Grandstream settings (if configured manually)

Account 1:

| Setting | Value |
|---|---|
| SIP Server | your SignalWire SIP domain |
| SIP User ID / Auth ID | the endpoint username |
| Register Expiration | **2 minutes** |
| NAT Traversal | **Keep-alive** (not STUN) |

Grandstream phones can also auto-provision from
`https://pbx.kltconnect.com/provisioning/cfg<MAC>.xml` — supported now.

## If a phone still doesn't ring

1. On the phone, check the line shows **Registered**.
2. SignalWire dashboard → SIP → Registrations: the endpoint should be listed.
   If it disappears after a few minutes, the phone's registration expiry is
   too long or keep-alive is off (see tables above).
3. `pm2 logs white-label-pbx` while calling — you should see
   `[Webhook] Incoming call: ...`. If you don't, the number's voice webhook
   URL is wrong in SignalWire.
4. If you see `Phone number ... not found in database`, add the number to
   the customer in the PBX admin UI.
