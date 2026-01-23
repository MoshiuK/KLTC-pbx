# White Label PBX Manager

A comprehensive multi-tenant PBX management system built with SignalWire integration. Provides white-label phone system services with AI-powered call routing, intelligent IVR, call recording, and SMS notifications.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Node](https://img.shields.io/badge/node-%3E%3D18.0.0-brightgreen.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🚀 Features

### Core PBX Features
- **Multi-Tenant Architecture**: Isolated environments for each customer
- **SIP Endpoint Management**: Create and configure SIP endpoints with flexible call handlers
- **Phone Number Provisioning**: Search, purchase, and manage phone numbers through SignalWire
- **Ring Groups**: Multiple distribution strategies (simultaneous, sequential, round-robin, random)
- **Advanced Call Routing**: Pattern matching, time-based routing, caller ID filtering
- **Call Recording**: S3 storage with metadata and transcription support

### AI & Automation
- **AI-Powered IVR**: Natural language call routing with speech recognition
- **Intelligent Call Transfer**: AI agent with SWAIG function integration
- **Automatic Call Summaries**: LLM-generated summaries sent via SMS
- **Smart Routing Suggestions**: AI-powered call flow optimization

### White Label Capabilities
- **Custom Branding**: Logo upload, color schemes, company names
- **Isolated Customer Portals**: Each customer gets their own branded interface
- **Usage Analytics**: Track calls, minutes, and system utilization
- **Notification System**: Email and in-app alerts for missed calls, voicemails

## 📋 Prerequisites

- **Node.js** v18 or higher
- **MySQL** v8.0 or higher
- **SignalWire Account** with API credentials
- **pnpm** package manager (recommended)

## 🔧 Quick Start

### Option 1: Automated Installation

```bash
# Clone the repository
git clone <your-repo-url>
cd white-label-pbx

# Run automated installer
./install.sh
```

The installer will:
1. Check prerequisites
2. Create .env file from template
3. Install dependencies
4. Setup database (optional)
5. Run diagnostics (optional)

### Option 2: Manual Installation

```bash
# 1. Install dependencies
pnpm install

# 2. Create environment file
cp .env.example .env
# Edit .env with your credentials

# 3. Setup database
pnpm run db:push

# 4. Run diagnostics
node diagnose.js

# 5. Start development server
pnpm run dev
```

## 🔑 Environment Configuration

Key environment variables (see `.env.example` for complete list):

```env
# Database
DATABASE_URL=mysql://user:password@localhost:3306/database

# SignalWire
SIGNALWIRE_PROJECT_ID=your-project-id
SIGNALWIRE_API_TOKEN=your-api-token
SIGNALWIRE_SPACE_URL=yourspace.signalwire.com

# Security
JWT_SECRET=your-32-char-secret

# Storage (for recordings/logos)
BUILT_IN_FORGE_API_URL=https://storage-api.com
BUILT_IN_FORGE_API_KEY=your-storage-key
```

### Getting SignalWire Credentials

1. Sign up at [SignalWire](https://signalwire.com)
2. Go to **Settings** → **API**
3. Copy your Project ID, API Token, and Space URL

## 📖 Documentation

- **[Complete Setup Guide](SETUP_GUIDE.md)** - Detailed installation and configuration
- **[API Documentation](docs/API.md)** - REST and tRPC endpoint reference
- **[Architecture Overview](docs/ARCHITECTURE.md)** - System design and data flow
- **[Troubleshooting](SETUP_GUIDE.md#troubleshooting)** - Common issues and solutions

## 🏗️ Architecture

```
┌─────────────────────┐
│   React Frontend    │
│  (Vite + TypeScript)│
│   - Admin Portal    │
│   - Customer Portal │
└──────────┬──────────┘
           │ tRPC
┌──────────┴──────────┐
│   Express Server    │
│  - REST API         │
│  - SignalWire       │
│    Webhooks         │
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

## 🔍 System Health Check

Run diagnostics to verify your setup:

```bash
node diagnose.js
```

This will check:
- ✓ Environment variables
- ✓ Database connectivity
- ✓ SignalWire API access
- ✓ Webhook configuration
- ✓ Storage setup

## 🚦 Running the Application

### Development Mode
```bash
pnpm run dev
```
Server starts at `http://localhost:3000`

### Production Build
```bash
pnpm run build
pnpm run start
```

### Running Tests
```bash
pnpm test
pnpm test --coverage
```

## 📱 Key Features Setup

### 1. AI Agent Call Transfer

The AI Agent needs SWAIG function integration to transfer calls:

1. **Configure in SignalWire Call Flow Builder**:
   - Add AI Agent block
   - Add SWAIG function with webhook: `https://your-domain.com/api/webhooks/swaig-transfer`
   - Add Gather Input block after AI Agent with department options

2. **Update SIP Domain** in `server/webhooks.ts` (line 666):
   ```typescript
   const sipDomain = 'your-space.sip.signalwire.com';
   ```

3. **Test**: Call your number and say "I need sales" - it should transfer properly

See [SETUP_GUIDE.md](SETUP_GUIDE.md#1-ai-agent-setup-critical-for-call-transfers) for detailed instructions.

### 2. Call Summary SMS

Enable automatic call summaries sent via SMS:

```sql
UPDATE customers 
SET smsSummaryEnabled = 1, 
    notificationPhone = '+15551234567' 
WHERE id = 1;
```

Or configure through Admin Portal → Customer Settings.

### 3. Phone Number Webhooks

Each phone number needs webhook configuration:

1. Purchase number via Admin Portal or SignalWire
2. Set Voice URL: `https://your-domain.com/api/webhooks/voice`
3. Set Voice Method: `POST`
4. Set Status Callback: `https://your-domain.com/api/webhooks/call-status`

## 🗂️ Project Structure

```
white-label-pbx/
├── client/                 # React frontend
│   ├── src/
│   │   ├── pages/         # Admin & customer pages
│   │   ├── components/    # Reusable UI components
│   │   └── lib/           # Utilities & tRPC client
│   └── public/            # Static assets
├── server/                 # Express backend
│   ├── _core/             # Core server utilities
│   ├── routers.ts         # tRPC API routes
│   ├── webhooks.ts        # SignalWire webhook handlers
│   ├── signalwire.ts      # SignalWire API client
│   ├── ai-ivr.ts          # AI IVR logic
│   ├── call-summary.ts    # LLM call summaries
│   └── db.ts              # Database queries
├── drizzle/               # Database schema & migrations
├── shared/                # Shared types & constants
├── .env.example           # Environment template
├── SETUP_GUIDE.md         # Comprehensive setup guide
└── diagnose.js            # System diagnostic script
```

## 🔐 Security

- Never commit `.env` files
- Rotate API tokens regularly
- Use strong JWT secrets (32+ characters)
- Enable rate limiting on webhooks
- Validate webhook signatures from SignalWire
- Use HTTPS in production

## 📊 Database Schema

Key tables:
- `users` - Authentication & user accounts
- `customers` - Multi-tenant customer records
- `sipEndpoints` - SIP endpoints per customer
- `phoneNumbers` - Phone number inventory
- `ringGroups` - Call distribution groups
- `callRoutes` - Routing rules & patterns
- `callRecordings` - Recording metadata & S3 URLs
- `notifications` - Alert & notification system

## 🎯 API Endpoints

### Webhooks (SignalWire)
- `POST /api/webhooks/voice` - Main call routing
- `POST /api/webhooks/call-status` - Call status updates
- `POST /api/webhooks/swaig-transfer` - AI transfer function
- `GET /api/webhooks/swaig-functions` - SWAIG function list

### tRPC API
- Customer management (`/api/trpc/customers.*`)
- SIP endpoints (`/api/trpc/sipEndpoints.*`)
- Phone numbers (`/api/trpc/phoneNumbers.*`)
- Ring groups (`/api/trpc/ringGroups.*`)
- Call routes (`/api/trpc/callRoutes.*`)
- Usage stats (`/api/trpc/usageStats.*`)

## 🐛 Known Issues

- **Customer Status**: New customers default to 'pending' instead of 'active'
  - **Fix**: `UPDATE customers SET status = 'active' WHERE status = 'pending'`

- **AI Transfer**: Requires Call Flow Builder integration with Gather Input blocks
  - **Solution**: See [AI Agent Setup](SETUP_GUIDE.md#1-ai-agent-setup-critical-for-call-transfers)

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Issues**: [GitHub Issues](your-repo-url/issues)
- **Diagnostics**: Run `node diagnose.js` for system health check

## 🙏 Acknowledgments

- Built with [SignalWire](https://signalwire.com) API
- UI components from [shadcn/ui](https://ui.shadcn.com)
- Database ORM: [Drizzle](https://orm.drizzle.team)
- tRPC for type-safe APIs

---

**Made with ❤️ for Knox Media Group, Inc.**
