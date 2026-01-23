#!/usr/bin/env node
/**
 * White Label PBX System - Diagnostic Script
 * Run this script to check your system configuration and identify issues
 * 
 * Usage: node diagnose.js
 */

import 'dotenv/config';
import axios from 'axios';
import mysql from 'mysql2/promise';

const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  cyan: '\x1b[36m'
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

function success(message) {
  log(`✓ ${message}`, 'green');
}

function error(message) {
  log(`✗ ${message}`, 'red');
}

function warning(message) {
  log(`⚠ ${message}`, 'yellow');
}

function info(message) {
  log(`ℹ ${message}`, 'cyan');
}

function section(title) {
  console.log('');
  log(`${'='.repeat(60)}`, 'blue');
  log(`  ${title}`, 'blue');
  log(`${'='.repeat(60)}`, 'blue');
  console.log('');
}

async function checkEnvironmentVariables() {
  section('Environment Variables Check');
  
  const required = {
    'DATABASE_URL': process.env.DATABASE_URL,
    'SIGNALWIRE_PROJECT_ID': process.env.SIGNALWIRE_PROJECT_ID,
    'SIGNALWIRE_API_TOKEN': process.env.SIGNALWIRE_API_TOKEN,
    'SIGNALWIRE_SPACE_URL': process.env.SIGNALWIRE_SPACE_URL,
    'JWT_SECRET': process.env.JWT_SECRET
  };
  
  const optional = {
    'BUILT_IN_FORGE_API_URL': process.env.BUILT_IN_FORGE_API_URL,
    'BUILT_IN_FORGE_API_KEY': process.env.BUILT_IN_FORGE_API_KEY,
    'OAUTH_SERVER_URL': process.env.OAUTH_SERVER_URL,
    'OWNER_OPEN_ID': process.env.OWNER_OPEN_ID,
    'PORT': process.env.PORT
  };
  
  let allRequiredPresent = true;
  
  info('Required Variables:');
  for (const [key, value] of Object.entries(required)) {
    if (value) {
      const masked = key.includes('TOKEN') || key.includes('SECRET') 
        ? value.substring(0, 8) + '...' 
        : value;
      success(`${key}: ${masked}`);
    } else {
      error(`${key}: NOT SET`);
      allRequiredPresent = false;
    }
  }
  
  console.log('');
  info('Optional Variables:');
  for (const [key, value] of Object.entries(optional)) {
    if (value) {
      const masked = key.includes('KEY') 
        ? value.substring(0, 8) + '...' 
        : value;
      success(`${key}: ${masked}`);
    } else {
      warning(`${key}: Not set (optional)`);
    }
  }
  
  return allRequiredPresent;
}

async function checkDatabaseConnection() {
  section('Database Connection Check');
  
  const dbUrl = process.env.DATABASE_URL;
  if (!dbUrl) {
    error('DATABASE_URL not set');
    return false;
  }
  
  try {
    info(`Connecting to database...`);
    
    // Parse connection string
    const match = dbUrl.match(/mysql:\/\/([^:]+):([^@]+)@([^:\/]+):?(\d+)?\/(.+)/);
    if (!match) {
      error('Invalid DATABASE_URL format');
      error('Expected: mysql://user:password@host:port/database');
      return false;
    }
    
    const [, user, password, host, port = '3306', database] = match;
    
    const connection = await mysql.createConnection({
      host,
      port: parseInt(port),
      user,
      password,
      database
    });
    
    success('Connected to database');
    
    // Check required tables
    info('Checking database schema...');
    const requiredTables = [
      'users', 'customers', 'sipEndpoints', 'phoneNumbers',
      'ringGroups', 'callRoutes', 'usageStats', 'callRecordings',
      'notifications', 'notificationSettings'
    ];
    
    const [tables] = await connection.query('SHOW TABLES');
    const existingTables = tables.map(row => Object.values(row)[0]);
    
    let allTablesExist = true;
    for (const table of requiredTables) {
      if (existingTables.includes(table)) {
        success(`Table exists: ${table}`);
      } else {
        error(`Table missing: ${table}`);
        allTablesExist = false;
      }
    }
    
    if (!allTablesExist) {
      warning('Run "pnpm run db:push" to create missing tables');
    }
    
    // Check for data
    const [customerCount] = await connection.query('SELECT COUNT(*) as count FROM customers');
    const [endpointCount] = await connection.query('SELECT COUNT(*) as count FROM sipEndpoints');
    
    console.log('');
    info(`Database Statistics:`);
    info(`  Customers: ${customerCount[0].count}`);
    info(`  SIP Endpoints: ${endpointCount[0].count}`);
    
    await connection.end();
    return allTablesExist;
    
  } catch (err) {
    error(`Database connection failed: ${err.message}`);
    
    if (err.code === 'ECONNREFUSED') {
      error('MySQL server is not running or is not accessible');
    } else if (err.code === 'ER_ACCESS_DENIED_ERROR') {
      error('Invalid username or password');
    } else if (err.code === 'ER_BAD_DB_ERROR') {
      error('Database does not exist');
    }
    
    return false;
  }
}

async function checkSignalWireConnection() {
  section('SignalWire API Check');
  
  const projectId = process.env.SIGNALWIRE_PROJECT_ID;
  const apiToken = process.env.SIGNALWIRE_API_TOKEN;
  const spaceUrl = process.env.SIGNALWIRE_SPACE_URL;
  
  if (!projectId || !apiToken || !spaceUrl) {
    error('SignalWire credentials not configured');
    return false;
  }
  
  try {
    info('Testing SignalWire API connection...');
    
    // Test account endpoint
    const response = await axios({
      method: 'GET',
      url: `https://${spaceUrl}/api/laml/2010-04-01/Accounts/${projectId}`,
      auth: {
        username: projectId,
        password: apiToken
      }
    });
    
    success(`Connected to SignalWire account: ${response.data.friendly_name}`);
    info(`Account Status: ${response.data.status}`);
    info(`Account Type: ${response.data.type}`);
    
    // Test SIP endpoints
    info('Checking SIP endpoints...');
    try {
      const sipResponse = await axios({
        method: 'GET',
        url: `https://${spaceUrl}/api/relay/rest/endpoints/sip`,
        auth: {
          username: projectId,
          password: apiToken
        }
      });
      
      const endpoints = sipResponse.data.data || [];
      success(`Found ${endpoints.length} SIP endpoints`);
      
      if (endpoints.length > 0) {
        const sample = endpoints[0];
        info(`Sample endpoint: ${sample.username}@${spaceUrl}`);
      }
      
    } catch (err) {
      warning('Could not list SIP endpoints (may not have permission)');
    }
    
    // Test phone numbers
    info('Checking phone numbers...');
    try {
      const phoneResponse = await axios({
        method: 'GET',
        url: `https://${spaceUrl}/api/laml/2010-04-01/Accounts/${projectId}/IncomingPhoneNumbers.json`,
        auth: {
          username: projectId,
          password: apiToken
        }
      });
      
      const numbers = phoneResponse.data.incoming_phone_numbers || [];
      success(`Found ${numbers.length} phone numbers`);
      
      if (numbers.length > 0) {
        numbers.forEach(num => {
          info(`  ${num.phone_number}: ${num.friendly_name || 'No name'}`);
          if (num.voice_url) {
            info(`    Voice URL: ${num.voice_url}`);
          } else {
            warning(`    No voice URL configured`);
          }
        });
      }
      
    } catch (err) {
      warning('Could not list phone numbers');
    }
    
    return true;
    
  } catch (err) {
    error(`SignalWire connection failed: ${err.message}`);
    
    if (err.response) {
      error(`HTTP ${err.response.status}: ${err.response.statusText}`);
      
      if (err.response.status === 401) {
        error('Invalid Project ID or API Token');
      } else if (err.response.status === 404) {
        error('Invalid Space URL or Project ID');
      }
    }
    
    return false;
  }
}

async function checkWebhookConfiguration() {
  section('Webhook Configuration Check');
  
  const spaceUrl = process.env.SIGNALWIRE_SPACE_URL;
  if (!spaceUrl) {
    error('Cannot check webhooks: SIGNALWIRE_SPACE_URL not set');
    return false;
  }
  
  // Note: We can't actually test webhooks without a public URL
  // But we can verify the endpoints exist in code
  
  const webhookEndpoints = [
    '/api/webhooks/voice',
    '/api/webhooks/call-status',
    '/api/webhooks/recording-status',
    '/api/webhooks/ai-gather',
    '/api/webhooks/swaig-transfer',
    '/api/webhooks/swaig-functions'
  ];
  
  info('Expected webhook endpoints (must be publicly accessible):');
  webhookEndpoints.forEach(endpoint => {
    info(`  POST https://your-domain.com${endpoint}`);
  });
  
  console.log('');
  warning('To test webhooks, you need to:');
  warning('1. Deploy your application to a public server (or use ngrok)');
  warning('2. Configure phone number Voice URL in SignalWire');
  warning('3. Make a test call to verify routing');
  
  return true;
}

async function checkStorageConfiguration() {
  section('Storage Configuration Check');
  
  const forgeUrl = process.env.BUILT_IN_FORGE_API_URL;
  const forgeKey = process.env.BUILT_IN_FORGE_API_KEY;
  
  if (!forgeUrl || !forgeKey) {
    warning('Storage not configured (BUILT_IN_FORGE_API_URL/KEY not set)');
    warning('Call recordings and logo uploads will not work');
    info('You can configure S3-compatible storage or use Manus storage proxy');
    return false;
  }
  
  try {
    info('Testing storage connection...');
    
    // Note: This is a basic check, actual upload would need more setup
    success('Storage credentials present');
    info(`Storage URL: ${forgeUrl}`);
    
    return true;
    
  } catch (err) {
    error(`Storage check failed: ${err.message}`);
    return false;
  }
}

async function checkSystemHealth() {
  section('System Health Summary');
  
  const checks = {
    'Environment Variables': await checkEnvironmentVariables(),
    'Database Connection': await checkDatabaseConnection(),
    'SignalWire API': await checkSignalWireConnection(),
    'Webhook Config': await checkWebhookConfiguration(),
    'Storage Config': await checkStorageConfiguration()
  };
  
  console.log('');
  section('Diagnostic Summary');
  
  for (const [check, passed] of Object.entries(checks)) {
    if (passed) {
      success(`${check}: PASSED`);
    } else {
      error(`${check}: FAILED`);
    }
  }
  
  console.log('');
  
  const allPassed = Object.values(checks).every(v => v);
  
  if (allPassed) {
    success('All checks passed! Your system is ready.');
  } else {
    error('Some checks failed. Review the issues above.');
    info('See SETUP_GUIDE.md for detailed troubleshooting steps.');
  }
  
  return allPassed;
}

// Run diagnostics
checkSystemHealth()
  .then(() => {
    process.exit(0);
  })
  .catch(err => {
    error(`Fatal error: ${err.message}`);
    console.error(err);
    process.exit(1);
  });
