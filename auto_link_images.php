<?php
require_once 'auth_check.php';
require_once '../config/database.php';

echo "<h1>Auto-Link Vehicle Images</h1>";
echo "<pre>";

$dir = '../uploads/vehicles/';
$files = scandir($dir);
$images = [];
foreach ($files as $f) {
    if ($f === '.' || $f === '..') continue;
    if (is_dir($dir . $f)) continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','jfif'])) {
        $images[] = $f;
    }
}

echo "Found " . count($images) . " image files\n\n";

// Normalize a string: lowercase, remove extensions, dots, underscores, numbers
function normalize($str) {
    $str = strtolower($str);
    $str = preg_replace('/\.(jpg|jpeg|png|gif|webp|jfif)$/i', '', $str);
    $str = preg_replace('/[_\-\.]+/', ' ', $str);
    $str = preg_replace('/\b\d{5,}\b/', '', $str); // remove long numbers like 1779118195
    $str = preg_replace('/\(\d+\)/', '', $str);     // remove (5), (11)
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

// Get vehicles
$vehicles = [];
$result = mysqli_query($conn, "SELECT id, brand, model, registration_number, image FROM vehicles ORDER BY id");
while ($v = mysqli_fetch_assoc($result)) {
    $vehicles[] = $v;
}

echo "Found " . count($vehicles) . " vehicles in DB\n\n";
echo "=== MATCHING ===\n\n";

$updated = 0;
$unmatched_vehicles = [];

foreach ($vehicles as $v) {
    $brand_norm = normalize($v['brand']);
    $model_norm = normalize($v['model']);
    $search_strings = [
        normalize($v['brand'] . ' ' . $v['model']),  // "toyota harrier"
        normalize($v['model'] . ' ' . $v['brand']),  // "harrier toyota"
        $model_norm,                                  // "harrier"
        $brand_norm,                                  // "toyota"
    ];

    $best_match = null;
    $best_score = 0;

    foreach ($images as $img) {
        $img_norm = normalize($img);

        foreach ($search_strings as $idx => $search) {
            if ($search === '') continue;

            // Full-phrase match
            if (strpos($img_norm, $search) !== false) {
                $score = 100 - $idx * 20; // brand+model full match > model only
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $img;
                }
            }

            // Word-by-word: all words of search must appear in img_norm
            $words = explode(' ', $search);
            if (count($words) > 1) {
                $all_found = true;
                foreach ($words as $w) {
                    if (strlen($w) < 3) continue;
                    if (strpos($img_norm, $w) === false) {
                        $all_found = false;
                        break;
                    }
                }
                if ($all_found) {
                    $score = 80 - $idx * 15;
                    if ($score > $best_score) {
                        $best_score = $score;
                        $best_match = $img;
                    }
                }
            }
        }
    }

    if ($best_match) {
        $path = 'uploads/vehicles/' . $best_match;
        $id = (int)$v['id'];
        mysqli_query($conn, "UPDATE vehicles SET image = '$path' WHERE id = $id");
        echo "✅ Vehicle #{$v['id']} {$v['brand']} {$v['model']} => $best_match\n";
        $updated++;
    } else {
        echo "❌ NO MATCH: #{$v['id']} {$v['brand']} {$v['model']}\n";
        $unmatched_vehicles[] = $v;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Matched:   $updated\n";
echo "Unmatched: " . count($unmatched_vehicles) . "\n\n";

if (!empty($unmatched_vehicles)) {
    echo "These vehicles need manual image assignment:\n";
    foreach ($unmatched_vehicles as $v) {
        echo "  - #{$v['id']} {$v['brand']} {$v['model']}\n";
    }
}

echo "\nImages not yet used:\n";
$used = [];
foreach ($vehicles as $v) {
    if (!empty($v['image'])) $used[] = basename($v['image']);
}
$result2 = mysqli_query($conn, "SELECT image FROM vehicles WHERE image IS NOT NULL AND image != ''");
while ($row = mysqli_fetch_assoc($result2)) {
    $used[] = basename($row['image']);
}
$unused = array_diff($images, $used);
echo "  " . count($unused) . " unused images:\n";
foreach (array_slice($unused, 0, 20) as $u) {
    echo "  - $u\n";
}

echo "</pre>";
echo "<p><a href='vehicles.php'>← Back to Manage Vehicles</a></p>";
