<?php

namespace App\Services;

class ScamDetectionService
{
    public function detect(string $content, string $type = 'sms'): array
    {
        $content = strtolower($content);

        /*
            PhishRakshak Strong Rule-Based Scam Engine
            Covers common Indian + global scam patterns.
        */

        // Email phishing / fake invoice / mailbox scam
        if ($type === 'email' && $this->containsAny($content, [
            'subject:', 'from:', 'reply-to:', 'dear customer', 'dear user',
            'mailbox quota', 'email quota', 'password expires', 'password expired',
            'unusual sign in', 'unusual login', 'account security alert',
            'verify your mailbox', 'verify your email', 'update your email',
            'invoice attached', 'payment invoice', 'past due invoice', 'docusign',
            'onedrive document', 'sharepoint document', 'google drive document',
            'click to view document', 'confirm your account', 'security team',
            'your account will be closed', 'email suspended', 'mail suspended',
            'ईमेल वेरिफाई', 'मेलबॉक्स', 'पासवर्ड एक्सपायर', 'अकाउंट सुरक्षा'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.87,
                'category' => 'Email Phishing Scam',
                'explanation' => 'This email looks suspicious because it talks about mailbox verification, password expiry, fake invoice/document, or account security. Such emails often steal login details through fake links.'
            ];
        }

        // Spam call / voice phishing note
        if ($type === 'call' && $this->containsAny($content, [
            'unknown caller', 'spam call', 'robocall', 'telecaller', 'press 1',
            'kyc call', 'bank call', 'credit card offer', 'loan offer',
            'insurance offer', 'lottery call', 'prize call', 'customer care call',
            'otp on call', 'share otp', 'anydesk', 'teamviewer', 'remote access',
            'digital arrest', 'trai', 'sim block', 'number block', 'aadhaar verification',
            'ivr', 'recorded call', 'कॉल', 'ओटीपी बताएं', 'सिम बंद',
            'लोन ऑफर', 'बीमा ऑफर', 'डिजिटल अरेस्ट', 'कस्टमर केयर'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.86,
                'category' => 'Spam Call / Vishing Scam',
                'explanation' => 'This call note looks suspicious because it mentions OTP, KYC, loan/insurance offer, remote access, SIM blocking, or digital arrest. Do not share OTP/PIN or install remote support apps after unknown calls.'
            ];
        }

        // 1. OTP / PIN / Password / Credential Theft Scam
        if ($this->containsAny($content, [
            'otp', 'one time password', 'pin', 'upi pin', 'atm pin', 'cvv',
            'password', 'passcode', 'verification code', 'security code',
            'login code', 'bank code', 'card number', 'expiry date',
            'ओटीपी', 'पासवर्ड', 'पिन', 'सीवीवी', 'कोड भेजें',
            'otp batao', 'share otp', 'send otp', 'provide otp',
            'अपना otp बताएं', 'otp शेयर', 'pin बताएं'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.94,
                'category' => 'OTP / Credential Theft Scam',
                'explanation' => 'This message is highly suspicious because it mentions OTP, PIN, CVV, password, or verification code. Genuine banks and companies never ask users to share such private information.'
            ];
        }

        // 2. KYC / Bank Account Scam
        if ($this->containsAny($content, [
            'kyc', 'kyc update', 're-kyc', 'verify account', 'account verify',
            'account blocked', 'account suspended', 'account closed',
            'debit card blocked', 'credit card blocked', 'net banking blocked',
            'bank account', 'aadhaar link', 'pan link', 'update pan',
            'update aadhaar', 'sbi', 'hdfc', 'icici', 'axis bank', 'pnb',
            'bank of baroda', 'bob', 'kotak', 'yes bank', 'canara bank',
            'केवाईसी', 'खाता बंद', 'खाता ब्लॉक', 'खाता चालू',
            'बैंक खाता', 'आधार लिंक', 'पैन लिंक', 'verify karo',
            'बैंक वेरिफिकेशन', 'खाता सत्यापन'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.90,
                'category' => 'KYC / Bank Scam',
                'explanation' => 'This message looks like a bank/KYC scam because it creates fear about account blocking or asks for account verification. Banks usually do not ask users to update KYC through unknown links.'
            ];
        }

        // 3. UPI / Wallet / Payment Scam
        if ($this->containsAny($content, [
            'upi', 'phonepe', 'google pay', 'gpay', 'paytm', 'bhim',
            'wallet', 'payment request', 'collect request', 'receive money',
            'send money', 'cashback', 'refund pending', 'money waiting',
            'amount received', 'transfer received', 'account transfer',
            'balance', 'allow payment', 'approve request', 'upi collect',
            'scan qr', 'qr code', 'payment failed', 'payment successful',
            'claim refund', 'wallet balance', 'available in wallet',
            'यूपीआई', 'पेमेंट', 'वॉलेट', 'पैसे', 'रिफंड', 'कैशबैक',
            'राशि प्राप्त', 'भुगतान', 'qr स्कैन', 'क्यूआर', 'बैलेंस'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.88,
                'category' => 'UPI / Wallet Scam',
                'explanation' => 'This message may be a UPI or wallet scam because it talks about payment, wallet balance, cashback, refund, QR code, or money request. Never approve unknown UPI requests or scan unknown QR codes.'
            ];
        }

        // 4. Short Link / Suspicious URL Scam
        if ($this->containsAny($content, [
            'bit.ly', 'tinyurl', 't.co/', 'goo.gl', 'shorturl', 'cutt.ly',
            'rebrand.ly', 'is.gd', 'ow.ly', 'buff.ly', 'linktr.ee',
            'click here', 'click now', 'open link', 'visit link',
            'tap here', 'claim link', 'verify link', 'login link',
            'link पर क्लिक', 'लिंक खोलें', 'क्लिक करें', 'लिंक पर जाएं'
        ]) || $this->containsUrl($content)) {
            return [
                'is_phishing' => true,
                'confidence' => 0.82,
                'category' => 'Suspicious Link Scam',
                'explanation' => 'This message contains a link or shortened URL. Scammers often use such links to hide fake websites, steal login details, or install malware.'
            ];
        }

        // 5. Loan App Scam
        if ($this->containsAny($content, [
            'loan', 'instant loan', 'quick loan', 'personal loan', 'easy loan',
            'low interest', 'no documents', 'no cibil', 'loan approved',
            'loan disbursed', 'credit limit', 'cash loan', 'app loan',
            'loan apply', 'loan offer', 'loan eligibility', 'zero interest loan',
            'लोन', 'तुरंत लोन', 'बिना डॉक्यूमेंट', 'कम ब्याज',
            'loan mil jayega', 'सस्ता लोन', 'लोन अप्रूव'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.84,
                'category' => 'Loan App Scam',
                'explanation' => 'This message may be a loan app scam because it promises instant or easy loans. Fake loan apps often collect personal data and later harass users.'
            ];
        }

        // 6. Job / Work From Home Scam
        if ($this->containsAny($content, [
            'job offer', 'work from home', 'part time job', 'earn daily',
            'daily income', 'registration fee', 'joining fee', 'typing job',
            'data entry job', 'telegram job', 'whatsapp job', 'earn money',
            'salary credited', 'interview fee', 'training fee', 'online job',
            'captcha job', 'copy paste job', 'youtube like job',
            'नौकरी', 'घर बैठे कमाएं', 'पार्ट टाइम', 'रजिस्ट्रेशन फीस',
            'जॉइनिंग फीस', 'रोज कमाएं', 'ऑनलाइन जॉब'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.83,
                'category' => 'Job Scam',
                'explanation' => 'This message may be a job scam because it promises easy income or asks for registration/training fees. Genuine companies normally do not ask for money to provide jobs.'
            ];
        }

        // 7. Electricity / Utility Bill Scam
        if ($this->containsAny($content, [
            'electricity bill', 'power bill', 'bill pending', 'disconnect',
            'power cut', 'pay now', 'last date', 'meter', 'consumer number',
            'bill overdue', 'connection cut', 'electricity department',
            'bijli bill', 'light bill', 'water bill', 'gas bill',
            'बिजली बिल', 'बिल जमा', 'बिजली कट', 'कनेक्शन कट',
            'आज रात', 'तुरंत भुगतान', 'बिल बकाया'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.84,
                'category' => 'Electricity / Utility Bill Scam',
                'explanation' => 'This message may be an electricity or utility bill scam because it creates fear of disconnection and asks for urgent payment.'
            ];
        }

        // 8. Prize / Lottery / Reward Scam
        if ($this->containsAny($content, [
            'prize', 'reward', 'winner', 'lottery', 'congratulations',
            'lucky draw', 'gift card', 'free gift', 'claim now', 'won',
            'iphone won', 'car won', 'cash prize', 'scratch card',
            'amazon reward', 'flipkart reward', 'voucher', 'coupon prize',
            'इनाम', 'लॉटरी', 'जीत गए', 'बधाई', 'फ्री गिफ्ट',
            'पुरस्कार', 'लकी ड्रॉ', 'गिफ्ट कार्ड'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.82,
                'category' => 'Prize / Lottery Scam',
                'explanation' => 'This message may be a prize or lottery scam because it claims that the user has won something. Scammers use such messages to collect fees or steal details.'
            ];
        }

        // 9. Courier / Parcel / Delivery Scam
        if ($this->containsAny($content, [
            'parcel', 'courier', 'delivery failed', 'package held',
            'custom duty', 'customs', 'address update', 'delivery charge',
            'fedex', 'dhl', 'blue dart', 'delhivery', 'india post',
            'parcel blocked', 'package waiting', 'delivery pending',
            'wrong address', 'reschedule delivery', 'shipping fee',
            'पार्सल', 'कूरियर', 'डिलीवरी', 'पैकेज', 'कस्टम',
            'पता अपडेट', 'delivery charge', 'शिपिंग फीस'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.80,
                'category' => 'Courier / Parcel Scam',
                'explanation' => 'This message may be a courier scam because it asks for delivery charges, address update, or customs payment. Verify only through official courier websites.'
            ];
        }

        // 10. Government / Tax / Refund Scam
        if ($this->containsAny($content, [
            'income tax refund', 'tax refund', 'gst refund', 'pan update',
            'government subsidy', 'pm yojana', 'aadhaar update', 'ration card',
            'epfo', 'pf claim', 'pension update', 'refund approved',
            'ayushman card', 'voter card', 'driving licence update',
            'passport verification', 'government benefit', 'subsidy credited',
            'इनकम टैक्स', 'जीएसटी रिफंड', 'सरकारी योजना', 'सब्सिडी',
            'आधार अपडेट', 'पेंशन', 'पीएफ', 'राशन कार्ड'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.82,
                'category' => 'Government / Tax Refund Scam',
                'explanation' => 'This message may be a government or tax refund scam. Scammers often misuse names like income tax, GST, Aadhaar, PF, or government schemes to steal personal data.'
            ];
        }

        // 11. Crypto / Investment / Trading Scam
        if ($this->containsAny($content, [
            'crypto', 'bitcoin', 'btc', 'usdt', 'trading', 'forex',
            'investment plan', 'double money', 'guaranteed return',
            'profit daily', 'telegram signal', 'stock tips', 'share market tips',
            'mutual fund profit', 'binary trading', 'deposit now',
            'earn 5000 daily', 'double your income', 'high return',
            'क्रिप्टो', 'बिटकॉइन', 'निवेश', 'डबल पैसा', 'गारंटीड रिटर्न',
            'रोज प्रॉफिट', 'शेयर मार्केट टिप्स'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.86,
                'category' => 'Investment / Crypto Scam',
                'explanation' => 'This message may be an investment scam because it promises guaranteed or very high returns. Real investments never guarantee quick profit without risk.'
            ];
        }

        // 12. Fake Customer Care / Tech Support Scam
        if ($this->containsAny($content, [
            'customer care', 'helpline', 'support number', 'call now',
            'remote support', 'anydesk', 'teamviewer', 'screen share',
            'refund support', 'bank support', 'paytm support', 'phonepe support',
            'google pay support', 'install remote app', 'remote access',
            'कस्टमर केयर', 'हेल्पलाइन', 'सपोर्ट नंबर', 'स्क्रीन शेयर',
            'anydesk install', 'remote access', 'ऐनीडेस्क'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.86,
                'category' => 'Fake Customer Care Scam',
                'explanation' => 'This message may be a fake customer care scam. Scammers often ask users to call fake support numbers or install remote access apps like AnyDesk.'
            ];
        }

        // 13. APK / Malware Installation Scam
        if ($this->containsAny($content, [
            '.apk', 'install app', 'download app', 'update app',
            'new version apk', 'bank apk', 'loan apk', 'reward apk',
            'android package', 'install this application', 'apk file',
            'download this file', 'install update', 'security app',
            'ऐप डाउनलोड', 'apk डाउनलोड', 'ऐप इंस्टॉल', 'update apk',
            'एपीके', 'फाइल डाउनलोड'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.88,
                'category' => 'Malicious APK Scam',
                'explanation' => 'This message may be dangerous because it asks the user to install an APK or unknown app. Fraud APKs can steal SMS, contacts, banking details, or OTP.'
            ];
        }

        // 14. Social Media Account Hack Scam
        if ($this->containsAny($content, [
            'instagram verification', 'facebook verification', 'whatsapp verification',
            'account will be disabled', 'blue tick', 'copyright violation',
            'login alert', 'verify your profile', 'followers increase',
            'account recovery', 'social media verification', 'meta support',
            'page violation', 'community guidelines violation',
            'इंस्टाग्राम', 'फेसबुक', 'व्हाट्सएप वेरिफिकेशन',
            'अकाउंट बंद', 'ब्लू टिक', 'कॉपीराइट'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.81,
                'category' => 'Social Media Account Scam',
                'explanation' => 'This message may be a social media phishing scam because it asks for account verification, blue tick, copyright issue, or login recovery through suspicious steps.'
            ];
        }

        // 15. Romance / Trust Scam
        if ($this->containsAny($content, [
            'i love you', 'urgent help', 'send money for ticket',
            'medical emergency', 'stuck abroad', 'gift parcel for you',
            'customs fee for gift', 'relationship support', 'need money urgently',
            'family emergency', 'help me financially',
            'मदद चाहिए', 'पैसे भेजो', 'गिफ्ट पार्सल', 'विदेश में फंसा',
            'इमरजेंसी', 'मेडिकल मदद'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.74,
                'category' => 'Romance / Trust Scam',
                'explanation' => 'This message may be a romance or trust scam. Scammers build emotional trust and then ask for money, gifts, tickets, or emergency help.'
            ];
        }

        // 16. Rental / Marketplace Advance Scam
        if ($this->containsAny($content, [
            'advance payment', 'booking amount', 'token amount',
            'flat available', 'room rent', 'olx', 'quikr', 'marketplace',
            'send advance', 'delivery advance', 'army officer selling',
            'property booking', 'security deposit', 'vehicle delivery',
            'second hand phone', 'used bike', 'used car',
            'एडवांस पेमेंट', 'टोकन अमाउंट', 'किराया', 'फ्लैट उपलब्ध',
            'सिक्योरिटी डिपॉजिट'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.76,
                'category' => 'Marketplace / Advance Payment Scam',
                'explanation' => 'This message may be an advance payment scam. Fraudsters often ask for booking amount, token money, or delivery charges before showing the real product or property.'
            ];
        }

        // 17. Fake Charity / Donation Scam
        if ($this->containsAny($content, [
            'donation', 'charity', 'help child', 'medical fund',
            'urgent donation', 'ngo support', 'relief fund',
            'blood donation money', 'hospital help', 'donate now',
            'दान', 'चंदा', 'मदद करें', 'गरीब बच्चे', 'medical help',
            'एनजीओ', 'राहत कोष'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.70,
                'category' => 'Fake Charity Scam',
                'explanation' => 'This message may be a fake charity scam. Always verify donation requests through official and trusted sources before paying.'
            ];
        }

        // 18. Insurance / Policy Scam
        if ($this->containsAny($content, [
            'insurance bonus', 'policy matured', 'claim approved',
            'insurance refund', 'policy bonus', 'lic bonus',
            'premium pending', 'policy update', 'claim amount',
            'बीमा', 'पॉलिसी', 'एलआईसी बोनस', 'क्लेम अप्रूव',
            'प्रीमियम बकाया'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.78,
                'category' => 'Insurance / Policy Scam',
                'explanation' => 'This message may be an insurance scam because it talks about bonus, claim, policy update, or refund. Verify such messages only from official insurance channels.'
            ];
        }

        // 19. SIM Block / Telecom Scam
        if ($this->containsAny($content, [
            'sim blocked', 'sim will be deactivated', 'mobile number blocked',
            'telecom verification', 'link aadhaar mobile', 'number suspended',
            'jio verification', 'airtel verification', 'vi verification',
            'सिम बंद', 'मोबाइल नंबर बंद', 'नंबर ब्लॉक', 'सिम वेरिफिकेशन'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.80,
                'category' => 'SIM / Telecom Scam',
                'explanation' => 'This message may be a telecom scam because it threatens SIM blocking or asks for mobile verification. Verify only through official telecom apps or stores.'
            ];
        }

        // 20. Police / Legal Threat Scam
        if ($this->containsAny($content, [
            'police case', 'fir', 'legal notice', 'arrest warrant',
            'money laundering case', 'cyber crime case', 'court notice',
            'digital arrest', 'narcotics case', 'parcel contains illegal',
            'पुलिस केस', 'एफआईआर', 'कानूनी नोटिस', 'गिरफ्तारी',
            'कोर्ट नोटिस', 'साइबर केस'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.89,
                'category' => 'Police / Legal Threat Scam',
                'explanation' => 'This message may be a legal threat scam. Scammers use fear of police, FIR, court, or digital arrest to pressure victims. Real authorities do not demand money or OTP through messages.'
            ];
        }

        // 21. General Urgency / Threat / Pressure Scam
        if ($this->containsAny($content, [
            'urgent', 'immediately', 'within 24 hours', 'last warning',
            'final notice', 'limited time', 'act now', 'blocked today',
            'legal action', 'verify now', 'respond now',
            'तुरंत', 'अभी', 'आखिरी चेतावनी', 'कानूनी कार्रवाई',
            'आज बंद', 'जल्दी करें', 'फाइनल नोटिस'
        ])) {
            return [
                'is_phishing' => true,
                'confidence' => 0.72,
                'category' => 'Urgency / Threat Scam',
                'explanation' => 'This message uses urgency, fear, or pressure. Scammers commonly use such tactics to make users act quickly without verifying.'
            ];
        }

        // Safe message
        return [
            'is_phishing' => false,
            'confidence' => 0.25,
            'category' => 'Safe',
            'explanation' => 'This content does not contain common scam patterns based on the current rule-based check.'
        ];
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

    private function containsUrl(string $content): bool
    {
        return (bool) preg_match('/https?:\/\/|www\.|[a-z0-9\-]+\.(com|in|net|org|xyz|info|top|site|online|click|live|shop|app|co|ru|cn|biz|icu|cyou|buzz)/i', $content);
    }

}
