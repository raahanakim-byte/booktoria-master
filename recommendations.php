<?php
function getAllDistinctFeatureValues($conn) {
    $authors = [];
    $genres = [];
    $locations = [];

    $res = mysqli_query($conn, "SELECT DISTINCT LOWER(TRIM(author)) AS author FROM products WHERE author <> ''");
    while ($r = mysqli_fetch_assoc($res)) {
        $authors[] = $r['author'];
    }

    $res = mysqli_query($conn, "SELECT DISTINCT LOWER(TRIM(genre)) AS genre FROM products WHERE genre <> ''");
    while ($r = mysqli_fetch_assoc($res)) {
        $genres[] = $r['genre'];
    }

    $res = mysqli_query($conn, "SELECT DISTINCT LOWER(TRIM(location)) AS location FROM products WHERE location <> ''");
    while ($r = mysqli_fetch_assoc($res)) {
        $locations[] = $r['location'];
    }

    return [
        'authors'   => array_values(array_unique($authors)),
        'genres'    => array_values(array_unique($genres)),
        'locations' => array_values(array_unique($locations)),
    ];
}

function bookToVector($book, $feature_indices) {
    $vec = [];

    $book_author   = strtolower(trim($book['author'] ?? ''));
    $book_genre    = strtolower(trim($book['genre'] ?? ''));
    $book_location = strtolower(trim($book['location'] ?? ''));

    foreach ($feature_indices['authors'] as $author) {
        $vec[] = ($author === $book_author) ? 1 : 0;
    }
    foreach ($feature_indices['genres'] as $genre) {
        $vec[] = ($genre === $book_genre) ? 1 : 0;
    }
    foreach ($feature_indices['locations'] as $location) {
        $vec[] = ($location === $book_location) ? 1 : 0;
    }

    return $vec;
}

function addVectors(&$accum, $vec, $weight = 1) {
    foreach ($vec as $i => $v) {
        if (!isset($accum[$i])) $accum[$i] = 0;
        $accum[$i] += $v * $weight;
    }
}

function cosineSimilarity($vecA, $vecB) {
    $dot = 0;
    $normA = 0;
    $normB = 0;
    $len = max(count($vecA), count($vecB));
    for ($i = 0; $i < $len; $i++) {
        $a = $vecA[$i] ?? 0;
        $b = $vecB[$i] ?? 0;
        $dot += $a * $b;
        $normA += $a * $a;
        $normB += $b * $b;
    }
    if ($normA == 0 || $normB == 0) return 0;
    return $dot / (sqrt($normA) * sqrt($normB));
}

function parseTotalProductsString($str) {
    $names = [];
    $parts = explode(',', $str);
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') continue;
        if (preg_match('/^(.+?)\s*(\(|x)\s*\d+/i', $part, $m)) {
            $names[] = trim($m[1]);
        } else {
            $names[] = $part;
        }
    }
    return $names;
}

function getRecommendedBooks($conn, $user_id = null, $limit = 6) {
    // Guest mode (no user_id)
    if (!$user_id) {
        $query = "
            (SELECT id, name AS title, author, genre, price, image, location, 'new' AS source
             FROM products ORDER BY id DESC LIMIT 3)
            UNION
            (SELECT id, title, author, genre, price, image, location, 'thrift' AS source
             FROM thrift_products ORDER BY posted_on DESC LIMIT 3)
        ";
        $res = mysqli_query($conn, $query) or die('recommendation query failed');
        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    }

    // --- Personalized mode below (your existing logic) ---
    $features = getAllDistinctFeatureValues($conn);
    $feature_indices = [
        'authors' => $features['authors'],
        'genres'  => $features['genres'],
        'locations' => $features['locations'],
    ];

    $user_profile = [];
    $purchased_book_names = [];

    $order_q = mysqli_query($conn, "SELECT total_products FROM `orders` WHERE user_id = '$user_id'");
    while ($order = mysqli_fetch_assoc($order_q)) {
        $book_names = parseTotalProductsString($order['total_products']);
        foreach ($book_names as $name) {
            $purchased_book_names[] = $name;
            $book_res = mysqli_query($conn, "SELECT * FROM products WHERE name LIKE '%" . mysqli_real_escape_string($conn, $name) . "%' LIMIT 1");
            if ($book_data = mysqli_fetch_assoc($book_res)) {
                $vec = bookToVector($book_data, $feature_indices);
                addVectors($user_profile, $vec, 1);
            }
        }
    }

    if (empty($user_profile)) {
        $user_profile = array_fill(0, count($features['authors']) + count($features['genres']) + count($features['locations']), 1);
    }

    $scores = [];
    $all_books = mysqli_query($conn, "SELECT * FROM products");
    while ($book = mysqli_fetch_assoc($all_books)) {
        if (in_array($book['name'], $purchased_book_names)) continue;
        $book_vec = bookToVector($book, $feature_indices);
        $sim = cosineSimilarity($user_profile, $book_vec);
        $scores[] = ['book' => $book, 'score' => $sim];
    }

    usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

    $recommended = [];
    foreach ($scores as $entry) {
        if (count($recommended) >= $limit) break;
        $recommended[] = $entry['book'];
    }

    return $recommended;
}
?>
