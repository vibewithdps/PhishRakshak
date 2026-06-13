<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScamDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScanController extends Controller
{
    private ScamDetectionService $scamDetectionService;

    public function __construct(ScamDetectionService $scamDetectionService)
    {
        $this->scamDetectionService = $scamDetectionService;
    }

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:sms,url,apk,email,mail,call',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type') === 'mail' ? 'email' : $request->input('type');

        $result = $this->scamDetectionService->detect(
            $request->input('content'),
            $type
        );

        $scan = Scan::create([
            'user_id' => $request->user()->id,
            'type' => $type,
            'content' => $request->input('content'),
            'is_phishing' => (bool) ($result['is_phishing'] ?? false),
            'confidence' => (float) ($result['confidence'] ?? 0),
            'category' => $result['category'] ?? 'Unknown',
            'explanation' => $result['explanation'] ?? 'Scan completed.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Scan completed successfully',
            'data' => $scan,
        ]);
    }

    public function history(Request $request)
    {
        $scans = Scan::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $scans,
        ]);
    }
}