<?php
/**
 * Test Announcement Banner Upload to Supabase
 * 
 * This script tests if the StorageService can successfully upload
 * a test image to the announcement-banners bucket.
 */

require_once __DIR__ . '/backend/services/StorageService.php';

echo "=== Test Announcement Banner Upload ===\n\n";

// Create a simple test image (1x1 pixel red PNG)
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==');
$testImagePath = sys_get_temp_dir() . '/test_banner_' . uniqid() . '.png';
file_put_contents($testImagePath, $testImageData);

echo "✓ Created test image: {$testImagePath}\n";
echo "  Size: " . filesize($testImagePath) . " bytes\n";
echo "  MIME: " . mime_content_type($testImagePath) . "\n\n";

// Attempt upload
echo "Uploading to Supabase bucket 'announcement-banners'...\n";

try {
    $publicUrl = StorageService::uploadAnnouncementBanner(
        $testImagePath,
        'image/png',
        'test_'
    );
    
    echo "✓ Upload successful!\n\n";
    echo "Public URL:\n{$publicUrl}\n\n";
    
    // Test if URL is accessible
    echo "Testing URL accessibility...\n";
    $ch = curl_init($publicUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✓ URL is accessible! (HTTP {$httpCode})\n";
        echo "\n=== SUCCESS ===\n";
        echo "✓ Banner upload is working correctly!\n";
        echo "✓ You can now create announcements with banner images.\n";
        echo "\nTest URL: {$publicUrl}\n";
        echo "You can open this URL in your browser to see the test image.\n";
    } else {
        echo "⚠ URL returned HTTP {$httpCode}\n";
        echo "The upload worked, but the URL might not be publicly accessible.\n";
        echo "Check if the bucket is set to PUBLIC in Supabase.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Upload failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Verify SUPABASE_URL in .env: " . (getenv('SUPABASE_URL') ?: 'NOT SET') . "\n";
    echo "2. Verify SUPABASE_SERVICE_KEY in .env: " . (getenv('SUPABASE_SERVICE_KEY') ? 'SET' : 'NOT SET') . "\n";
    echo "3. Verify bucket 'announcement-banners' exists and is public\n";
    echo "4. Check PHP error logs for more details\n";
    
    exit(1);
}

// Cleanup
@unlink($testImagePath);
echo "\n✓ Cleaned up test image\n";

exit(0);
