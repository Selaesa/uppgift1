<?php
function addAuthor($authorName, $authorBirthYear, $pdo) {
    try {
    $stmt = $pdo->prepare("INSERT INTO forfattare (namn, fodelsear) VALUES (:namn, :fodelsear)");
    $stmt->bindParam(':namn', $authorName, PDO::PARAM_STR);
    $stmt->bindParam(':fodelsear', $authorBirthYear, PDO::PARAM_INT);
    return $stmt->execute();
} catch (PDOException $e) {
    return false;
}
}

function addBook($bookTitle, $bookAuthorId, $bookYear, $pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO bok (titel, forfattar_id, utgivningsar) VALUES (:titel, :forfattar_id, :utgivningsar)");
        $stmt->bindParam(':titel', $bookTitle, PDO::PARAM_STR);
        $stmt->bindParam(':forfattar_id', $bookAuthorId, PDO::PARAM_INT);
        $stmt->bindParam(':utgivningsar', $bookYear, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        //echo $e->getMessage();
        return false;
    }
}

function addBorrower($borrowerName, $pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO lantagare (namn, medlem_since) VALUES (:namn, :medlem_since)");
        $stmt->bindParam(':namn', $borrowerName, PDO::PARAM_STR);
        $stmt->bindParam(':medlem_since', date('Y-m-d'), PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        //echo $e->getMessage();
        return false;
    }
}


function loanBook($bookId, $borrowerId, $loanDate, $pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO lan (bok_id, lantagar_id, lanedatum) VALUES (:bok_id, :lantagar_id, :lanedatum)");

        return $stmt->execute([
            ':bok_id' => $bookId,
            ':lantagar_id' => $borrowerId,
            ':lanedatum' => $loanDate
        ]);

    } catch (PDOException $e) {
        return false;
    }
}

function renderBookLoans ($loanStatus, $pdo){
    $queryString = "SELECT lan.lanedatum, lan.aterlamningsdatum, bok.titel, lantagare.namn FROM lan 
    INNER JOIN bok ON lan.bok_id = bok.bok_id
    INNER JOIN lantagare ON lan.lantagar_id = lantagare.lantagar_id";

        if ($loanStatus === 0) {
            $queryString .= " WHERE lan.aterlamningsdatum IS NULL ";
        } else {
            $queryString .= " WHERE lan.aterlamningsdatum IS NOT NULL ";
        }

    $queryString .= "ORDER BY lan.lanedatum ASC";

    try{
        $result = $pdo->query($queryString)->fetchAll();
        return $result;
    } catch (PDOException $e) {
        return false;
    }
}
