<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Utility: normalize mixed locale date strings to 'YYYY-MM-DD' for MySQL DATE. */
function normalizeDate(string $dateStr): string
{
    $dateStr = trim($dateStr);

    // Try strtotime first (handles most formats)
    $ts = strtotime($dateStr);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    // Fallback: DateTime parser
    $dt = date_create($dateStr);
    if ($dt !== false) {
        return $dt->format('Y-m-d');
    }

    // If parsing fails, store today's date
    return date('Y-m-d');
}

/** Utility: ensure recommendations fit VARCHAR(2500) and are clean. */
function formatRecommendations(?string $recs): string
{
    $recs = trim((string)$recs);
    if ($recs === '') {
        return '';
    }
    if (mb_strlen($recs, 'UTF-8') > 2500) {
        return mb_substr($recs, 0, 1497, 'UTF-8') . '…';
    }
    return $recs;
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Read JSON body
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        throw new RuntimeException('Empty request body.');
    }

    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    // Extract & sanitize
    $Organization          = trim((string)($data['Organization']          ?? ''));
    $Contact_Person        = trim((string)($data['Contact_Person']        ?? ''));
    $Contact_Person_role   = trim((string)($data['Contact_Person_role']   ?? ''));
    $Email                 = trim((string)($data['Email']                 ?? ''));
    $Industry              = trim((string)($data['Industry']              ?? ''));
    $DateRaw               = trim((string)($data['Date']                  ?? ''));
    $Overall_Score         = (float)($data['Overall_Score']               ?? 0);
    $Maturity_Stage        = trim((string)($data['Maturity_Stage']        ?? ''));
    $Strategy              = (float)($data['Strategy']                    ?? 0);
    $DataCat               = (float)($data['Data']                        ?? 0);
    $Technology            = (float)($data['Technology']                  ?? 0);
    $People                = (float)($data['People']                      ?? 0);
    $Governance            = (float)($data['Governance']                  ?? 0);
    $Recommendations       = formatRecommendations((string)($data['Recommendations'] ?? ''));

    // Validate required fields
    $missing = [];
    foreach ([
        'Organization' => $Organization,
        'Contact_Person' => $Contact_Person,
        'Contact_Person_role' => $Contact_Person_role,
        'Email' => $Email,
        'Industry' => $Industry,
        'Date' => $DateRaw,
        'Maturity_Stage' => $Maturity_Stage,
    ] as $field => $value) {
        if ($value === '') $missing[] = $field;
    }
    if ($missing) {
        throw new InvalidArgumentException('Missing required fields: ' . implode(', ', $missing));
    }
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email format.');
    }

    $Date = normalizeDate($DateRaw);

    // Insert
    $pdo = getPdo();

    $sql = "
        INSERT INTO `survey_results`
        (`Organization`, `Contact_Person`, `Contact_Person_role`, `Email`, `Industry`, `Date`,
         `Overall_Score`, `Maturity_Stage`, `Strategy`, `Data`, `Technology`, `People`, `Governance`, `Recommendations`)
        VALUES
        (:Organization, :Contact_Person, :Contact_Person_role, :Email, :Industry, :Date,
         :Overall_Score, :Maturity_Stage, :Strategy, :DataCat, :Technology, :People, :Governance, :Recommendations)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':Organization'         => $Organization,
        ':Contact_Person'       => $Contact_Person,
        ':Contact_Person_role'  => $Contact_Person_role,
        ':Email'                => $Email,
        ':Industry'             => $Industry,
        ':Date'                 => $Date,
        ':Overall_Score'        => $Overall_Score,
        ':Maturity_Stage'       => $Maturity_Stage,
        ':Strategy'             => $Strategy,
        ':DataCat'              => $DataCat,
        ':Technology'           => $Technology,
        ':People'               => $People,
        ':Governance'           => $Governance,
        ':Recommendations'      => $Recommendations,
    ]);

    http_response_code(201);
    echo json_encode([
        'status'     => 'ok',
        'survey_id'  => (int)$pdo->lastInsertId(),
        'message'    => 'Inserted successfully'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}