# White Label PBX - Quick Reference

## 🚀 Common Commands

### Development
```bash
pnpm run dev          # Start development server
pnpm run build        # Build for production
pnpm run start        # Start production server
pnpm test             # Run tests
pnpm run check        # TypeScript type checking
node diagnose.js      # Run system diagnostics
```

### Database
```bash
pnpm run db:push      # Apply schema changes
```

## 🔑 Environment Variables (Essential)

```env
DATABASE_URL=mysql://user:pass@host:3306/db
SIGNALWIRE_PROJECT_ID=xxxxxxxx
SIGNALWIRE_API_TOKEN=xxxxxxxx
SIGNALWIRE_SPACE_URL=yourspace.signalwire.com
JWT_SECRET=min-32-characters-secret
```

## 📞 Webhook URLs (Configure in SignalWire)

All webhooks need to be publicly accessible (use ngrok for dev):

```
Voice URL:     https://your-domain.com/api/webhooks/voice
Status URL:    https://your-domain.com/api/webhooks/call-status
SWAIG URL:     https://your-domain.com/api/webhooks/swaig-transfer
```

## 🛠️ Quick Fixes

### Customer Stuck on 'Pending'
```sql
UPDATE customers SET status = 'active' WHERE status = 'pending';
```

### Update SIP Domain for Transfers
Edit `server/webhooks.ts` line 666:
```typescript
const sipDomain = 'your-actual-space.sip.signalwire.com';
```

### Enable SMS Summaries
```sql
UPDATE customers 
SET smsSummaryEnabled = 1, 
    notificationPhone = '+15551234567' 
WHERE id = 1;
```

## 🔍 Testing SignalWire Connection

```bash
# Quick test with curl
curl -u "$SIGNALWIRE_PROJECT_ID:$SIGNALWIRE_API_TOKEN" \
  "https://$SIGNALWIRE_SPACE_URL/api/laml/2010-04-01/Accounts/$SIGNALWIRE_PROJECT_ID"
```

## 📱 Common Database Queries

### List Customers
```sql
SELECT id, name, email, status FROM customers;
```

### List SIP Endpoints
```sql
SELECT e.id, e.username, e.extensionNumber, c.name as customer
FROM sipEndpoints e
JOIN customers c ON e.customerId = c.id
WHERE e.status = 'active';
```

### List Phone Numbers
```sql
SELECT p.phoneNumber, p.friendlyName, c.name as customer
FROM phoneNumbers p
JOIN customers c ON p.customerId = c.id;
```

### Call Statistics
```sql
SELECT 
  c.name,
  COUNT(r.id) as total_recordings,
  SUM(r.duration) as total_minutes
FROM customers c
LEFT JOIN callRecordings r ON c.id = r.customerId
GROUP BY c.id;
```

## 🎯 AI Agent SWAIG Configuration

In SignalWire Call Flow Builder, add SWAIG function:

```json
{
  "function": "transfer",
  "web_hook_url": "https://your-domain.com/api/webhooks/swaig-transfer",
  "purpose": "Transfer to department",
  "argument": {
    "type": "object",
    "properties": {
      "destination": {
        "type": "string",
        "description": "Department: sales, support, accounting"
      }
    }
  }
}
```

## 🔐 Generate Secure JWT Secret

```bash
# Generate 32-byte random secret
openssl rand -base64 32

# Or using Node.js
node -e "console.log(require('crypto').randomBytes(32).toString('base64'))"
```

## 📊 Check System Status

```bash
# Run full diagnostics
node diagnose.js

# Quick database check
mysql -h localhost -u user -p -e "USE database; SHOW TABLES;"

# Check if server is running
curl http://localhost:3000/api/trpc/health || echo "Server not running"
```

## 🌐 Deployment Checklist

- [ ] Set `NODE_ENV=production`
- [ ] Configure SSL/HTTPS
- [ ] Update webhook URLs in SignalWire
- [ ] Set strong JWT_SECRET
- [ ] Configure storage (S3 or equivalent)
- [ ] Setup database backups
- [ ] Enable log rotation
- [ ] Configure rate limiting
- [ ] Test all call flows
- [ ] Verify SWAIG transfers work

## 🚨 Troubleshooting Quick Checks

| Issue | Check |
|-------|-------|
| Can't connect to DB | Verify DATABASE_URL format and credentials |
| SignalWire 401 | Wrong PROJECT_ID or API_TOKEN |
| SignalWire 404 | Wrong SPACE_URL |
| Webhooks not working | Ensure public URL and phone numbers configured |
| AI won't transfer | Check SWAIG function and SIP domain |
| No recordings | Verify storage credentials |
| SMS not sending | Check smsSummaryEnabled and notificationPhone |

## 📞 Support

- Full docs: `SETUP_GUIDE.md`
- System check: `node diagnose.js`
- Logs: Check server console output

## 🎓 Common Scenarios

### Adding a New Customer
1. Admin Portal → Customers → Add Customer
2. Set status to 'active'
3. Create SIP endpoints for them
4. Purchase phone numbers
5. Configure ring groups
6. Test call routing

### Setting Up Call Routing
1. Customer Portal → Call Routes
2. Create route with priority
3. Set match conditions (time, caller ID)
4. Choose destination (endpoint, ring group, AI)
5. Activate route

### Enabling AI Agent
1. Create route with destination type 'ai_agent'
2. Configure SWAIG function in SignalWire
3. Add Gather Input blocks in Call Flow Builder
4. Update SIP domain in code
5. Test transfers

## 💡 Pro Tips

- Use ngrok for webhook testing: `ngrok http 3000`
- Check logs: Look for `[Webhook]` and `[SWAIG]` prefixes
- Test one feature at a time
- Always verify credentials with `diagnose.js`
- Keep .env file secure and never commit it
- Document your custom modifications

---

**For detailed information, see [SETUP_GUIDE.md](SETUP_GUIDE.md)**
