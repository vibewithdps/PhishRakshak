<?php

namespace Tests\Unit;

use App\Services\ScamDetectionService;
use PHPUnit\Framework\TestCase;

class ScamDetectionTest extends TestCase
{
    private ScamDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScamDetectionService();
    }

    public function test_legitimate_otp_notification_is_marked_safe()
    {
        $result = $this->service->detect('Your OTP is 458921. Do not share it with anyone.', 'sms');

        $this->assertFalse($result['is_phishing']);
        $this->assertLessThan(0.20, $result['confidence']);
        $this->assertEquals('Legitimate OTP Notification', $result['category']);
    }

    public function test_legitimate_transaction_alert_is_marked_safe()
    {
        $result = $this->service->detect('Dear Customer, INR 2,450.00 debited from A/C XX4921. Avail Bal: INR 18,200. UPI Ref 38291048291.', 'sms');

        $this->assertFalse($result['is_phishing']);
        $this->assertLessThan(0.20, $result['confidence']);
        $this->assertEquals('Legitimate Transaction Alert', $result['category']);
    }

    public function test_kyc_phishing_sms_is_detected()
    {
        $result = $this->service->detect('Your SBI KYC is blocked. Call 9876543210 urgently to avoid permanent deactivation.', 'sms');

        $this->assertTrue($result['is_phishing']);
        $this->assertGreaterThanOrEqual(0.70, $result['confidence']);
        $this->assertEquals('KYC / Bank Phishing', $result['category']);
    }

    public function test_electricity_bill_scam_is_detected()
    {
        $result = $this->service->detect('Your electricity power will be disconnected tonight at 9:30 PM due to bill pending. Contact 9123456789 immediately.', 'sms');

        $this->assertTrue($result['is_phishing']);
        $this->assertGreaterThanOrEqual(0.75, $result['confidence']);
        $this->assertEquals('Electricity / Utility Bill Scam', $result['category']);
    }

    public function test_verified_domains_are_marked_safe()
    {
        $domains = [
            'https://amazon.in/dp/B08N5WRWNW',
            'https://flipkart.com/view-order',
            'https://irctc.co.in/nget/train-search',
            'https://github.com/torvalds/linux',
            'https://google.com'
        ];

        foreach ($domains as $domain) {
            $result = $this->service->detect($domain, 'url');
            $this->assertFalse($result['is_phishing'], "Failed for domain: {$domain}");
            $this->assertLessThan(0.25, $result['confidence']);
            $this->assertEquals('Verified Safe Domain', $result['category']);
        }
    }

    public function test_brand_typosquatting_is_detected_with_extreme_confidence()
    {
        $result = $this->service->detect('http://sbi-online-kyc-update.xyz/login.php', 'url');

        $this->assertTrue($result['is_phishing']);
        $this->assertGreaterThanOrEqual(0.90, $result['confidence']);
        $this->assertEquals('Brand Impersonation / Typosquatting', $result['category']);
    }

    public function test_ip_based_url_is_flagged()
    {
        $result = $this->service->detect('http://192.168.1.1/admin/login', 'url');

        $this->assertTrue($result['is_phishing']);
        $this->assertEquals('IP-Based Phishing Link', $result['category']);
    }

    public function test_official_apk_is_safe()
    {
        $result = $this->service->detect('com.whatsapp.apk', 'apk');

        $this->assertFalse($result['is_phishing']);
        $this->assertLessThan(0.15, $result['confidence']);
        $this->assertEquals('Verified Official App Package', $result['category']);
    }

    public function test_sideloaded_banking_trojan_is_detected()
    {
        $result = $this->service->detect('SBI_YONO_Update_Security.apk', 'apk');

        $this->assertTrue($result['is_phishing']);
        $this->assertGreaterThanOrEqual(0.90, $result['confidence']);
        $this->assertEquals('Sideloaded Banking Trojan', $result['category']);
    }

    public function test_digital_arrest_vishing_is_detected_with_highest_confidence()
    {
        $result = $this->service->detect('CBI officer on video call stating drugs found in your parcel and digital arrest warrant issued. Transfer funds to RBI safe account.', 'call');

        $this->assertTrue($result['is_phishing']);
        $this->assertGreaterThanOrEqual(0.95, $result['confidence']);
        $this->assertEquals('Digital Arrest / Police Extortion Vishing', $result['category']);
    }

    public function test_confidence_scores_vary_dynamically()
    {
        $samples = [
            ['sms', 'Your OTP is 458921. Do not share it with anyone.'],
            ['sms', 'Your SBI KYC is blocked. Call 9876543210 urgently to avoid permanent deactivation.'],
            ['sms', 'Congratulations! You won 25 Lakhs in KBC Lottery. Send 5000 processing fee to claim.'],
            ['url', 'https://amazon.in/dp/B08N5WRWNW'],
            ['url', 'http://sbi-online-kyc-update.xyz/login.php'],
            ['apk', 'com.whatsapp.apk'],
            ['apk', 'SBI_YONO_Update_Security.apk'],
            ['call', 'CBI officer on video call stating drugs found in your parcel and digital arrest warrant issued. Transfer funds to RBI safe account.']
        ];

        $scores = [];
        foreach ($samples as $s) {
            $res = $this->service->detect($s[1], $s[0]);
            $scores[] = $res['confidence'];
        }

        // Must have diverse, non-identical scores across tests
        $uniqueScores = array_unique($scores);
        $this->assertGreaterThanOrEqual(5, count($uniqueScores), 'Confidence scores should be dynamic and diverse');
    }
}
