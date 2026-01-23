# White Label PBX System - Setup & Troubleshooting Guide

## System Overview

This is a multi-tenant PBX management system that integrates with SignalWire to provide white-label phone system services. It features:

- **Admin Portal**: Manage multiple customers and their PBX configurations
- **Customer Portal**: Self-service portal for each customer to manage their phone system
- **AI-Powered IVR**: Intelligent call routing with natural language processing
- **Call Recording & Transcription**: Automatic call summaries via SMS
- **Ring Groups**: Sophisticated call distribution strategies
- **Time-based Routing**: Route calls based on time and caller ID patterns

## Architecture

```
┌─────────────────────┐
│   React Frontend    │
│  (Vite + TypeScript)│
└──────────┬──────────┘
           │ tRPC
┌──────────┴──────────┐
│   Express Server    │
│  - REST API         │
│  - Webhooks         │
│  - tRPC Routes      │
└──────────┬──────────┘
           │
     ┌─────┴─────┬────────────┐
     │           │            │
┌────┴────┐ ┌───┴────┐  ┌───┴─────┐
│  MySQL  │ │SignalWire│ │ Storage │
│Database │ │   API    │ │  (S3)   │
└─────────┘ └──────────┘ └─────────┘
```

## Prerequisites

1. **Node.js**: v18+ (pnpm package manager)
2. **MySQL Database**: v8.0+
3. **SignalWire Account**: With API credentials
4. **Storage**: S3-compatible storage or Manus storage proxy

## Environment Variables Setup

Create a `.env` file in the root directory with the following variables:

```env
# ============================================
# Core Application Settings
# ============================================
NODE_ENV=development
PORT=3000
VITE_APP_ID=white-label-pbx

# ============================================
# Database Configuration
# ============================================
DATABASE_URL=mysql://username:password@localhost:3306/pbx_database

# ============================================
# Authentication & Security
# ============================================
JWT_SECRET=your-secret-key-here-minimum-32-characters
OAUTH_SERVER_URL=https://your-oauth-server.com
OWNER_OPEN_ID=admin-open-id

# ============================================
# SignalWire API Credentials
# ============================================
SIGNALWIRE_PROJECT_ID=your-project-id
SIGNALWIRE_API_TOKEN=your-api-token
SIGNALWIRE_SPACE_URL=your-space.signalwire.com

# ============================================
# Storage Configuration (for recordings/logos)
# ============================================
BUILT_IN_FORGE_API_URL=https://your-storage-api.com
BUILT_IN_FORGE_API_KEY=your-storage-api-key

# Alternative: Direct S3 Configuration (if not using proxy)
# AWS_ACCESS_KEY_ID=your-access-key
# AWS_SECRET_ACCESS_KEY=your-secret-key
# AWS_REGION=us-east-1
# S3_BUCKET=your-bucket-name
```

### Getting SignalWire Credentials

1. Log into your SignalWire account at https://signalwire.com
2. Navigate to **Settings** → **API**
3. Copy these values:
   - **Project ID**: Found in your project settings
   - **API Token**: Generate a new token or use existing
   - **Space URL**: Your space name (e.g., `yourspace.signalwire.com`)

## Installation Steps

### 1. Install Dependencies

```bash
# Install pnpm if not already installed
npm install -g pnpm

# Install project dependencies
pnpm install
```

### 2. Setup Database

```bash
# Generate database schema
pnpm run db:push

# This will:
# - Create all necessary tables
# - Run migrations
# - Set up indexes and relationships
```

### 3. Verify SignalWire Connection

Create a test script to verify your credentials:

```bash
# Test SignalWire connection
node -e "
const axios = require('axios');
const PROJECT_ID = 'your-project-id';
const API_TOKEN = 'your-token';
const SPACE_URL = 'yourspace.signalwire.com';

axios({
  url: \`https://\${SPACE_URL}/api/laml/2010-04-01/Accounts/\${PROJECT_ID}\`,
  auth: { username: PROJECT_ID, password: API_TOKEN }
}).then(r => console.log('✓ SignalWire Connected:', r.data.friendly_name))
  .catch(e => console.error('✗ Connection Failed:', e.message));
"
```

### 4. Start Development Server

```bash
# Start the development server
pnpm run dev

# Server will start on http://localhost:3000
```

### 5. Build for Production

```bash
# Build frontend and backend
pnpm run build

# Start production server
pnpm run start
```

## Key Features Configuration

### 1. AI Agent Setup (CRITICAL FOR CALL TRANSFERS)

The AI Agent needs proper webhook configuration to handle transfers:

#### Step 1: Create SWAIG Function in SignalWire

1. Go to **Call Flow Builder** in SignalWire
2. Add an **AI Agent** block to your call flow
3. Configure the AI Agent with your prompt
4. Under **SWAIG Functions**, add:

```json
{
  "function": "transfer",
  "web_hook_url": "https://your-domain.com/api/webhooks/swaig-transfer",
  "purpose": "Transfer the call to a specific department",
  "argument": {
    "type": "object",
    "properties": {
      "destination": {
        "type": "string",
        "description": "Department name: sales, support, accounting, or billing"
      }
    },
    "required": ["destination"]
  }
}
```

#### Step 2: Configure Call Flow Builder Integration

In your Call Flow Builder after the AI Agent:

1. Add a **Gather Input** block
2. Configure options:
   - **Press 1**: Sales → Connect to SIP endpoint `knox_101`
   - **Press 2**: Accounting → Connect to SIP endpoint `knox_102`
   - **Press 3**: Support → Connect to SIP endpoint `knox_103`

The SWAIG webhook will instruct the AI Agent to stop and let the Call Flow Builder handle the actual transfer using the Gather Input logic.

#### Step 3: Update SIP Domain in Code

In `server/webhooks.ts` line 666, update the SIP domain to match your SignalWire space:

```typescript
const sipDomain = 'your-space-name.sip.signalwire.com';
```

Find this by going to your SignalWire dashboard → **SIP Endpoints** → any endpoint → copy the domain after the `@` symbol.

### 2. Call Summary SMS Feature

Automatically send AI-generated call summaries via SMS:

1. Enable in customer settings (database field: `smsSummaryEnabled`)
2. Set notification phone number
3. Summaries are sent when calls complete with recordings

Configure in the UI:
- Admin Portal → Customer Details → Settings → "Enable SMS Summaries"
- Or directly in database: `UPDATE customers SET smsSummaryEnabled = 1, notificationPhone = '+1234567890' WHERE id = ?`

### 3. Phone Number Configuration

Each phone number needs a webhook URL configured in SignalWire:

1. Purchase number via the admin UI or SignalWire dashboard
2. Set Voice URL to: `https://your-domain.com/api/webhooks/voice`
3. Set Voice Method to: `POST`
4. (Optional) Set Status Callback URL to: `https://your-domain.com/api/webhooks/call-status`

### 4. Ring Groups Setup

Ring groups support multiple strategies:

- **Simultaneous**: All endpoints ring at once
- **Sequential**: Ring one after another
- **Round Robin**: Distribute calls evenly
- **Random**: Random selection each time

Configure through the Customer Portal → Ring Groups

## Troubleshooting

### Issue 1: Customer Status Stuck on "Pending"

**Symptom**: New customers show `status: pending` instead of `active`

**Cause**: Database default is set to "pending" and no activation logic changes it

**Fix**:
```sql
-- Update existing customers
UPDATE customers SET status = 'active' WHERE status = 'pending';

-- Or fix in the customer creation code (server/routers.ts)
-- Add status: 'active' when creating new customer
```

### Issue 2: AI Agent Not Transferring Calls

**Symptom**: AI Agent acknowledges transfer request but doesn't transfer

**Root Causes**:
1. SWAIG function not properly configured
2. Wrong SWML response format
3. SIP domain mismatch
4. Missing Call Flow Builder integration

**Solution**:
1. Verify SWAIG webhook is accessible: `curl https://your-domain.com/api/webhooks/swaig-functions`
2. Check logs for SWAIG requests: `[SWAIG Transfer]` prefix
3. Ensure SIP domain matches your space (line 666 in webhooks.ts)
4. Use the Call Flow Builder approach with Gather Input blocks

**Working Implementation**:
The AI Agent should:
1. Receive transfer request from caller
2. Call SWAIG webhook with department name
3. SWAIG returns `{ back_to_back_functions: false, stop: true }`
4. AI Agent stops and Call Flow Builder takes over
5. Call Flow Builder executes the transfer based on Gather Input settings

### Issue 3: Database Connection Errors

**Symptom**: `Error: connect ECONNREFUSED` or MySQL connection timeouts

**Checks**:
```bash
# Test MySQL connection
mysql -h localhost -u username -p database_name

# Verify DATABASE_URL format
# Correct: mysql://user:pass@host:3306/database
# Wrong: mysql://user:pass@host/database (missing port)
```

### Issue 4: SignalWire API Errors

**Common Errors**:

**401 Unauthorized**:
- Wrong PROJECT_ID or API_TOKEN
- Check credentials in SignalWire dashboard

**404 Not Found**:
- Wrong SPACE_URL
- Missing `.signalwire.com` suffix

**403 Forbidden**:
- API token doesn't have required permissions
- Generate new token with full permissions

### Issue 5: Webhooks Not Receiving Calls

**Symptoms**: Calls don't route properly, no webhook logs

**Debug Steps**:
1. Check phone number Voice URL is set correctly in SignalWire
2. Verify your server is publicly accessible (use ngrok for testing)
3. Check webhook logs: `grep "Webhook" your-log-file`
4. Test webhook manually:

```bash
curl -X POST https://your-domain.com/api/webhooks/voice \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "CallSid=CA123&From=+15551234567&To=+15557654321&CallStatus=ringing"
```

### Issue 6: Recording Transcription Not Working

**Checks**:
1. Verify S3/storage credentials are correct
2. Check call recording is enabled in phone number settings
3. Review webhook logs for recording completion events
4. Ensure storage bucket has proper permissions

## Database Schema Quick Reference

### Key Tables

- **users**: Authentication and user accounts
- **customers**: Multi-tenant customer records
- **sipEndpoints**: SIP endpoints per customer
- **phoneNumbers**: Purchased phone numbers
- **ringGroups**: Call distribution groups
- **callRoutes**: Routing rules and patterns
- **callRecordings**: Recording metadata and S3 URLs
- **notifications**: In-app and email notifications

### Important Relationships

```
customers (1) ─── (N) sipEndpoints
customers (1) ─── (N) phoneNumbers
customers (1) ─── (N) ringGroups
customers (1) ─── (N) callRoutes
ringGroups (1) ─── (N) sipEndpoints (via JSON array)
phoneNumbers (1) ─── (1) sipEndpoints (assigned)
phoneNumbers (1) ─── (1) ringGroups (assigned)
```

## API Endpoints

### Webhooks (SignalWire Callbacks)

- `POST /api/webhooks/voice` - Main call routing webhook
- `POST /api/webhooks/call-status` - Call status updates
- `POST /api/webhooks/recording-status` - Recording completion
- `POST /api/webhooks/ai-gather` - AI IVR speech processing
- `POST /api/webhooks/ai-fallback` - AI IVR fallback handling
- `POST /api/webhooks/swaig-transfer` - AI Agent transfer function
- `GET /api/webhooks/swaig-functions` - Available SWAIG functions list

### tRPC API Routes

All tRPC routes are available at `/api/trpc/*`:

- Customer management
- SIP endpoint CRUD
- Phone number management
- Ring group configuration
- Call route management
- Usage statistics
- Notification settings

## Performance Optimization

### Production Checklist

- [ ] Set `NODE_ENV=production`
- [ ] Enable database connection pooling
- [ ] Configure CDN for static assets
- [ ] Enable gzip compression
- [ ] Set up log rotation
- [ ] Configure rate limiting on webhooks
- [ ] Enable SSL/TLS (required for webhooks)
- [ ] Set up monitoring (New Relic, Datadog, etc.)

### Scaling Considerations

1. **Database**: Use read replicas for reporting queries
2. **Webhooks**: Consider queue system for high call volume
3. **Storage**: Use CDN for recording playback
4. **API**: Implement caching for frequently accessed data

## Security Best Practices

1. **Never commit `.env` files** to version control
2. **Rotate API tokens** regularly
3. **Use strong JWT_SECRET** (minimum 32 characters)
4. **Implement rate limiting** on webhook endpoints
5. **Validate webhook signatures** from SignalWire
6. **Sanitize user inputs** in call routing patterns
7. **Use HTTPS** for all production deployments

## Support & Resources

### SignalWire Documentation
- API Reference: https://developer.signalwire.com/apis/docs/
- Call Flow Builder: https://signalwire.com/resources/call-flow-builder
- SWAIG Functions: https://docs.signalwire.com/ai/prompt-engineering

### Project Resources
- GitHub Issues: (add your repo URL)
- Documentation: (add your docs URL)

## Common Development Tasks

### Add New SIP Endpoint
```typescript
await db.createSipEndpoint({
  customerId: 1,
  username: 'user123',
  password: 'secure-password',
  displayName: 'John Doe',
  extensionNumber: '101',
  callHandler: 'laml_webhooks',
  status: 'active'
});
```

### Create Ring Group
```typescript
await db.createRingGroup({
  customerId: 1,
  name: 'Sales Team',
  extensionNumber: '200',
  strategy: 'simultaneous',
  ringTimeout: 30,
  memberEndpointIds: [1, 2, 3],
  status: 'active'
});
```

### Add Call Route
```typescript
await db.createCallRoute({
  customerId: 1,
  name: 'Business Hours',
  matchType: 'time_based',
  timeStart: '09:00',
  timeEnd: '17:00',
  daysOfWeek: [1, 2, 3, 4, 5], // Mon-Fri
  destinationType: 'ring_group',
  destinationId: 1,
  status: 'active',
  priority: 10
});
```

## Testing

### Run Tests
```bash
# Run all tests
pnpm test

# Run specific test file
pnpm test server/signalwire.test.ts

# Run with coverage
pnpm test --coverage
```

### Manual Testing Scenarios

1. **Test Call Routing**:
   - Call your purchased number
   - Verify it routes to correct endpoint
   - Test ring group behavior

2. **Test AI Agent**:
   - Call AI-enabled number
   - Say "I need sales"
   - Verify transfer completes

3. **Test Time-based Routing**:
   - Create business hours route
   - Create after-hours route
   - Test at different times

## Deployment

### Option 1: Traditional VPS (Ubuntu)

```bash
# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install pnpm
npm install -g pnpm

# Clone and setup
git clone your-repo
cd white-label-pbx
pnpm install
pnpm run build

# Use PM2 for process management
npm install -g pm2
pm2 start dist/index.js --name pbx-system
pm2 startup
pm2 save
```

### Option 2: Docker

```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json pnpm-lock.yaml ./
RUN npm install -g pnpm && pnpm install
COPY . .
RUN pnpm run build
EXPOSE 3000
CMD ["node", "dist/index.js"]
```

### Option 3: Cloud Platforms

- **Heroku**: Use `heroku.yml` or buildpack
- **AWS**: EC2, ECS, or Lambda with API Gateway
- **Google Cloud**: Cloud Run or App Engine
- **DigitalOcean**: App Platform or Droplet

## Next Steps After Setup

1. **Create Admin User**: Set `OWNER_OPEN_ID` in .env
2. **Add First Customer**: Through admin portal
3. **Configure SignalWire Subproject**: For customer isolation
4. **Purchase Phone Numbers**: Assign to customer
5. **Create SIP Endpoints**: Set up extensions
6. **Test Call Flow**: Make test calls
7. **Configure Branding**: Upload logo, set colors
8. **Enable Features**: Recording, transcription, SMS summaries

## Known Limitations

1. **Sequential Ring Groups**: Limited to 10 endpoints due to LaML complexity
2. **Call Recording Storage**: Requires external S3-compatible storage
3. **AI Agent Transfer**: Requires Call Flow Builder integration
4. **International Numbers**: Limited by SignalWire availability
5. **Concurrent Calls**: Limited by SignalWire account plan

## Changelog & Versioning

Current Version: **1.0.0**

### Completed Features
- ✅ Multi-tenant architecture
- ✅ SIP endpoint management
- ✅ Phone number provisioning
- ✅ Ring groups with multiple strategies
- ✅ Time-based call routing
- ✅ AI-powered IVR
- ✅ Call recordings with S3 storage
- ✅ SMS call summaries
- ✅ White-label branding
- ✅ Usage statistics and reporting

### Known Issues
- [ ] Customer status defaults to 'pending' instead of 'active'
- [x] AI Agent transfer requires Call Flow Builder integration (documented)

---

**Need Help?** Open an issue on GitHub or contact support.
