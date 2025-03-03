<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quiz_group'])) {
        // Get QuizID from user input
        $quizID = $_POST['quiz_id'] ?? '';

        if (!empty($quizID)) {
            $quizDir = __DIR__ . "/quiz/$quizID";
            $outputFile = __DIR__ . "/quiz/$quizID.csv";

            if (is_dir($quizDir)) {
                $allRows = [];
                $headers = ["問題文", "正解の選択肢", "不正解の選択肢1", "不正解の選択肢2", "解説", "削除"];

                foreach (glob("$quizDir/*.csv") as $file) {
                    $rows = array_map('str_getcsv', file($file));

                    $fileName = basename($file);
                    foreach ($rows as $index => $row) {
                        if ($index === 0 && $row === ["問題文", "正解の選択肢", "不正解の選択肢1", "不正解の選択肢2", "解説"]) {
                            continue; // Skip duplicate headers
                        }
                        $row[] = '<form method="POST" style="display:inline;">' .
                                 '<input type="hidden" name="delete_file" value="' . htmlspecialchars($fileName, ENT_QUOTES) . '">' .
                                 '<input type="hidden" name="delete_quiz_id" value="' . htmlspecialchars($quizID, ENT_QUOTES) . '">' .
                                 '<button type="submit" onclick="return confirm(\"削除しますか？\")">削除</button>' .
                                 '</form>';
                        $allRows[] = $row;
                    }
                }

                // Add headers back
                array_unshift($allRows, $headers);

                // Write to output CSV file
                $fp = fopen($outputFile, 'w');
                // Add BOM for UTF-8
                fwrite($fp, "\xEF\xBB\xBF");
                foreach ($allRows as $row) {
                    fputcsv($fp, $row);
                }
                fclose($fp);

                // Generate table for display
                $tableHtml = "<table class='quiz-table'><thead><tr>";
                foreach ($headers as $header) {
                    $tableHtml .= "<th>" . htmlspecialchars($header) . "</th>";
                }
                $tableHtml .= "</tr></thead><tbody>";

                foreach ($allRows as $index => $row) {
                    if ($index === 0) continue; // Skip headers for table body
                    $tableHtml .= "<tr>";
                    foreach ($row as $cell) {
                        $tableHtml .= "<td>" . $cell . "</td>";
                    }
                    $tableHtml .= "</tr>";
                }

                $tableHtml .= "</tbody></table>";

                $displayTable = $tableHtml;
                $downloadButton = "<a href='quiz/$quizID.csv' download='$quizID.csv'><button class='action-button'>ダウンロード</button></a>";
                $uploadForm = "<form method='POST' enctype='multipart/form-data' style='display:inline;'>
                    <input type='hidden' name='upload_quiz_id' value='$quizID'>
                    <input type='file' name='uploaded_file' accept='.csv' required>
                    <button type='submit' name='upload_file' class='action-button'>アップロード</button>
                </form>";
                $deleteAllButton = "<form method='POST' style='display:inline;'>
                    <input type='hidden' name='delete_all_quiz_id' value='$quizID'>
                    <button type='submit' class='action-button' onclick='return confirm(\"全ての行を削除してよろしいですか？\")'>全削除</button>
                </form>";
            } else {
                $displayTable = "<p>Quiz directory does not exist.</p>";
                $downloadButton = "";
                $uploadForm = "";
                $deleteAllButton = "";
            }
        } else {
            $displayTable = "<p>QuizID is required.</p>";
            $downloadButton = "";
            $uploadForm = "";
            $deleteAllButton = "";
        }
    } elseif (isset($_POST['upload_file']) && isset($_POST['upload_quiz_id'])) {
        $quizID = $_POST['upload_quiz_id'];
        $quizDir = __DIR__ . "/quiz/$quizID";

        if (!is_dir($quizDir)) {
            mkdir($quizDir, 0777, true);
        }

        if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['uploaded_file']['tmp_name'];
            $fileContents = array_map('str_getcsv', file($uploadedFile));

            // Skip headers and process rows
            foreach ($fileContents as $index => $row) {
                if ($index === 0 || count($row) < 5) continue; // Skip headers or invalid rows

                // Keep only columns up to "解説"
                $filteredRow = array_slice($row, 0, 5);

                // Generate a random 16-character filename
                $randomFileName = bin2hex(random_bytes(8)) . ".csv";
                $destination = $quizDir . "/" . $randomFileName;

                // Save the filtered row into a new CSV file
                $fp = fopen($destination, 'w');
                fputcsv($fp, $filteredRow);
                fclose($fp);
            }

            echo "<p>File uploaded and processed successfully.</p>";
        } else {
            echo "<p>No file uploaded or an error occurred.</p>";
        }
    } elseif (isset($_POST['delete_file']) && isset($_POST['delete_quiz_id'])) {
        $quizID = $_POST['delete_quiz_id'];
        $fileName = $_POST['delete_file'];
        $filePath = __DIR__ . "/quiz/$quizID/$fileName";

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                echo "<p>File $fileName deleted successfully.</p>";
            } else {
                echo "<p>Failed to delete $fileName.</p>";
            }
        } else {
            echo "<p>File $fileName does not exist.</p>";
        }
    } elseif (isset($_POST['delete_all_quiz_id'])) {
        $quizID = $_POST['delete_all_quiz_id'];
        $quizDir = __DIR__ . "/quiz/$quizID";

        if (is_dir($quizDir)) {
            foreach (glob("$quizDir/*.csv") as $file) {
                unlink($file);
            }
            echo "<p>All files in $quizDir have been deleted successfully.</p>";
        } else {
            echo "<p>Quiz directory does not exist.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            text-align: center;
        }
        h1 {
            color: #333;
        }
        .quiz-table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        .quiz-table th, .quiz-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .quiz-table th {
            background-color: #f4f4f4;
        }
        .action-button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 5px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .action-button:hover {
            background-color: #0056b3;
        }
        form {
            margin-bottom: 10px;
        }
    </style>

</head>
<body>
    <h1>Quiz Manager</h1>
    <form method="POST">
        <label for="quiz_id">QuizID:</label>
        <input type="text" id="quiz_id" name="quiz_id" required>
        <button type="submit" name="update_quiz_group" class="action-button">クイズグループ更新</button>
    </form>
    <hr>
    <div>
        <?php 
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($displayTable)) {
            echo $displayTable;
            echo $uploadForm;
            echo $downloadButton;
            echo $deleteAllButton;
        } 
        ?>
    </div>
</body>
</html>
