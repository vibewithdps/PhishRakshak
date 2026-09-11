<?php

namespace App\Services;

class ScamDetectionService
{
    /**
     * Verified official domains across Global & India
     * (Search, Banking, Govt, E-Commerce, Social, Delivery, Tech)
     */
    private array $verifiedDomains = [
        // Tech & Search
        'google.com', 'google.co.in', 'youtube.com', 'apple.com', 'icloud.com',
        'microsoft.com', 'live.com', 'outlook.com', 'github.com', 'linkedin.com',
        'twitter.com', 'x.com', 'facebook.com', 'instagram.com', 'whatsapp.com',
        'telegram.org', 'zoom.us', 'wikipedia.org', 'stackoverflow.com', 'reddit.com',
        'medium.com', 'spotify.com', 'adobe.com', 'netflix.com', 'cloudflare.com',
        'aws.amazon.com', 'openai.com', 'anthropic.com', 'gemini.google.com',

        // Indian Banking & Payments
        'sbi.co.in', 'onlinesbi.sbi', 'onlinesbi.com', 'hdfcbank.com', 'icicibank.com',
        'axisbank.com', 'kotak.com', 'pnbindia.in', 'bankofbaroda.in', 'canarabank.com',
        'unionbankofindia.co.in', 'idfcfirstbank.com', 'indusind.com', 'yesbank.in',
        'rbi.org.in', 'npci.org.in', 'bhimupi.org.in', 'paytm.com', 'phonepe.com',
        'cred.club', 'zerodha.com', 'groww.in', 'angelone.in', 'upstox.com',

        // Indian Government & Utilities
        'gov.in', 'nic.in', 'uidai.gov.in', 'incometax.gov.in', 'epfindia.gov.in',
        'passportindia.gov.in', 'parivahan.gov.in', 'irctc.co.in', 'digilocker.gov.in',
        'cybercrime.gov.in', 'trai.gov.in', 'mha.gov.in', 'cbic.gov.in',

        // E-Commerce, Food & Logistics
        'amazon.in', 'amazon.com', 'flipkart.com', 'myntra.com', 'ajio.com',
        'meesho.com', 'swiggy.com', 'zomato.com', 'blinkit.com', 'zepto.com',
        'tatacliq.com', 'nykaa.com', 'bookmyshow.com', 'makemytrip.com', 'goibibo.com',
        'yatra.com', 'redbus.in', 'delhivery.com', 'bluedart.com', 'indiapost.gov.in',
        'dtdc.in', 'fedex.com', 'dhl.com',

        // Telecom & Hosting
        'jio.com', 'airtel.in', 'myvi.in', 'bsnl.co.in', 'vercel.app', 'netlify.app',
        'github.io', 'pages.dev', 'firebaseapp.com', 'render.com', 'fiverr.com', 'upwork.com'
    ];

    /**
     * High-risk top-level domains frequently abused by phishing campaigns
     */
    private array $highRiskTlds = [
        'xyz', 'top', 'click', 'buzz', 'club', 'online', 'site', 'icu',
        'work', 'date', 'link', 'live', 'loan', 'stream', 'gq', 'cf',
        'ml', 'ga', 'tk', 'cyou', 'rest', 'fit', 'surf', 'quest', 'skin',
        'bar', 'sbs', 'beauty', 'hair', 'press', 'host', 'space', 'monster'
    ];

    /**
     * Common URL shorteners used to obfuscate destinations
     */
    private array $urlShorteners = [
        'bit.ly', 'tinyurl.com', 't.co', 'cutt.ly', 'is.gd', 'ow.ly',
        'buff.ly', 'rebrand.ly', 'shorturl.at', 'tiny.cc', 'bl.ink', 'v.gd'
    ];

    /**
     * High-value target brands frequently impersonated in phishing attacks
     */
    private array $targetBrands = [
        'sbi' => ['sbi.co.in', 'onlinesbi.sbi', 'onlinesbi.com'],
        'hdfc' => ['hdfcbank.com'],
        'icici' => ['icicibank.com'],
        'axis' => ['axisbank.com'],
        'kotak' => ['kotak.com'],
        'pnb' => ['pnbindia.in'],
        'paytm' => ['paytm.com'],
        'phonepe' => ['phonepe.com'],
        'gpay' => ['google.com'],
        'netflix' => ['netflix.com'],
        'amazon' => ['amazon.in', 'amazon.com'],
        'flipkart' => ['flipkart.com'],
        'apple' => ['apple.com', 'icloud.com'],
        'google' => ['google.com', 'google.co.in'],
        'microsoft' => ['microsoft.com', 'live.com', 'outlook.com'],
        'uidai' => ['uidai.gov.in', 'gov.in'],
        'aadhaar' => ['uidai.gov.in', 'gov.in'],
        'incometax' => ['incometax.gov.in', 'gov.in'],
        'jio' => ['jio.com'],
        'airtel' => ['airtel.in'],
        'delhivery' => ['delhivery.com'],
        'indiapost' => ['indiapost.gov.in', 'gov.in'],
        'fedex' => ['fedex.com']
    ];

    /**
     * Main entry point for detection
     */
    public function detect(string $content, string $type = 'sms'): array
    {
        $type = strtolower(trim($type));
        $content = trim($content);

        return match ($type) {
            'url' => $this->analyzeUrl($content),
            'apk' => $this->analyzeApk($content),
            'email', 'mail' => $this->analyzeEmail($content),
            'call' => $this->analyzeCall($content),
            default => $this->analyzeSms($content),
        };
    }

    // =========================================================================
    // 1. URL & DOMAIN INTELLIGENCE ANALYZER
    // =========================================================================

    public function analyzeUrl(string $url): array
    {
        $rawUrl = trim($url);
        $normalizedUrl = $rawUrl;

        if (!preg_match('#^https?://#i', $normalizedUrl)) {
            $normalizedUrl = 'http://' . $normalizedUrl;
        }

        $parsed = parse_url($normalizedUrl);
        $host = strtolower($parsed['host'] ?? '');
        $path = strtolower($parsed['path'] ?? '');
        $query = strtolower($parsed['query'] ?? '');
        $scheme = strtolower($parsed['scheme'] ?? 'http');

        if (empty($host)) {
            return [
                'is_phishing' => false,
                'confidence' => 0.15,
                'category' => 'Invalid URL',
                'explanation' => 'Could not parse a valid domain or host name from the provided input.'
            ];
        }

        // Check if host is raw IP address (e.g. http://192.168.1.1/login or http://45.33.22.11)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $isPrivate = !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            $confidence = $isPrivate ? 0.72 : 0.93;

            return [
                'is_phishing' => true,
                'confidence' => $confidence,
                'category' => 'IP-Based Phishing Link',
                'explanation' => 'URL uses a raw IP address (' . $host . ') instead of a registered domain name. Legitimate public services never ask users to access banking or logins via raw IP addresses.'
            ];
        }

        // Check against verified legitimate domains
        $isVerifiedSafe = false;
        foreach ($this->verifiedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                $isVerifiedSafe = true;
                break;
            }
        }

        // Brand Impersonation & Typosquatting Analysis
        $impersonationRisk = $this->checkBrandImpersonation($host, $path);
        if ($impersonationRisk['is_impersonation']) {
            $baseConfidence = 0.88;
            if ($this->isHighRiskTld($host)) {
                $baseConfidence += 0.08;
            }
            if ($this->hasPhishingPathKeywords($path . ' ' . $query)) {
                $baseConfidence += 0.03;
            }

            return [
                'is_phishing' => true,
                'confidence' => min($baseConfidence, 0.98),
                'category' => 'Brand Impersonation / Typosquatting',
                'explanation' => sprintf(
                    'Detected deceptive brand spoofing targeting "%s". The domain "%s" is not the official brand domain and appears designed to harvest credentials or banking details.',
                    strtoupper($impersonationRisk['brand']),
                    $host
                )
            ];
        }

        // If it's a verified safe domain and passed impersonation check
        if ($isVerifiedSafe) {
            $confidence = 0.08;
            if ($this->hasPhishingPathKeywords($query)) {
                $confidence = 0.22;
            }

            return [
                'is_phishing' => false,
                'confidence' => $confidence,
                'category' => 'Verified Safe Domain',
                'explanation' => sprintf('The domain "%s" is a verified official domain with valid reputation and no spoofing indicators.', $host)
            ];
        }

        // Check for URL Shorteners
        foreach ($this->urlShorteners as $shortener) {
            if ($host === $shortener || str_ends_with($host, '.' . $shortener)) {
                $confidence = 0.74;
                if ($this->hasPhishingPathKeywords($path . ' ' . $query)) {
                    $confidence += 0.12;
                }

                return [
                    'is_phishing' => true,
                    'confidence' => min($confidence, 0.89),
                    'category' => 'Obfuscated / Shortened URL',
                    'explanation' => sprintf('This is a shortened URL (%s) used to mask the actual landing page. Scammers frequently use URL shorteners to evade security scanners and redirect victims to fake websites.', $host)
                ];
            }
        }

        // Dynamic Risk Accumulation for Unverified Domains
        $riskScore = 0.25; // baseline uncertainty
        $detectedSignals = [];

        // Check high-risk TLD
        $tld = $this->getTld($host);
        if (in_array($tld, $this->highRiskTlds, true)) {
            $riskScore += 0.35;
            $detectedSignals[] = 'untrusted high-risk TLD (.' . $tld . ')';
        }

        // Check high hyphen count / domain entropy
        $hyphenCount = substr_count($host, '-');
        if ($hyphenCount >= 3) {
            $riskScore += 0.20;
            $detectedSignals[] = 'excessive hyphenation indicating domain spoofing';
        } elseif ($hyphenCount >= 1) {
            $riskScore += 0.08;
        }

        // Excessive subdomain nesting
        $subdomainCount = substr_count($host, '.');
        if ($subdomainCount >= 3) {
            $riskScore += 0.15;
            $detectedSignals[] = 'excessive subdomain depth';
        }

        // Suspicious path & query parameters
        if ($this->hasPhishingPathKeywords($path . ' ' . $query)) {
            $riskScore += 0.20;
            $detectedSignals[] = 'sensitive authentication/banking keywords in URL path';
        }

        // Insecure HTTP on sensitive keywords
        if ($scheme === 'http' && $this->hasPhishingPathKeywords($path . ' ' . $query)) {
            $riskScore += 0.12;
            $detectedSignals[] = 'insecure unencrypted HTTP protocol';
        }

        $confidence = round(min(max($riskScore, 0.10), 0.96), 2);
        $isPhishing = $confidence >= 0.50;

        if ($isPhishing) {
            $primarySignal = !empty($detectedSignals) ? implode(', ', $detectedSignals) : 'unverified domain patterns';
            return [
                'is_phishing' => true,
                'confidence' => $confidence,
                'category' => in_array($tld, $this->highRiskTlds, true) ? 'Suspicious TLD / Unverified Web Host' : 'Phishing / Suspicious URL',
                'explanation' => sprintf('This URL shows suspicious characteristics: %s. Exercise extreme caution before entering passwords or financial data.', $primarySignal)
            ];
        }

        return [
            'is_phishing' => false,
            'confidence' => $confidence,
            'category' => 'Low Risk Web Domain',
            'explanation' => sprintf('The domain "%s" did not trigger specific phishing heuristics, though it is not on the primary verified brand list. Verify SSL and authenticity before login.', $host)
        ];
    }

    // =========================================================================
    // 2. SMS & MESSAGE ANALYZER (SMISHING)
    // =========================================================================

    public function analyzeSms(string $text): array
    {
        $lower = strtolower($text);

        // STEP 1: False Positive Mitigation for Genuine Transactional Alerts
        $genuineCheck = $this->evaluateLegitimateMessage($lower, $text);
        if ($genuineCheck !== null) {
            return $genuineCheck;
        }

        // STEP 2: Evaluate Multi-Category Smishing Signals
        $candidates = [
            'Digital Arrest / Law Enforcement Extortion' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'digital arrest' => 45, 'cbi' => 30, 'fir' => 25, 'narcotics' => 30,
                    'parcel seized' => 25, 'arrest warrant' => 35, 'warrant' => 25,
                    'supreme court' => 25, 'customs seized' => 30, 'money laundering' => 35,
                    'गिरफ्तारी' => 35, 'पुलिस' => 25, 'एफआईआर' => 25, 'डिजिटल अरेस्ट' => 45
                ]),
                'category' => 'Digital Arrest / Police Impersonation',
                'explanation' => 'Uses fear of police arrest, CBI, court warrants, or digital arrest to extort money. Real law enforcement agencies never issue arrest warrants or demand settlement via SMS/calls.'
            ],
            'OTP & Credential Harvesting' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'share otp' => 40, 'tell otp' => 40, 'forward otp' => 45, 'send otp' => 35,
                    'enter upi pin' => 50, 'provide pin' => 35, 'cvv' => 35, 'share pin' => 35,
                    'password expired' => 30, 'verify credentials' => 30, 'tell password' => 35,
                    'ओटीपी बताएं' => 40, 'पिन डालें' => 40, 'otp शेयर' => 40, 'पासवर्ड बताएं' => 40
                ]),
                'category' => 'OTP / Credential Harvesting',
                'explanation' => 'Attempts to harvest sensitive credentials like OTP, PIN, password, or CVV. Legitimate banks and services never ask users to disclose or forward OTPs.'
            ],
            'KYC & Bank Account Phishing' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'kyc' => 25, 'pan update' => 25, 'pan card' => 20, 'aadhaar link' => 25,
                    'account blocked' => 30, 'card blocked' => 30, 'debit card suspended' => 30,
                    'net banking' => 15, 'sbi' => 15, 'hdfc' => 15, 'icici' => 15, 'pnb' => 15,
                    'axis' => 15, 'account deactivation' => 30, 'verify account' => 25,
                    'केवाईसी' => 25, 'खाता बंद' => 30, 'पैन कार्ड' => 20, 'आधार अपडेट' => 20, 'खाता ब्लॉक' => 30
                ]),
                'category' => 'KYC / Bank Phishing',
                'explanation' => 'Falsely claims that bank account or KYC has expired or is blocked. Scammers urge victims to verify KYC through untrusted links to steal banking credentials.'
            ],
            'Electricity / Utility Bill Scam' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'electricity' => 20, 'power bill' => 30, 'bill pending' => 25, 'bill overdue' => 25,
                    'disconnect' => 25, 'power cut' => 25, 'bijli' => 25, 'light bill' => 25,
                    'electricity officer' => 30, 'meter' => 15, 'बिजली बिल' => 35, 'बिजली कट' => 35,
                    'कनेक्शन कट' => 35, 'बिल बकाया' => 30
                ]),
                'category' => 'Electricity / Utility Bill Scam',
                'explanation' => 'Creates panic about immediate power or utility disconnection tonight to force urgent payments to fraudulent phone numbers or links.'
            ],
            'UPI / Cashback / Payment Trap' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'cashback' => 25, 'reward waiting' => 25, 'claim refund' => 30, 'approve payment' => 30,
                    'receive money' => 25, 'money waiting' => 30, 'phonepe' => 15, 'gpay' => 15,
                    'paytm' => 15, 'scan qr' => 35, 'upi collect' => 30, 'upi pin' => 25,
                    'पैसे क्लेम' => 30, 'कैशबैक' => 25, 'रिफंड' => 25
                ]),
                'category' => 'UPI / Cashback Fraud',
                'explanation' => 'Lures victims with fake cashback, refunds, or rewards. Remember: Entering a UPI PIN is NEVER required to receive money, only to send it.'
            ],
            'Instant Loan App Trap' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'instant loan' => 35, 'quick loan' => 30, 'loan approved' => 30, 'no cibil' => 35,
                    'without document' => 30, 'low interest' => 20, 'disbursed' => 25,
                    'zero percent' => 25, 'personal loan' => 20, 'तुरंत लोन' => 35,
                    'बिना सिबिल' => 35, 'लोन पास' => 30, 'सस्ता लोन' => 25
                ]),
                'category' => 'Predatory Loan App Scam',
                'explanation' => 'Promotes unauthorized instant loan apps without documentation. Fraudulent loan apps often access phone contacts/photos and lead to harassment.'
            ],
            'Work From Home / Task Scam' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'part time job' => 30, 'earn daily' => 30, 'daily income' => 25, 'youtube like' => 40,
                    'telegram task' => 35, 'registration fee' => 35, 'work from home' => 20,
                    'copy paste job' => 35, 'typing job' => 30, 'घर बैठे कमाएं' => 35,
                    'रोज 5000' => 40, 'पार्ट टाइम' => 25
                ]),
                'category' => 'Work From Home / Task Scam',
                'explanation' => 'Promises unrealistic daily income for simple online tasks like liking videos or typing, but demands advance deposits and prepaid investments.'
            ],
            'Courier / Customs / Delivery Fee' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'package held' => 30, 'delivery failed' => 25, 'customs fee' => 35, 'wrong address' => 25,
                    'reschedule delivery' => 25, 'parcel blocked' => 30, 'custom duty' => 30,
                    'courier fee' => 30, 'delivery charge' => 25, 'पार्सल रुका' => 30,
                    'कूरियर फीस' => 30, 'डिलीवरी चार्ज' => 25
                ]),
                'category' => 'Fake Courier / Delivery Scam',
                'explanation' => 'Falsely claims an undelivered package needs address verification or a tiny redelivery/customs fee to capture card details.'
            ],
            'Prize / Lottery / Gift Scam' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'won' => 20, 'winner' => 25, 'lucky draw' => 35, 'lottery' => 40,
                    'kbc' => 40, 'iphone' => 20, 'gift card' => 25, 'free gift' => 25,
                    'congratulations' => 15, 'बधाई' => 25, 'लॉटरी' => 40, 'इनाम' => 30
                ]),
                'category' => 'Prize / Lottery Scam',
                'explanation' => 'Claims you have won a lottery or expensive prize in an event you never participated in, requiring advance processing fees.'
            ],
            'TRAI / SIM Deactivation' => [
                'weight' => $this->calculateSignalScore($lower, [
                    'sim blocked' => 35, 'sim card' => 15, 'trai' => 30, 'disconnected' => 25,
                    'deactivation' => 30, 'mobile blocked' => 35, 'सिम बंद' => 35, 'नंबर बंद' => 35
                ]),
                'category' => 'SIM / Telecom Scam',
                'explanation' => 'Threatens imminent SIM or phone deactivation under false authority notices to compel calling fake support lines or clicking phishing links.'
            ],
        ];

        // Find the strongest matching category
        $bestCategory = null;
        $highestWeight = 0;

        foreach ($candidates as $name => $data) {
            if ($data['weight'] > $highestWeight) {
                $highestWeight = $data['weight'];
                $bestCategory = $data;
            }
        }

        // Additional modifiers for dynamic confidence
        $urgencyScore = $this->calculateSignalScore($lower, [
            'immediately' => 15, 'urgently' => 15, 'within 2 hours' => 20, 'today' => 10,
            'tonight' => 15, 'final notice' => 20, 'last warning' => 20, 'act now' => 15,
            'तुरंत' => 15, 'आज रात' => 15
        ]);

        $hasLink = preg_match('#https?://|[a-z0-9\-]+\.(xyz|top|click|club|link|online|in|com)#i', $lower);
        $linkBonus = $hasLink ? 18 : 0;

        $hasPhone = preg_match('/\b[6-9][0-9]{9}\b/', $lower);
        $phoneBonus = $hasPhone ? 12 : 0;

        $totalScore = $highestWeight + ($urgencyScore * 0.5) + $linkBonus + $phoneBonus;

        if ($totalScore >= 35) {
            // Normalize confidence smoothly between 0.70 and 0.98
            $confidence = round(min(0.70 + (($totalScore - 35) / 100) * 0.28, 0.98), 2);
            $cat = $bestCategory['category'] ?? 'Suspicious Smishing Message';
            $explanation = $bestCategory['explanation'] ?? 'This message displays strong smishing patterns designed to trigger panic or urgency.';

            if ($hasLink) {
                $explanation .= ' Contains an unverified external link.';
            }

            return [
                'is_phishing' => true,
                'confidence' => $confidence,
                'category' => $cat,
                'explanation' => $explanation
            ];
        }

        // Check if it's just a general link without other signals
        if ($hasLink) {
            return [
                'is_phishing' => true,
                'confidence' => 0.62,
                'category' => 'Unverified Link in SMS',
                'explanation' => 'This message contains an unverified link without standard verification text. Exercise caution before opening links received from unknown senders.'
            ];
        }

        return [
            'is_phishing' => false,
            'confidence' => round(max(0.08, min(0.24, $totalScore / 100)), 2),
            'category' => 'Safe Content',
            'explanation' => 'No active scam, urgency, or credential theft indicators detected in this message.'
        ];
    }

    // =========================================================================
    // 3. APK & ANDROID PACKAGE ANALYZER
    // =========================================================================

    public function analyzeApk(string $content): array
    {
        $lower = strtolower(trim($content));

        // Official verified app packages whitelist
        $officialPackages = [
            'com.whatsapp' => 'WhatsApp Messenger',
            'com.google.android.apps.nbu.paisa.user' => 'Google Pay',
            'net.one97.paytm' => 'Paytm',
            'com.phonepe.app' => 'PhonePe',
            'com.sbi.lotusintouch' => 'SBI YONO Official',
            'com.msbi.paylance' => 'SBI Quick',
            'com.snapwork.hdfc' => 'HDFC Bank Mobile',
            'com.csam.icici.bank.imobile' => 'ICICI iMobile Pay',
            'com.axis.mobile' => 'Axis Mobile',
            'com.msf.kbank.mobile' => 'Kotak Bank Mobile',
            'in.org.npci.upiapp' => 'BHIM UPI Official',
            'in.gov.uidai.maadhaarplus' => 'mAadhaar Official',
            'com.instagram.android' => 'Instagram',
            'com.facebook.katana' => 'Facebook',
            'org.telegram.messenger' => 'Telegram'
        ];

        foreach ($officialPackages as $pkg => $name) {
            if ($lower === $pkg || $lower === $pkg . '.apk') {
                return [
                    'is_phishing' => false,
                    'confidence' => 0.06,
                    'category' => 'Verified Official App Package',
                    'explanation' => sprintf('Package identifier matches official verified app: %s (%s).', $name, $pkg)
                ];
            }
        }

        // Sideloaded Banking Trojan / Spoofed Financial APKs
        $bankingSpoofs = ['sbi', 'yono', 'hdfc', 'icici', 'axis', 'pnb', 'paytm', 'phonepe', 'bhim', 'rbi', 'bank'];
        $hasBankBrand = false;
        $matchedBrand = '';
        foreach ($bankingSpoofs as $brand) {
            if (str_contains($lower, $brand)) {
                $hasBankBrand = true;
                $matchedBrand = strtoupper($brand);
                break;
            }
        }

        if ($hasBankBrand && ($this->containsAny($lower, ['.apk', 'update', 'security', 'kyc', 'reward', 'support', 'download']))) {
            return [
                'is_phishing' => true,
                'confidence' => 0.95,
                'category' => 'Sideloaded Banking Trojan',
                'explanation' => sprintf('Critical Risk: High-probability malware attempting to impersonate %s banking services via an unverified APK. Sideloaded banking APKs intercept SMS, OTPs, and banking credentials.', $matchedBrand)
            ];
        }

        // Fake Social / Modded APKs
        if ($this->containsAny($lower, ['whatsapp gold', 'whatsapp_gold', 'gbwhatsapp', 'fmwhatsapp', 'instagram pro', 'free recharge', 'free diamond'])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.91,
                'category' => 'Modded Trojan / Spyware APK',
                'explanation' => 'Unverified modded application (e.g. WhatsApp Gold / GBWhatsApp). Such APKs contain spyware, adware, or trojans that steal private messages and contacts.'
            ];
        }

        // Remote Access Droppers
        if ($this->containsAny($lower, ['anydesk', 'teamviewer', 'quicksupport', 'screenshare', 'remote control', 'screen mirror'])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.93,
                'category' => 'Remote Access Tool (RAT) Dropper',
                'explanation' => 'Remote access or screen-sharing application being distributed or recommended. Scammers utilize remote access tools to take full control of mobile devices and bank accounts.'
            ];
        }

        // Predatory Loan APKs
        if ($this->containsAny($lower, ['instant loan', 'easy loan', 'quick cash', 'speed loan', 'rupee loan', 'cash wallet', 'credit loan'])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.88,
                'category' => 'Predatory Loan App / Spyware',
                'explanation' => 'Unregulated loan APK. These apps typically harvest contact lists, photos, and location data to blackmail borrowers with exorbitant interest rates.'
            ];
        }

        // General APK file warning
        if (str_ends_with($lower, '.apk') || str_contains($lower, 'download apk') || str_contains($lower, 'install apk')) {
            return [
                'is_phishing' => true,
                'confidence' => 0.76,
                'category' => 'Unverified Sideloaded APK',
                'explanation' => 'APK file from outside the official Google Play Store. Sideloaded apps bypass Google Play Protect review and pose elevated security risks.'
            ];
        }

        return [
            'is_phishing' => false,
            'confidence' => 0.20,
            'category' => 'Standard App Reference',
            'explanation' => 'No known malicious APK patterns or fake banking dropper signatures detected.'
        ];
    }

    // =========================================================================
    // 4. EMAIL PHISHING ANALYZER
    // =========================================================================

    public function analyzeEmail(string $content): array
    {
        $lower = strtolower($content);

        // 1. Mailbox / Storage Suspension Phishing
        if ($this->containsAny($lower, [
            'mailbox quota', 'email quota', 'mailbox full', 'storage exceeded',
            'password expires', 'password expired', 'verify your mailbox',
            'account will be closed', 'mail suspended', 'email suspended',
            'keep your current password'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.91,
                'category' => 'Mailbox Quota Phishing',
                'explanation' => 'Deceptive email warning about mailbox storage limits or password expiration. Designed to trick users into entering webmail credentials on a spoofed portal.'
            ];
        }

        // 2. Fake Invoices, DocuSign & Cloud Document Lures
        if ($this->containsAny($lower, [
            'past due invoice', 'invoice attached', 'payment receipt attached',
            'docusign document', 'review docusign', 'onedrive document shared',
            'sharepoint document', 'dropbox link to invoice', 'remittance advice',
            'overdue payment'
        ])) {
            $confidence = 0.85;
            if ($this->containsAny($lower, ['click here', 'open attachment', 'view document', 'http', 'bit.ly'])) {
                $confidence = 0.92;
            }

            return [
                'is_phishing' => true,
                'confidence' => $confidence,
                'category' => 'Fake Invoice / DocuSign Phishing',
                'explanation' => 'Uses fake invoices or cloud document review lures (DocuSign/OneDrive/SharePoint) to deliver malware or direct victims to credential-harvesting web forms.'
            ];
        }

        // 3. Security Alert & Account Compromise Spoofing
        if ($this->containsAny($lower, [
            'unusual sign in', 'unusual login', 'account security alert',
            'compromised password', 'security team alert', 'sign-in from unknown device',
            'login attempt from russia', 'login attempt from nigeria', 'confirm your identity'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.87,
                'category' => 'Security Alert Spoofing',
                'explanation' => 'Fabricates security breaches or unauthorized logins to induce panic and force the victim onto a credential-harvesting phishing page.'
            ];
        }

        // Check if there are general Smishing / Phishing vectors within email body
        $smsResult = $this->analyzeSms($content);
        if ($smsResult['is_phishing']) {
            return [
                'is_phishing' => true,
                'confidence' => min($smsResult['confidence'] + 0.02, 0.97),
                'category' => 'Email ' . $smsResult['category'],
                'explanation' => $smsResult['explanation']
            ];
        }

        return [
            'is_phishing' => false,
            'confidence' => 0.12,
            'category' => 'Normal Business / Personal Email',
            'explanation' => 'Email does not exhibit mailbox quota extortion, spoofed security warnings, or fake invoice phishing patterns.'
        ];
    }

    // =========================================================================
    // 5. SPAM CALL & VISHING ANALYZER
    // =========================================================================

    public function analyzeCall(string $content): array
    {
        $lower = strtolower($content);

        // 1. Digital Arrest / Law Enforcement Vishing
        if ($this->containsAny($lower, [
            'digital arrest', 'cbi officer', 'police officer', 'cyber crime branch',
            'drugs in parcel', 'narcotics bureau', 'money laundering case',
            'stay on video call', 'transfer money to verify', 'supreme court warrant',
            'डिजिटल अरेस्ट', 'पुलिस अधिकारी', 'सीबीआई', 'अरेस्ट वारंट'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.97,
                'category' => 'Digital Arrest / Police Extortion Vishing',
                'explanation' => 'Extreme Danger: Digital Arrest extortion scam. Scammers impersonate police, CBI, or customs officers over video/voice calls, claiming illegal parcels or crimes to intimidate victims into transferring money.'
            ];
        }

        // 2. Telecom / TRAI SIM Deactivation Threat
        if ($this->containsAny($lower, [
            'trai', 'sim deactivation', 'number will be disconnected',
            'within 2 hours', 'illegal activities on your number', 'press 9 to speak',
            'telecom verification', 'सिम बंद हो जाएगा', 'ट्राई'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.92,
                'category' => 'TRAI / SIM Disconnection Vishing',
                'explanation' => 'Automated robocall or fraudster falsely claiming your mobile number will be disconnected by TRAI. Genuine telecom authorities do not disconnect numbers via automated phone calls.'
            ];
        }

        // 3. Remote Access & Refund Vishing
        if ($this->containsAny($lower, [
            'anydesk', 'teamviewer', 'quicksupport', 'screen share', 'remote access',
            'customer care refund', 'paytm refund', 'phonepe customer care',
            'install app to get refund', 'ऐनीडेस्क', 'स्क्रीन शेयर'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.94,
                'category' => 'Remote Desktop / Refund Vishing',
                'explanation' => 'Fraudulent caller instructing the victim to install remote desktop software (AnyDesk, TeamViewer) under the guise of customer support or refunds.'
            ];
        }

        // 4. Bank Account / Credit Card Vishing
        if ($this->containsAny($lower, [
            'credit card reward points', 'card block', 'kyc verification on call',
            'share otp', 'tell otp', 'cvv on call', 'card expiry date',
            'bank customer care', 'ओटीपी बताएं', 'क्रेडिट कार्ड रिवॉर्ड'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.91,
                'category' => 'Bank / KYC Phone Scam (Vishing)',
                'explanation' => 'Caller attempting to extract banking credentials, credit card details, or OTP over the phone. Banks never solicit OTPs or PINs through voice calls.'
            ];
        }

        // 5. Unsolicited Telemarketing / Loan Pitch
        if ($this->containsAny($lower, [
            'pre approved loan', 'instant loan on call', 'zero percent interest',
            'lottery call', 'congratulations prize', 'लोन ऑफर'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.74,
                'category' => 'Predatory Telecaller / Spam',
                'explanation' => 'Unsolicited loan or lottery cold call. Verify credentials before providing Aadhaar or PAN details to unknown telecallers.'
            ];
        }

        return [
            'is_phishing' => false,
            'confidence' => 0.14,
            'category' => 'Standard Call Note',
            'explanation' => 'No high-risk vishing, digital arrest, or credential solicitation indicators found in this call summary.'
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Differentiates genuine transactional bank alerts from smishing attempts
     */
    private function evaluateLegitimateMessage(string $lower, string $originalText): ?array
    {
        // 1. Genuine Debit / Credit Transaction Alert
        $hasDebitCredit = (
            str_contains($lower, 'debited') ||
            str_contains($lower, 'credited') ||
            str_contains($lower, 'txn of') ||
            str_contains($lower, 'transferred')
        );

        $hasFinancialIdentifiers = (
            str_contains($lower, 'a/c') ||
            str_contains($lower, 'acct') ||
            str_contains($lower, 'account') ||
            str_contains($lower, 'inr') ||
            str_contains($lower, 'rs.') ||
            str_contains($lower, 'upi ref') ||
            str_contains($lower, 'rrn') ||
            str_contains($lower, 'avail bal') ||
            str_contains($lower, 'bal:')
        );

        $hasUrgentCallToAction = (
            str_contains($lower, 'click') ||
            str_contains($lower, 'call') ||
            str_contains($lower, 'blocked') ||
            str_contains($lower, 'kyc') ||
            str_contains($lower, 'verify within') ||
            str_contains($lower, 'suspended')
        );

        if ($hasDebitCredit && $hasFinancialIdentifiers && !$hasUrgentCallToAction) {
            return [
                'is_phishing' => false,
                'confidence' => 0.08,
                'category' => 'Legitimate Transaction Alert',
                'explanation' => 'Standard banking transaction confirmation (debit/credit notification) with no suspicious links or urgent calls to action.'
            ];
        }

        // 2. Genuine OTP Delivery Notification
        $hasOtpPattern = preg_match('/\b(otp|code|passcode)\b.*\b[0-9]{4,8}\b|\b[0-9]{4,8}\b.*\b(otp|code)\b/i', $lower);
        $hasSafetyDisclaimer = (
            str_contains($lower, 'do not share') ||
            str_contains($lower, 'never share') ||
            str_contains($lower, 'valid for') ||
            str_contains($lower, 'bank never asks') ||
            str_contains($lower, 'kisi ke sath share na kare') ||
            str_contains($lower, 'share na kare')
        );
        $asksToShareOrReply = (
            str_contains($lower, 'reply with') ||
            str_contains($lower, 'send this otp') ||
            str_contains($lower, 'tell otp') ||
            str_contains($lower, 'share with caller') ||
            str_contains($lower, 'click link to verify otp')
        );

        if ($hasOtpPattern && $hasSafetyDisclaimer && !$asksToShareOrReply && !$hasUrgentCallToAction) {
            return [
                'is_phishing' => false,
                'confidence' => 0.09,
                'category' => 'Legitimate OTP Notification',
                'explanation' => 'Official OTP delivery alert containing standard security warnings ("do not share"). No suspicious links or solicitation detected.'
            ];
        }

        // 3. Genuine E-Commerce / Delivery Status
        $hasDeliveryStatus = (
            str_contains($lower, 'out for delivery') ||
            str_contains($lower, 'delivered') ||
            str_contains($lower, 'shipped') ||
            str_contains($lower, 'order confirmed') ||
            str_contains($lower, 'deliver ho gaya')
        );
        $hasOfficialStore = (
            str_contains($lower, 'amazon') ||
            str_contains($lower, 'flipkart') ||
            str_contains($lower, 'myntra') ||
            str_contains($lower, 'zomato') ||
            str_contains($lower, 'swiggy')
        );

        if ($hasDeliveryStatus && $hasOfficialStore && !$hasUrgentCallToAction) {
            return [
                'is_phishing' => false,
                'confidence' => 0.07,
                'category' => 'Legitimate Delivery Update',
                'explanation' => 'Official order and delivery update notification with no suspicious fee or credential requests.'
            ];
        }

        return null;
    }

    /**
     * Brand typosquatting / lookalike detection
     */
    private function checkBrandImpersonation(string $host, string $path): array
    {
        foreach ($this->targetBrands as $brand => $allowedDomains) {
            // Check if brand is present in hostname
            if (str_contains($host, $brand)) {
                $isAllowed = false;
                foreach ($allowedDomains as $allowed) {
                    if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    return [
                        'is_impersonation' => true,
                        'brand' => $brand,
                    ];
                }
            }
        }

        return ['is_impersonation' => false, 'brand' => ''];
    }

    private function isHighRiskTld(string $host): bool
    {
        $tld = $this->getTld($host);
        return in_array($tld, $this->highRiskTlds, true);
    }

    private function getTld(string $host): string
    {
        $parts = explode('.', $host);
        return strtolower(end($parts));
    }

    private function hasPhishingPathKeywords(string $str): bool
    {
        return $this->containsAny($str, [
            'kyc', 'login', 'signin', 'verify', 'account', 'update',
            'auth', 'password', 'banking', 'secure', 'claim', 'refund',
            'reward', 'bonus', 'wallet', 'pan', 'aadhaar', 'disburse'
        ]);
    }

    private function calculateSignalScore(string $content, array $termsWithWeight): int
    {
        $score = 0;
        foreach ($termsWithWeight as $term => $weight) {
            if (str_contains($content, strtolower($term))) {
                $score += $weight;
            }
        }
        return $score;
    }

    private function containsAny(string $content, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
}
