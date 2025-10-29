<?php
include 'config.php';
include 'recommendations.php';

// Test 1: Get all distinct features
$features = getAllDistinctFeatureValues($conn);
print_r($features);

// Test 2: Test bookToVector function
$sampleBook = [
    'author' => 'John Doe',
    'genre' => 'Fantasy',
    'location' => 'USA'
];
$vec = bookToVector($sampleBook, $features);
print_r($vec);

// Test 3: Test cosineSimilarity function
$vecA = [1, 0, 0];
$vecB = [1, 0, 0];
echo 'Similarity (should be 1): ' . cosineSimilarity($vecA, $vecB) . PHP_EOL;

$vecC = [0, 1, 0];
echo 'Similarity (should be 0): ' . cosineSimilarity($vecA, $vecC) . PHP_EOL;

// Test 4: Test getRecommendedBooks with a real user id
$user_id = 1;  // change to a real user id from your DB
$recommended = getRecommendedBooks($conn, $user_id);
print_r($recommended);
