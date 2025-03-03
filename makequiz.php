<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 入力データを取得
    $quizText = $_POST["quizText"] ?? '';
    $optionA = $_POST["optionA"] ?? '';
    $optionB = $_POST["optionB"] ?? '';
    $optionC = $_POST["optionC"] ?? '';
    $correctOptionKey = $_POST["correctOption"] ?? '';
    $explanation = $_POST["explanation"] ?? '';
    $quizID = $_POST["quizID"] ?? '';

    // QuizIDが空の場合はエラーを返す
    if (empty($quizID)) {
        echo "QuizIDが指定されていません。";
        exit;
    }

    // 保存先フォルダを設定
    $quizDir = "./quiz/" . basename($quizID);

    // フォルダが存在しない場合はエラーを返す
    if (!is_dir($quizDir)) {
        echo "指定されたQuizIDに対応するフォルダが存在しません: $quizDir";
        exit;
    }

    // 正解の選択肢を決定
    $correctOption = '';
    $incorrectOptions = [];
    switch ($correctOptionKey) {
        case 'A':
            $correctOption = $optionA;
            $incorrectOptions = [$optionB, $optionC];
            break;
        case 'B':
            $correctOption = $optionB;
            $incorrectOptions = [$optionA, $optionC];
            break;
        case 'C':
            $correctOption = $optionC;
            $incorrectOptions = [$optionA, $optionB];
            break;
        default:
            echo "正解の選択肢が無効です。";
            exit;
    }

    // ランダムな16桁のファイル名を生成
    $randomFileName = str_pad(random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT) . ".csv";

    // 保存先ファイルパスを設定
    $filePath = $quizDir . "/" . $randomFileName;

    // CSVフォーマットで1行を作成
    $newLine = implode(",", [
        escapeCsv($quizText),
        escapeCsv($correctOption),
        escapeCsv($incorrectOptions[0]),
        escapeCsv($incorrectOptions[1]),
        escapeCsv($explanation)
    ]) . "\n";

    // ファイルに書き込み
    if (file_put_contents($filePath, $newLine) !== false) {
        echo "クイズが正常に保存されました。ファイル名: $randomFileName";
    } else {
        echo "クイズの保存に失敗しました。";
    }
} else {
    echo "無効なリクエストです。";
}

// CSV用エスケープ関数
function escapeCsv($value) {
    // 値にカンマやダブルクォーテーションが含まれる場合を処理
    if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        $value = str_replace('"', '""', $value); // ダブルクォートをエスケープ
        $value = '"' . $value . '"'; // ダブルクォートで囲む
    }
    return $value;
}
?>
