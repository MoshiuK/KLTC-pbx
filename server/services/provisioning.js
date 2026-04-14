/**
 * SIP Phone Auto-Provisioning Service
 *
 * Generates configuration files for SIP phones so they automatically
 * register with SignalWire when plugged into the network.
 *
 * Supported manufacturers:
 * - Yealink (T2x, T3x, T4x, T5x series)
 * - Polycom (VVX series)
 * - Grandstream (GXP/GRP series)
 * - Cisco (SPA + 7800/8800 series)
 * - Fanvil (X/V series)
 * - Snom (D series)
 * - Obihai/Poly (OBi series)
 * - Panasonic (KX-UT/KX-HDV series)
 * - Avaya (J100/9600/1600 series)
 * - Sangoma/Digium (S/D series)
 * - Mitel (6800/6900 series)
 * - Alcatel-Lucent (8000 series)
 * - Generic SIP (any standards-compliant phone)
 *
 * How it works:
 * 1. Admin creates extension -> SIP credentials generated
 * 2. Admin selects phone model -> provisioning config generated
 * 3. Phone is pointed to provisioning URL (via DHCP Option 66 or manual)
 * 4. Phone downloads its config and auto-registers
 *
 * Provisioning URL format:
 * {BASE_URL}/api/provisioning/{mac_address}/{filename}
 */

const prisma = require('../utils/prisma');
const logger = require('../utils/logger');

/**
 * Generate provisioning config for a specific phone/extension.
 */
async function generateConfig(extension, tenant, phoneModel) {
  const sipDomain = tenant.swSpaceUrl || process.env.SIGNALWIRE_SPACE_URL || 'example.signalwire.com';
  const sipProxy = sipDomain;
  const sipPort = '5060';
  const sipTransport = 'UDP';

  const params = {
    displayName: extension.name,
    sipUsername: extension.sipUsername,
    sipPassword: extension.sipPassword,
    sipDomain,
    sipProxy,
    sipPort,
    sipTransport,
    extensionNumber: extension.number,
    callerIdName: extension.callerIdName || extension.name,
    callerIdNumber: extension.callerIdNumber || '',
    tenantName: tenant.brandName || tenant.name,
  };

  const brand = (phoneModel || 'generic').toLowerCase();
  if (brand.startsWith('yealink')) return generateYealinkConfig(params, phoneModel);
  if (brand.startsWith('polycom') && !brand.includes('obi')) return generatePolycomConfig(params, phoneModel);
  if (brand.startsWith('grandstream')) return generateGrandstreamConfig(params, phoneModel);
  if (brand.startsWith('cisco')) return generateCiscoConfig(params, phoneModel);
  if (brand.startsWith('fanvil')) return generateFanvilConfig(params, phoneModel);
  if (brand.startsWith('snom')) return generateSnomConfig(params, phoneModel);
  if (brand.startsWith('obihai') || brand.startsWith('obi')) return generateObihaiConfig(params, phoneModel);
  if (brand.startsWith('panasonic')) return generatePanasonicConfig(params, phoneModel);
  if (brand.startsWith('avaya')) return generateAvayaConfig(params, phoneModel);
  if (brand.startsWith('sangoma') || brand.startsWith('digium')) return generateSangomaConfig(params, phoneModel);
  if (brand.startsWith('mitel')) return generateMitelConfig(params, phoneModel);
  if (brand.startsWith('alcatel')) return generateAlcatelConfig(params, phoneModel);
  return generateGenericConfig(params);
}

// ============================================================================
// YEALINK
// ============================================================================
function generateYealinkConfig(p, model) {
  return {
    filename: 'y000000000000.cfg',
    contentType: 'text/plain',
    content: [
      '#!version:1.0.0.1',
      '## Yealink Auto-Provisioning -- ' + p.tenantName,
      '## Extension: ' + p.extensionNumber + ' -- ' + p.displayName,
      'account.1.enable = 1',
      'account.1.label = ' + p.extensionNumber,
      'account.1.display_name = ' + p.displayName,
      'account.1.auth_name = ' + p.sipUsername,
      'account.1.user_name = ' + p.sipUsername,
      'account.1.password = ' + p.sipPassword,
      'account.1.sip_server.1.address = ' + p.sipDomain,
      'account.1.sip_server.1.port = ' + p.sipPort,
      'account.1.sip_server.1.transport_type = 0',
      'account.1.sip_server.1.register_on_enable = 1',
      'account.1.sip_server.1.expires = 3600',
      'account.1.outbound_proxy_enable = 0',
      'account.1.nat.udp_update_enable = 1',
      'account.1.nat.udp_update_time = 30',
      'account.1.cid_source = 2',
      'account.1.caller_id_name = ' + p.callerIdName,
      'account.1.codec.1.enable = 1',
      'account.1.codec.1.payload_type = PCMU',
      'account.1.codec.2.enable = 1',
      'account.1.codec.2.payload_type = PCMA',
      'account.1.codec.3.enable = 1',
      'account.1.codec.3.payload_type = G729',
      'account.1.codec.4.enable = 1',
      'account.1.codec.4.payload_type = G722',
      'phone_setting.ring_type = Ring1.wav',
      'phone_setting.auto_answer = 0',
      'voice_mail.number.1 = *97',
      'network.dhcp_enable = 1',
      'local_time.ntp_server1 = pool.ntp.org',
      'local_time.time_zone = -5',
      'auto_provision.mode = 0',
    ].join('
'),
  };
}

// ============================================================================
// POLYCOM
// ============================================================================
function generatePolycomConfig(p, model) {
  return {
    filename: '000000000000.cfg',
    contentType: 'application/xml',
    content: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
      + '<!-- Polycom Auto-Provisioning -- ' + p.tenantName + ' -->
'
      + '<polycomConfig>
'
      + '  <reg reg.1.displayName="' + p.displayName + '" reg.1.label="' + p.extensionNumber + '"
'
      + '    reg.1.address="' + p.sipUsername + '" reg.1.auth.userId="' + p.sipUsername + '"
'
      + '    reg.1.auth.password="' + p.sipPassword + '"
'
      + '    reg.1.server.1.address="' + p.sipDomain + '" reg.1.server.1.port="' + p.sipPort + '"
'
      + '    reg.1.server.1.transport="UDPonly" reg.1.server.1.register="1"
'
      + '    reg.1.server.1.expires="3600" />
'
      + '  <voIpProt voIpProt.server.1.address="' + p.sipProxy + '" voIpProt.server.1.port="' + p.sipPort + '" />
'
      + '  <voice voice.codecPref.G711Mu="1" voice.codecPref.G711A="2" voice.codecPref.G729AB="3" voice.codecPref.G722="4" />
'
      + '  <msg msg.mwi.1.subscribe="' + p.sipUsername + '" msg.mwi.1.callBack="*97" />
'
      + '  <nat nat.keepalive.interval="30" />
'
      + '  <tcpIpApp tcpIpApp.sntp.address="pool.ntp.org" tcpIpApp.sntp.gmtOffset="-18000" />
'
      + '</polycomConfig>',
  };
}

// ============================================================================
// GRANDSTREAM
// ============================================================================
function generateGrandstreamConfig(p, model) {
  return {
    filename: 'cfg000000000000.xml',
    contentType: 'application/xml',
    content: '<?xml version="1.0" encoding="UTF-8"?>
'
      + '<!-- Grandstream Auto-Provisioning -- ' + p.tenantName + ' -->
'
      + '<gs_provision version="1">
'
      + '  <config name="P271" value="1"/>
'
      + '  <config name="P270" value="' + p.displayName + '"/>
'
      + '  <config name="P47"  value="' + p.sipDomain + '"/>
'
      + '  <config name="P2312" value="' + p.sipPort + '"/>
'
      + '  <config name="P35"  value="' + p.sipUsername + '"/>
'
      + '  <config name="P36"  value="' + p.sipUsername + '"/>
'
      + '  <config name="P34"  value="' + p.sipPassword + '"/>
'
      + '  <config name="P3"   value="' + p.displayName + '"/>
'
      + '  <config name="P33"  value="' + p.callerIdName + '"/>
'
      + '  <config name="P57" value="0"/>
'
      + '  <config name="P58" value="8"/>
'
      + '  <config name="P59" value="18"/>
'
      + '  <config name="P52" value="1"/>
'
      + '  <config name="P72" value="30"/>
'
      + '  <config name="P30" value="pool.ntp.org"/>
'
      + '  <config name="P64" value="-5"/>
'
      + '  <config name="P8" value="0"/>
'
      + '</gs_provision>',
  };
}

// ============================================================================
// CISCO
// ============================================================================
function generateCiscoConfig(p, model) {
  return {
    filename: 'spa000000000000.xml',
    contentType: 'application/xml',
    content: '<flat-profile>
'
      + '  <!-- Cisco SPA Auto-Provisioning -- ' + p.tenantName + ' -->
'
      + '  <Line_Enable_1_ ua="na">Yes</Line_Enable_1_>
'
      + '  <Display_Name_1_ ua="na">' + p.displayName + '</Display_Name_1_>
'
      + '  <User_ID_1_ ua="na">' + p.sipUsername + '</User_ID_1_>
'
      + '  <Auth_ID_1_ ua="na">' + p.sipUsername + '</Auth_ID_1_>
'
      + '  <Password_1_ ua="na">' + p.sipPassword + '</Password_1_>
'
      + '  <Proxy_1_ ua="na">' + p.sipDomain + '</Proxy_1_>
'
      + '  <Register_1_ ua="na">Yes</Register_1_>
'
      + '  <Register_Expires_1_ ua="na">3600</Register_Expires_1_>
'
      + '  <SIP_Port_1_ ua="na">' + p.sipPort + '</SIP_Port_1_>
'
      + '  <SIP_Transport_1_ ua="na">UDP</SIP_Transport_1_>
'
      + '  <Caller_ID_Name_1_ ua="na">' + p.callerIdName + '</Caller_ID_Name_1_>
'
      + '  <Preferred_Codec_1_ ua="na">G711u</Preferred_Codec_1_>
'
      + '  <G729a_Enable_1_ ua="na">Yes</G729a_Enable_1_>
'
      + '  <G722_Enable_1_ ua="na">Yes</G722_Enable_1_>
'
      + '  <NAT_Keep_Alive_Enable_1_ ua="na">Yes</NAT_Keep_Alive_Enable_1_>
'
      + '  <Voice_Mail_Number_1_ ua="na">*97</Voice_Mail_Number_1_>
'
      + '  <Primary_NTP_Server ua="na">pool.ntp.org</Primary_NTP_Server>
'
      + '  <Connection_Type ua="na">DHCP</Connection_Type>
'
      + '</flat-profile>',
  };
}

// ============================================================================
// FANVIL
// ============================================================================
function generateFanvilConfig(p, model) {
  return {
    filename: 'fanvil.cfg',
    contentType: 'text/plain',
    content: [
      '## Fanvil Auto-Provisioning -- ' + p.tenantName,
      '## Extension: ' + p.extensionNumber + ' -- ' + p.displayName,
      '<<VOIP CONFIG FILE>>Version:2.0002',
      '<SIP1/AccountEnable>1',
      '<SIP1/DisplayName>' + p.displayName,
      '<SIP1/Label>' + p.extensionNumber,
      '<SIP1/UserName>' + p.sipUsername,
      '<SIP1/AuthName>' + p.sipUsername,
      '<SIP1/AuthPassword>' + p.sipPassword,
      '<SIP1/SIPServerAddress>' + p.sipDomain,
      '<SIP1/SIPServerPort>' + p.sipPort,
      '<SIP1/TransportType>0',
      '<SIP1/RegisterExpires>3600',
      '<SIP1/CallerIDName>' + p.callerIdName,
      '<SIP1/CodecOrder>PCMU,PCMA,G729,G722',
      '<SIP1/UDPKeepAliveMsg>1',
      '<SIP1/UDPKeepAliveInterval>30',
      '<SIP1/VoiceMailNumber>*97',
      '<LAN/DHCPEnable>1',
      '<NTP/Enable>1',
      '<NTP/Server1>pool.ntp.org',
      '<NTP/TimeZone>-5',
    ].join('
'),
  };
}

// ============================================================================
// SNOM
// ============================================================================
function generateSnomConfig(p, model) {
  return {
    filename: 'snom.xml',
    contentType: 'application/xml',
    content: '<?xml version="1.0" encoding="utf-8"?>
'
      + '<!-- Snom Auto-Provisioning -- ' + p.tenantName + ' -->
'
      + '<settings>
'
      + '  <phone-setting e="2">
'
      + '    <active_line idx="1" perm="">on</active_line>
'
      + '    <user_realname idx="1" perm="">' + p.displayName + '</user_realname>
'
      + '    <user_name idx="1" perm="">' + p.sipUsername + '</user_name>
'
      + '    <user_host idx="1" perm="">' + p.sipDomain + '</user_host>
'
      + '    <user_pass idx="1" perm="">' + p.sipPassword + '</user_pass>
'
      + '    <user_expiry idx="1" perm="">3600</user_expiry>
'
      + '    <user_idle_text idx="1" perm="">' + p.extensionNumber + ' - ' + p.displayName + '</user_idle_text>
'
      + '    <user_cid_name idx="1" perm="">' + p.callerIdName + '</user_cid_name>
'
      + '    <user_mailbox idx="1" perm="">*97</user_mailbox>
'
      + '    <codec1_name idx="1" perm="">9</codec1_name>
'
      + '    <codec2_name idx="1" perm="">0</codec2_name>
'
      + '    <codec3_name idx="1" perm="">8</codec3_name>
'
      + '    <codec4_name idx="1" perm="">18</codec4_name>
'
      + '    <dhcp perm="">on</dhcp>
'
      + '    <ntp_server perm="">pool.ntp.org</ntp_server>
'
      + '    <timezone perm="">USA-5</timezone>
'
      + '    <stun_server perm="">stun.signalwire.com</stun_server>
'
      + '    <nat_traversal idx="1" perm="">on</nat_traversal>
'
      + '    <keepalive idx="1" perm="">30</keepalive>
'
      + '  </phone-setting>
'
      + '</settings>',
  };
}

// ============================================================================
// OBIHAI / Poly OBi
// ============================================================================
function generateObihaiConfig(p, model) {
  return {
    filename: 'obihai.xml',
    contentType: 'application/xml',
    content: '<?xml version="1.0" encoding="utf-8"?>
'
      + '<!-- OBihai/Poly OBi Auto-Provisioning -- ' + p.tenantName + ' -->
'
      + '<ParameterList>
'
      + '  <SP1_Enable>true</SP1_Enable>
'
      + '  <SP1_AuthUserName>' + p.sipUsername + '</SP1_AuthUserName>
'
      + '  <SP1_AuthPassword>' + p.sipPassword + '</SP1_AuthPassword>
'
      + '  <SP1_URI>' + p.sipUsername + '@' + p.sipDomain + '</SP1_URI>
'
      + '  <SP1_CallingLineIdName>' + p.callerIdName + '</SP1_CallingLineIdName>
'
      + '  <SP1_CallingLineIdNumber>' + (p.callerIdNumber || p.extensionNumber) + '</SP1_CallingLineIdNumber>
'
      + '  <SP1_ProxyServer>' + p.sipDomain + '</SP1_ProxyServer>
'
      + '  <SP1_ProxyServerPort>' + p.sipPort + '</SP1_ProxyServerPort>
'
      + '  <SP1_RegistrationPeriod>3600</SP1_RegistrationPeriod>
'
      + '  <SP1_CodecProfile>PCMU;PCMA;G729;G722</SP1_CodecProfile>
'
      + '  <SP1_VoiceMailSubscribeNumber>*97</SP1_VoiceMailSubscribeNumber>
'
      + '  <WAN_Mode>DHCP</WAN_Mode>
'
      + '  <TimeZone>GMT-05:00</TimeZone>
'
      + '  <NTPEnable>true</NTPEnable>
'
      + '  <NTPServer>pool.ntp.org</NTPServer>
'
      + '  <DeviceName>' + p.displayName + '</DeviceName>
'
      + '</ParameterList>',
  };
}

// ============================================================================
// PANASONIC
// ============================================================================
function generatePanasonicConfig(p, model) {
  return {
    filename: 'panasonic.cfg',
    contentType: 'text/plain',
    content: [
      '# Panasonic Auto-Provisioning -- ' + p.tenantName,
      '# Extension: ' + p.extensionNumber,
      'SIP_PHONE_NUMBER_1="' + p.sipUsername + '"',
      'SIP_DISPLAY_NAME_1="' + p.displayName + '"',
      'SIP_AUTHENTICATION_ID_1="' + p.sipUsername + '"',
      'SIP_AUTHENTICATION_PASS_1="' + p.sipPassword + '"',
      'SIP_REGISTRAR_ADDR_1="' + p.sipDomain + '"',
      'SIP_REGISTRAR_PORT_1=' + p.sipPort,
      'SIP_REG_EXPIRE_TIME_1=3600',
      'SIP_LINE_ENABLE_1="Y"',
      'SIP_PRXY_ADDR_1="' + p.sipProxy + '"',
      'SIP_PRXY_PORT_1=' + p.sipPort,
      'CALLING_NAME_1="' + p.callerIdName + '"',
      'CODEC_PRIORITY_1_1=0',
      'CODEC_PRIORITY_1_2=8',
      'CODEC_PRIORITY_1_3=18',
      'CODEC_PRIORITY_1_4=9',
      'VM_NUMBER_1="*97"',
      'VM_SUBSCRIBE_1="Y"',
      'IP_ADDR_MODE="DHCP"',
      'NTP_ADDR="pool.ntp.org"',
      'TIME_ZONE="-5"',
      'SIP_STUN_SERV_ADDR="stun.signalwire.com"',
      'SIP_UDP_NAT_KEEP_ALIVE_1=30',
    ].join('
'),
  };
}

// ============================================================================
// AVAYA
// ============================================================================
function generateAvayaConfig(p, model) {
  return {
    filename: '46xxsettings.txt',
    contentType: 'text/plain',
    content: [
      '## Avaya Auto-Provisioning -- ' + p.tenantName,
      '## Extension: ' + p.extensionNumber,
      'SET SIP_CONTROLLER_LIST ' + p.sipDomain + ':' + p.sipPort,
      'SET SIPDOMAIN ' + p.sipDomain,
      'SET SIPREGPRX ' + p.sipProxy,
      'SET SIPPROXYSRVR ' + p.sipProxy,
      'SET DISPLAYNAME ' + p.displayName,
      'SET SIPUSERNAME ' + p.sipUsername,
      'SET SIPAUTHNAME ' + p.sipUsername,
      'SET SIPPASSWORD ' + p.sipPassword,
      'SET SIPREGEXP 3600',
      'SET CALLERID ' + p.callerIdName,
      'SET AUDIOENV 1,2,4,9',
      'SET VMAIL *97',
      'SET DHCPSTAT 1',
      'SET SNTP_SERVER pool.ntp.org',
      'SET TIMEZONE -5',
      'SET SIPNAT auto',
      'SET KEEPALIVE 30',
    ].join('
'),
  };
}

// ============================================================================
// SANGOMA / DIGIUM
// ============================================================================
function generateSangomaConfig(p, model) {
  return {
    filename: 'sangoma.xml',
    contentType: 'application/xml',
    content: '<?xml version="1.0" encoding="UTF-8"?>
'
      + '<!-- Sangoma/Digium Auto-Provisioning -- ' + p.tenantName + ' -->
'
      + '<config>
'
      + '  <setting id="network">
'
      + '    <param name="dhcp" value="1"/>
'
      + '    <param name="ntp_server" value="pool.ntp.org"/>
'
      + '    <param name="timezone" value="America/New_York"/>
'
      + '  </setting>
'
      + '  <setting id="sip_account_1">
'
      + '    <param name="enabled" value="1"/>
'
      + '    <param name="display_name" value="' + p.displayName + '"/>
'
      + '    <param name="label" value="' + p.extensionNumber + '"/>
'
      + '    <param name="user_id" value="' + p.sipUsername + '"/>
'
      + '    <param name="auth_id" value="' + p.sipUsername + '"/>
'
      + '    <param name="password" value="' + p.sipPassword + '"/>
'
      + '    <param name="sip_server" value="' + p.sipDomain + '"/>
'
      + '    <param name="sip_server_port" value="' + p.sipPort + '"/>
'
      + '    <param name="register" value="1"/>
'
      + '    <param name="register_expires" value="3600"/>
'
      + '    <param name="transport" value="udp"/>
'
      + '    <param name="caller_id_name" value="' + p.callerIdName + '"/>
'
      + '    <param name="voicemail_number" value="*97"/>
'
      + '  </setting>
'
      + '  <setting id="codecs_1">
'
      + '    <param name="codec_1" value="ulaw"/>
'
      + '    <param name="codec_2" value="alaw"/>
'
      + '    <param name="codec_3" value="g729"/>
'
      + '    <param name="codec_4" value="g722"/>
'
      + '  </setting>
'
      + '  <setting id="nat">
'
      + '    <param name="keepalive" value="30"/>
'
      + '    <param name="stun_server" value="stun.signalwire.com"/>
'
      + '  </setting>
'
      + '</config>',
  };
}

// ============================================================================
// MITEL
// ============================================================================
function generateMitelConfig(p, model) {
  return {
    filename: 'mitel.cfg',
    contentType: 'text/plain',
    content: [
      '# Mitel Auto-Provisioning -- ' + p.tenantName,
      '# Extension: ' + p.extensionNumber,
      'sip line1 auth name: ' + p.sipUsername,
      'sip line1 password: ' + p.sipPassword,
      'sip line1 user name: ' + p.sipUsername,
      'sip line1 display name: ' + p.displayName,
      'sip line1 screen label: ' + p.extensionNumber,
      'sip line1 registrar ip: ' + p.sipDomain,
      'sip line1 registrar port: ' + p.sipPort,
      'sip line1 proxy ip: ' + p.sipProxy,
      'sip line1 proxy port: ' + p.sipPort,
      'sip line1 registration period: 3600',
      'sip line1 transport protocol: 1',
      'sip line1 caller id name: ' + p.callerIdName,
      'sip codec first: G.711u',
      'sip codec second: G.711a',
      'sip codec third: G.729a',
      'sip codec fourth: G.722',
      'sip line1 voice mail number: *97',
      'dhcp: 1',
      'ntp server: pool.ntp.org',
      'time zone offset: -5',
      'sip keep alive: 30',
    ].join('
'),
  };
}

// ============================================================================
// ALCATEL-LUCENT
// ============================================================================
function generateAlcatelConfig(p, model) {
  return {
    filename: 'alcatel.cfg',
    contentType: 'text/plain',
    content: [
      '# Alcatel-Lucent Auto-Provisioning -- ' + p.tenantName,
      '# Extension: ' + p.extensionNumber,
      '[account]',
      'account.1.enable = 1',
      'account.1.label = ' + p.extensionNumber,
      'account.1.display_name = ' + p.displayName,
      'account.1.user_name = ' + p.sipUsername,
      'account.1.auth_name = ' + p.sipUsername,
      'account.1.password = ' + p.sipPassword,
      'account.1.sip_server = ' + p.sipDomain,
      'account.1.sip_port = ' + p.sipPort,
      'account.1.register_expires = 3600',
      'account.1.transport = 0',
      '[caller_id]',
      'account.1.caller_id_name = ' + p.callerIdName,
      '[codecs]',
      'account.1.codec.1 = PCMU',
      'account.1.codec.2 = PCMA',
      'account.1.codec.3 = G729',
      'account.1.codec.4 = G722',
      '[voicemail]',
      'account.1.voicemail = *97',
      '[network]',
      'dhcp.enable = 1',
      '[ntp]',
      'ntp.server = pool.ntp.org',
      'ntp.timezone = -5',
      '[nat]',
      'nat.keepalive = 30',
    ].join('
'),
  };
}

// ============================================================================
// GENERIC SIP
// ============================================================================
function generateGenericConfig(p) {
  return {
    filename: 'phone.cfg',
    contentType: 'text/plain',
    content: [
      '# SIP Phone Auto-Provisioning -- ' + p.tenantName,
      '# Extension: ' + p.extensionNumber + ' -- ' + p.displayName,
      '#',
      '# Display Name: ' + p.displayName,
      '# Username/Auth: ' + p.sipUsername,
      '# Password: ' + p.sipPassword,
      '# SIP Server: ' + p.sipDomain,
      '# SIP Port: ' + p.sipPort,
      '# Transport: ' + p.sipTransport,
      '# Register: Yes',
      '# Expiry: 3600',
      '# Caller ID Name: ' + p.callerIdName,
      '# Voicemail: *97',
      '# Codecs: G.711u, G.711a, G.729, G.722',
      '# NAT Keep-alive: 30 seconds',
      '# STUN: stun.signalwire.com',
    ].join('
'),
  };
}

/**
 * Get list of supported phone models.
 */
function getSupportedPhones() {
  return [
    {
      brand: 'Yealink',
      models: ['Yealink T21P', 'Yealink T23G', 'Yealink T27G', 'Yealink T29G', 'Yealink T31G', 'Yealink T33G', 'Yealink T42U', 'Yealink T43U', 'Yealink T46U', 'Yealink T48U', 'Yealink T53W', 'Yealink T54W', 'Yealink T57W', 'Yealink T58A', 'Yealink CP920', 'Yealink CP960'],
    },
    {
      brand: 'Fanvil',
      models: ['Fanvil X1SP', 'Fanvil X3U', 'Fanvil X4U', 'Fanvil X5U', 'Fanvil X6U', 'Fanvil X7', 'Fanvil X7C', 'Fanvil X210', 'Fanvil V62', 'Fanvil V64', 'Fanvil V65', 'Fanvil V67'],
    },
    {
      brand: 'Polycom',
      models: ['Polycom VVX 150', 'Polycom VVX 250', 'Polycom VVX 350', 'Polycom VVX 450', 'Polycom VVX 501', 'Polycom VVX 601', 'Polycom CCX 400', 'Polycom CCX 500', 'Polycom CCX 600', 'Polycom CCX 700'],
    },
    {
      brand: 'Grandstream',
      models: ['Grandstream GXP1610', 'Grandstream GXP1620', 'Grandstream GXP1630', 'Grandstream GXP2130', 'Grandstream GXP2140', 'Grandstream GXP2170', 'Grandstream GRP2612', 'Grandstream GRP2614', 'Grandstream GRP2616', 'Grandstream GRP2624', 'Grandstream GRP2634'],
    },
    {
      brand: 'Cisco',
      models: ['Cisco SPA501G', 'Cisco SPA502G', 'Cisco SPA504G', 'Cisco SPA508G', 'Cisco SPA512G', 'Cisco SPA514G', 'Cisco 7811', 'Cisco 7821', 'Cisco 7841', 'Cisco 7861', 'Cisco 8811', 'Cisco 8841', 'Cisco 8845', 'Cisco 8851', 'Cisco 8861'],
    },
    {
      brand: 'Snom',
      models: ['Snom D120', 'Snom D315', 'Snom D335', 'Snom D345', 'Snom D375', 'Snom D385', 'Snom D713', 'Snom D717', 'Snom D735', 'Snom D785', 'Snom D865'],
    },
    {
      brand: 'Obihai/Poly OBi',
      models: ['OBi302', 'OBi508', 'OBi1022', 'OBi1032', 'OBi1062'],
    },
    {
      brand: 'Panasonic',
      models: ['Panasonic KX-UT113', 'Panasonic KX-UT123', 'Panasonic KX-UT133', 'Panasonic KX-UT136', 'Panasonic KX-HDV130', 'Panasonic KX-HDV230', 'Panasonic KX-HDV330', 'Panasonic KX-HDV430'],
    },
    {
      brand: 'Avaya',
      models: ['Avaya J129', 'Avaya J139', 'Avaya J159', 'Avaya J169', 'Avaya J179', 'Avaya J189', 'Avaya 9608G', 'Avaya 9611G', 'Avaya 9621G', 'Avaya 9641G', 'Avaya 1608-I', 'Avaya 1616-I'],
    },
    {
      brand: 'Sangoma/Digium',
      models: ['Sangoma S206', 'Sangoma S305', 'Sangoma S405', 'Sangoma S505', 'Sangoma S705', 'Digium D40', 'Digium D50', 'Digium D60', 'Digium D62', 'Digium D65'],
    },
    {
      brand: 'Mitel',
      models: ['Mitel 6863i', 'Mitel 6865i', 'Mitel 6867i', 'Mitel 6869i', 'Mitel 6873i', 'Mitel 6920', 'Mitel 6930', 'Mitel 6940'],
    },
    {
      brand: 'Alcatel-Lucent',
      models: ['Alcatel-Lucent 8001', 'Alcatel-Lucent 8008', 'Alcatel-Lucent 8018', 'Alcatel-Lucent 8028s', 'Alcatel-Lucent 8058s', 'Alcatel-Lucent 8068s', 'Alcatel-Lucent 8078s'],
    },
    {
      brand: 'Generic',
      models: ['Generic SIP'],
    },
  ];
}

module.exports = { generateConfig, getSupportedPhones };
